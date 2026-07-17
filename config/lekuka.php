<?php

return [

    'base_url' => env('LEKUKA_BASE_URL'),

    'device_model_name' => env('LEKUKA_DEVICE_MODEL_NAME'),

    'device_model_version' => env('LEKUKA_DEVICE_MODEL_VERSION'),

    'certificate' => env('LEKUKA_CERTIFICATE'),

    'private_key' => env('LEKUKA_PRIVATE_KEY'),

    'ca_certificate' => env('LEKUKA_CA_CERTIFICATE'),

    'timeout' => env('LEKUKA_TIMEOUT', 30),

];