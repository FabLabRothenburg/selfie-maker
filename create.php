<?php

if (empty($_POST['image_id'])) {
    die('image_id missing');
}

$data = [
    'title' => $_POST['description'],
    'featured_media' => $_POST['image_id'],
    'status' => 'publish',
];


$curl = curl_init(\sprintf('%s/wp-json/wp/v2/posts', getenv('WP_URL')));

curl_setopt($curl, CURLOPT_POST, 1);
curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_HTTPHEADER, [
    \sprintf('Authorization: Basic %s', base64_encode(getenv('WP_USERPASS'))),
    'Content-type: application/json',
]);

echo curl_exec($curl);
