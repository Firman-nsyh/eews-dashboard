<?php

return [
    'url'    => env('INFLUXDB_URL',    'http://192.168.56.4:8086'),
    'token'  => env('INFLUXDB_TOKEN',  'tVsO9p9EgGe2_R96cBeIy89z3HBowOOi0NqADf3RY4MeIiKtEeXpwOEsDisCuUjCwzyIz9qMLayQXiGgsmqxjg=='),
    'org'    => env('INFLUXDB_ORG',    'kelompok_4'),
    'bucket' => env('INFLUXDB_BUCKET', 'deteksi_gempa'),
];