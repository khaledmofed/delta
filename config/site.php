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

    // Lead forms (product pages + contact). New leads are emailed to this team inbox.
    'leads_email' => env('LEADS_NOTIFY_EMAIL', 'dis@deltains-brokers.com'),
    // Internal alerts from the motor journey (new policy, payment failed, insurer refused checkout).
    'ops_email' => env('OPS_NOTIFY_EMAIL', env('LEADS_NOTIFY_EMAIL', 'dis@deltains-brokers.com')),
    // Claims & Support page (/claims). Help requests are saved as leads with product "claims".
    'claims' => [
        'request_types' => [
            'new_claim' => 'Report a new claim',
            'claim_followup' => 'Follow up on a claim',
            'policy_change' => 'Change my policy',
            'renewal' => 'Renewal',
            'documents' => 'Policy documents',
            'other' => 'Something else',
        ],
        // Official UAE emergency numbers.
        'emergency' => [
            ['999', 'Police', 'fa-shield-halved'],
            ['998', 'Ambulance', 'fa-truck-medical'],
            ['997', 'Civil Defence', 'fa-fire-extinguisher'],
        ],
    ],
    'emirates' => ['Sharjah', 'Dubai', 'Abu Dhabi', 'Ajman', 'Umm Al Quwain', 'Ras Al Khaimah', 'Fujairah'],
    'contact_prefs' => ['Phone call', 'WhatsApp', 'Email'],

    // Pages not converted to Blade yet (motor journey, accounts, demo screens).
    'links' => [
        'motor_quote' => '/motor-quote',
        // No customer accounts yet: "Sign in" / "My Account" / "Customer portal" open "Track my order".
        'login' => '/track',
        'demo' => '/demo',
        'admin' => '/admin',
        'portal' => '/portal',
    ],

    // Demo mode (local only by default): shows the prototype's "Demo controls" panel,
    // prefills the motor wizard with a test customer and allows "Use sample files"
    // (a placeholder image is sent instead of a real document). Never enable in production.
    'demo' => (bool) env('SITE_DEMO', env('APP_ENV') === 'local'),

];
