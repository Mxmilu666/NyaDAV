<?php
namespace NyaDAV;

use Swoole\Coroutine\Http\Client;
use Exception;

class NyaDAVException extends Exception {}

class NyaDAV {
    private $host;
    private $port;
    private $path;
    private $ssl;
    private $auth;
    private $client;
    public $err;

    /**
     * NyaDAV constructor.
     * @param string $host
     * @param int $port
     * @param bool $ssl
     * @throws NyaDAVException
     */
    public function __construct($host, $port, $ssl = false) {
        if (empty($host) || empty($port)) {
            $this->err = "Host and port must be provided.";
            throw new NyaDAVException("Host and port must be provided.");
        }

        $this->host = $host;
        $this->port = $port;
        $this->ssl = $ssl;
        $this->client = new Client($this->host, $this->port, $this->ssl);
    }

    /**
     * Set client settings.
     * @param array $settings
     */
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

    /**
     * Get file list from the specified path.
     * @param string $path
     * @return array|false
     * @throws NyaDAVException
     */
    public function getfilelist($path) {
        $client = $this->client;
        $client->setMethod('PROPFIND');
        $client->execute($path);

        switch ($client->statusCode) {
            case 401:
                $this->err = "Authentication failed.";
                throw new NyaDAVException("Authentication failed.");
                return false;
            case -1:
                $this->err = "Connection failed: " . $client->errCode;
                throw new NyaDAVException("Connection failed: " . $client->errCode);
                return false;
            case 207:
                $responseXml = $client->body;
                $xml = new \SimpleXMLElement($responseXml);
                $list = [];
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
                return $list;
            default:
                $this->err = "Failed to fetch file list: HTTP " . $client->statusCode;
                throw new NyaDAVException("Failed to fetch file list: HTTP " . $client->statusCode);
                return false;
        }
    }

    /**
     * Get file size of the specified path.
     * @param string $path
     * @return int|null
     */
    public function getfilesize($path) {
        $client = $this->client;
        $client->set(['timeout' => 10]);
        $client->setMethod('HEAD');
        $client->execute($path);
        return $client->headers['content-length'] ?? null; 
    }

    /**
     * Get file from the specified path.
     * @param string $path
     * @param string|null $filename
     * @return array|false
     */
    public function getfile($path, $filename = null) {
        $client = $this->client;
        if (is_null($filename)) {
            $client->get($path);
            switch ($client->statusCode) {
                case 404:
                    $this->err = "Failed to getfile: HTTP " . $client->statusCode;
                    throw new NyaDAVException("Failed to getfile: HTTP " . $client->statusCode);
                    return false;
                default:
                    return [
                        'etag' => $client->headers['etag'],
                        'size'=> $client->headers['content-length'],
                        'raw_url' => $client->headers['location']
                    ];
                
            }
        } else {
            return $client->download($path, $filename);
            switch ($client->statusCode) {
                case 404:
                    $this->err = "Failed to getfile: HTTP " . $client->statusCode;
                    throw new NyaDAVException("Failed to getfile: HTTP " . $client->statusCode);
                    return false; 
            }
        }
    }

    /**
     * Upload file to the specified path.
     * @param string $path
     * @param string $filename
     * @return bool
     * @throws NyaDAVException
     */
    public function uploadfile($path, $filename) {
        $client = $this->client;
        $fileContents = file_get_contents($filename);
        if ($fileContents === false) {
            $this->err = "Failed to read the local file.";
            throw new NyaDAVException("Failed to read the local file.");
            return false;
        }

        $client->setMethod('PUT');
        $client->setData($fileContents);
        $client->execute($path);

        switch ($client->statusCode) {
            case 401:
                $this->err = "Authentication failed.";
                throw new NyaDAVException("Authentication failed.");
                return false;
            case -1:
                $this->err = "Connection failed: " . $client->errCode;
                throw new NyaDAVException("Connection failed: " . $client->errCode);
                return false;
            case $client->statusCode >= 400:
                $this->err = "Failed to upload file: HTTP " . $client->statusCode;
                throw new NyaDAVException("Failed to upload file: HTTP " . $client->statusCode);
                return false;
            default:
                return true;
        }
    }

    /**
     * Delete file from the specified path.
     * @param string $path
     * @return bool
     * @throws NyaDAVException
     */
    public function deletefile($path) {
        $client = $this->client;
        $client->setMethod('DELETE');
        $client->execute($path);

        switch ($client->statusCode) {
            case 401:
                $this->err = "Authentication failed.";
                throw new NyaDAVException("Authentication failed.");
                return false;
            case -1:
                $this->err = "Connection failed: " . $client->errCode;
                throw new NyaDAVException("Connection failed: " . $client->errCode);
                return false;
            case $client->statusCode >= 400:
                $this->err = "Failed to delete file: HTTP " . $client->statusCode;
                throw new NyaDAVException("Failed to delete file: HTTP " . $client->statusCode);
                return false;
            default:
                return true;
        }
    }

        /**
     * Retrieve whether the file exists.
     * @param string $path
     * @return bool
     * @throws NyaDAVException
     */
    public function file_exists($path) {
        $client = $this->client;
        $client->get($path);
        switch ($client->statusCode) {
            case 404:
                return false;
            default:
                return true;
        }
    }

        /**
     * Close connection.
     */

    public function close() {
        $client = $this->client;
        $client->close();
    }
}
