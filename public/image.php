<?php
ini_set('memory_limit', '150M');
require_once 'vendor/autoload.php';

$url = !empty($_GET['url']) ? $_GET['url'] : "";
$default = !empty($_GET['default']) ? $_GET['default'] : false;
unset($_GET['url']);

$defaultUrl = "default.png";

if (empty($url)) {
    if ($default) {
        $url = $defaultUrl;
    } else {
        header("HTTP/1.0 404 Image not found");
        http_response_code(404);
        exit;
    }
}

use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use League\Glide\ServerFactory;
use Symfony\Component\HttpFoundation;

try {
    // Set source filesystem
    $source = new League\Flysystem\Filesystem(
            new League\Flysystem\Adapter\Local(__DIR__ . '/uploads')
    );

// Set cache filesystem
    $cache = new League\Flysystem\Filesystem(
            new League\Flysystem\Adapter\Local(__DIR__."/cache")
    );

// Set image manager
    $imageManager = new Intervention\Image\ImageManager([
        'driver' => 'gd',
    ]);

// Set manipulators
    $manipulators = [
        new League\Glide\Manipulators\Orientation(),
        new League\Glide\Manipulators\Crop(),
        new League\Glide\Manipulators\Size(),
        new League\Glide\Manipulators\Brightness(),
        new League\Glide\Manipulators\Contrast(),
        new League\Glide\Manipulators\Gamma(),
        new League\Glide\Manipulators\Sharpen(),
        new League\Glide\Manipulators\Filter(),
        new League\Glide\Manipulators\Blur(),
        new League\Glide\Manipulators\Pixelate(),
        new League\Glide\Manipulators\Background(),
        new League\Glide\Manipulators\Border(),
        new League\Glide\Manipulators\Encode(),
    ];
//echo "in";
// Set API
    $api = new League\Glide\Api\Api($imageManager, $manipulators);
    //print_r($api);exit;
// Setup Glide server
    $server = new League\Glide\Server(
            $source, $cache, $api
    );

    try {
        $server->outputImage($url, $_GET);
        $server->deleteCache($url);
    } catch (Exception $ex) {
        if ($default) {
            $url = $defaultUrl;
            $server->outputImage($url, $_GET);
            $server->deleteCache($url);
        } else {
            header("HTTP/1.0 404 " . $ex->getMessage());
            http_response_code(404);
            exit;
        }
    }

// Set response factory
    //$server->setResponseFactory(new SymfonyResponseFactory());
} catch (Exception $ex) {
    header("HTTP/1.0 404 " . $ex->getMessage());
    http_response_code(404);
    exit;
}

