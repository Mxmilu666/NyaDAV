<div align="center">

![NyaDAV](https://socialify.git.ci/Mxmilu666/NyaDAV/image?description=1&forks=1&issues=1&language=1&name=1&owner=1&pulls=1&stargazers=1&theme=Auto)

# 🐱 NyaDAV

✨ The simple WebDAV client for PHP and Swoole ✨

</div>

## Features

- 📄 Get file list
- 📏 Get file size
- 📥 Download files
- 📤 Upload files
- ❌ Delete files

## Requirements

- PHP 8.0+
- Swoole 5.1.0+

## Installation

You can install NyaDAV via Composer:

```sh
composer require mxmilu666/nyadav
```

# Usage

## Initialize the Client

```php
use NyaDAV\NyaDAV;
use NyaDAV\NyaDAVException;

try {
    $dav = new NyaDAV('example.com', 80, false); // Set true for SSL
} catch (NyaDAVException $e) {
    echo $dav->err;
    //or
    echo 'Error: ' . $e->getMessage();
}
```

## Set Client Settings
```php
$dav->set([
    'auth' => [
        'username' => 'your-username',
        'password' => 'your-password'
    ],
    'depth' => 1
]);
```

## Get File List
```php
try {
    $files = $dav->getfilelist('/remote.php/webdav/');
    print_r($files);
} catch (NyaDAVException $e) {
    echo $dav->err;
    //or
    echo 'Error: ' . $e->getMessage();
}

```

## Get File Size
```php
$size = $dav->getfilesize('/remote.php/webdav/test.txt');
echo 'File Size: ' . $size;

```

## Download File
Local download:
```php
$fileInfo = $dav->getfile('/remote.php/webdav/test.txt', 'local_test.txt');
print_r($fileInfo);

```
Cloud download (302):
```php
$fileInfo = $dav->getfile('/remote.php/webdav/test.txt');
print_r($fileInfo);

```

## Upload File
```php
try {
    $success = $dav->uploadfile('/remote.php/webdav/uploaded.txt', 'local_upload.txt');
    if ($success) {
        echo 'File uploaded successfully!';
    }
} catch (NyaDAVException $e) {
    echo $dav->err;
    //or
    echo 'Error: ' . $e->getMessage();
}

```

## Delete File
```php
try {
    $success = $dav->deletefile('/remote.php/webdav/uploaded.txt');
    if ($success) {
        echo 'File deleted successfully!';
    }
} catch (NyaDAVException $e) {
    echo $dav->err;
    //or
    echo 'Error: ' . $e->getMessage();
}

```

## File Exists
```php
try {
    $success = $dav->file_exists('/remote.php/webdav/test.txt');
    if ($success) {
        echo 'exists';
    }
    else{
        echo 'not exist';
    }
} catch (NyaDAVException $e) {
    echo $dav->err;
    //or
    echo 'Error: ' . $e->getMessage();
}

```

# Contributing

Feel free to contribute by opening issues or submitting pull requests. We welcome all contributions! 🌟

# License

This project is licensed under the `Apache-2.0` License.