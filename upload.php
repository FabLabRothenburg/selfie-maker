<?php

$outputData = [];

/*$_FILES = [[
    'name' => 'IMG_20180922_153159462.jpg',
    'type' => 'image/jpeg',
    'tmp_name' => '/tmp/foo.jpg',
    'error' => 0,
    'size' => filesize('/tmp/foo.jpg'),
]];*/

foreach ($_FILES as $file) {
    if ($file['error'] != 0) {
        continue;
    }

    $curl = curl_init('https://westeurope.api.cognitive.microsoft.com/face/v1.0/detect');
    curl_setopt($curl, CURLOPT_POST, 1);
    curl_setopt($curl, CURLOPT_POSTFIELDS, file_get_contents($file['tmp_name']));
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HTTPHEADER, [
        \sprintf('Ocp-Apim-Subscription-Key: %s', getenv('FACES_SUBSCRIPTION_KEY')),
        'Content-type: application/octet-stream',
        \sprintf('Content-length: %d', $file['size']),
    ]);

    $result = curl_exec($curl);
    $faces = json_decode($result);
    #print_r($faces);

    if (!is_array($faces)) {
        echo "request to azure faces api failed :-(";
        die;
    }

    $cmd = \sprintf('convert %s -auto-orient', escapeshellarg($file['tmp_name']));

    foreach ($faces as $face) {
        $sizeMax = max($face->faceRectangle->width, $face->faceRectangle->height);
        $cmd .= \sprintf(
            ' \( -background none smiley.png -resize %dx%d -repage +%d+%d \)',
            $sizeMax, $sizeMax,
            $face->faceRectangle->left,
            $face->faceRectangle->top
        );
    }

    $tmpfile = tempnam(sys_get_temp_dir(), 'smilified');
    $cmd .= \sprintf(' -flatten %s', escapeshellarg($tmpfile));
    # echo "shell --> $cmd \n";
    shell_exec($cmd);

    $curl = curl_init(\sprintf('%s/wp-json/wp/v2/media', getenv('WP_URL')));
    curl_setopt($curl, CURLOPT_POST, 1);
    curl_setopt($curl, CURLOPT_POSTFIELDS, file_get_contents($tmpfile));
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HTTPHEADER, [
        \sprintf('Authorization: Basic %s', base64_encode(getenv('WP_USERPASS'))),
        \sprintf('Content-disposition: attachment; filename="%s"', $file['name'])
    ]);

    $result = curl_exec($curl);
    $mediadata = json_decode($result);

    if (!is_object($mediadata)) {
        echo "failed to upload media ...";
        die;
    }

    if (isset($mediadata->media_details->sizes->medium_large)) {
        $medium = $mediadata->media_details->sizes->medium_large;
    } else {
        $medium = $mediadata->media_details->sizes->full;
    }

    $outputData[] = [ 'source_url' => $medium->source_url, 'id' => $mediadata->id ];

    unlink($tmpfile);
}

header('Content-type: application/json');
echo json_encode($outputData);
