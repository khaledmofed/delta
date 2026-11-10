<?php

/*
| Public website settings (shared by the header, mobile menu and footer).
| Mirrors SERVICES in delta-website/_build/layout.py.
*/

return [

    'phone' => '+971 6 574 7420',
    'phone_href' => '+97165747420',
    'email' => 'dis@deltains-brokers.com',
    'location' => 'Sharjah, United Arab Emirates',

    // key => [name, FontAwesome icon, sold online?, short description]
    // Each key has a route named "products.{key}" at /{key}-insurance.
    'services' => [
        'motor' => ['name' => 'Motor Insurance', 'icon' => 'fa-car-side', 'online' => true, 'desc' => 'Compare insurers and buy online in minutes.'],
        'medical' => ['name' => 'Medical Insurance', 'icon' => 'fa-heart-pulse', 'online' => false, 'desc' => 'Cover for individuals, families and employees.'],
        'home' => ['name' => 'Home Insurance', 'icon' => 'fa-house-heart', 'online' => false, 'desc' => 'Protect your home and your belongings.'],
        'travel' => ['name' => 'Travel Insurance', 'icon' => 'fa-plane-departure', 'online' => false, 'desc' => 'Medical and trip cover wherever you go.'],
        'boat' => ['name' => 'Boat Insurance', 'icon' => 'fa-sailboat', 'online' => false, 'desc' => 'Cover for boats, yachts and jet skis.'],
        'pet' => ['name' => 'Pet Insurance', 'icon' => 'fa-paw', 'online' => false, 'desc' => 'Help with vet bills for cats and dogs.'],
    ],

    // Pages not converted to Blade yet (motor journey, accounts, demo screens).
    'links' => [
        'motor_quote' => '/motor-quote',
        'login' => '/login',
        'demo' => '/demo',
        'admin' => '/admin',
        'portal' => '/portal',
    ],

    // The prototype's "Demo controls" panel. Shown only on local by default.
    'demo_panel' => env('SITE_DEMO_PANEL', env('APP_ENV') === 'local'),

];
