<?php

return [

    /**
     * Credentials for the web service of Internetmarke
     *
     * To get access you have to fill out their registration form and send it to pcf-1click@deutschepost.de
     * You can find the registration form here: https://www.deutschepost.de/de/i/internetmarke-porto-drucken/downloads.html
     */
    'partner_id' => env('INTERNETMARKE_PARTNER_ID'),
    'secret_key' => env('INTERNETMARKE_SECRET_KEY'),
    'key_phase' => env('INTERNETMARKE_KEY_PHASE', 1),

    /**
     * Credentials for the web service of ProdWS (the separate service to get product information / updates)
     *
     * To get access you have to fill out their registration form (see above) and check that you want to use ProdWS
     */
    'prodws' => [

        'mandant_id' => env('PRODWS_MANDANT_ID'),
        'username' => env('PRODWS_USERNAME'),
        'password' => env('PRODWS_PASSWORD'),

    ]
];
