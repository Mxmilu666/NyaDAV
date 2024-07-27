<?php
use Swoole\Coroutine\Http\Client;

class NyaDAV {
    public $host;
    public $port;
    public $path;
    public $ssl;
    public $auth;
    public $client;
    public $err;

    public function __construct($host, $port, $ssl = false) {
        $this->host = $host;
        $this->port = $port;
        $this->ssl = $ssl;
        $this->client = new Client($this->host, $this->port, $this->ssl);
    }

    public function set(array $settings){
        $auth = [];
        $depth = 1;
        if (isset($settings['auth']) && is_array($settings['auth'])) {
            $this->auth = base64_encode($settings['auth']['username'] . ':' . $settings['auth']['password']);
            $auth = ['Authorization' => "Basic " . $this->auth];
        }

        if (isset($settings['depth'])) {
            $depth = $settings['depth'];
        }

        $this->client->setHeaders([
            'Depth' => $depth,
            ...$auth
        ]);
    }

    public function getfilelist($path) {
        $client = $this->client;
        $client->setMethod('PROPFIND');
        $client->execute($path);
        if ($client->statusCode === 401) {
            $this->err = "Authentication failed.";
            return false;
        } elseif ($client->statusCode === -1) {
            $this->err = "Connection failed: " . $client->errCode;
            return false;
        } else {
            $responseXml = $client->body;
            $xml = new SimpleXMLElement($responseXml);
            $items = [];
            foreach ($xml->children('D', true)->response as $response) {
                $href = (string) $response->href;
                $displayName = (string) $response->propstat->prop->displayname;
                $lastModified = (string) $response->propstat->prop->getlastmodified;
                $creationDate = (string) $response->propstat->prop->creationdate;
                $isCollection = isset($response->propstat->prop->resourcetype->collection) ? "Directory" : "File";
                $list[] = [
                    'Path' => $href,
                    'Name' => $displayName,
                    'Type' => $isCollection,
                    'LastModified' => $lastModified,
                    'CreationDate' => $creationDate,
                ];
            }
        }
        return $list;
    }
    function getfilesize($path) {
        $client = $this->client;
        $client->set(['timeout' => 10]);
        $client->setMethod('HEAD');
        $client->execute($path);
        $fileSize = $client->headers['content-length'] ?? null; 
        return $fileSize;
    }

    public function getfile($path,$filename = null) {
        $client = $this->client;
        if(is_null($filename)){
            $client->get($path);
            print_r($client->headers);
            return [
                'etag' => $client->headers['etag'],
                'raw_url' => $client->headers['location']
            ];
        }
        else{
            return $client->download($path,$filename);
        }
    }
}