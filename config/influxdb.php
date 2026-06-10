<?php

return [
    'url'    => env('INFLUXDB_URL',    'http://192.168.56.4:8086'),
    'token'  => env('INFLUXDB_TOKEN',  ''),
    'org'    => env('INFLUXDB_ORG',    ''),
    'bucket' => env('INFLUXDB_BUCKET', 'deteksi_gempa'),
];