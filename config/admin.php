<?php

/*
| Back-office roles and permissions. Same roles and matrix as the prototype
| (delta-website/assets/js/delta-data.js: ROLES, ROLE_MATRIX), limited to the
| permissions the admin panel uses today. users.role holds the role key.
*/

$all = [
    'quotes.view', 'orders.view', 'providers.view', 'providers.update', 'leads.view', 'leads.update', 'api_logs.view',
];

return [

    'roles' => [
        'super_admin' => ['name' => 'Super Admin', 'permissions' => $all],
        'operations' => ['name' => 'Operations Manager', 'permissions' => ['providers.view', 'quotes.view', 'orders.view', 'leads.view', 'leads.update', 'api_logs.view']],
        'insurance' => ['name' => 'Insurance Manager', 'permissions' => ['providers.view', 'providers.update', 'quotes.view', 'orders.view', 'api_logs.view']],
        'finance' => ['name' => 'Finance', 'permissions' => ['orders.view']],
        'support' => ['name' => 'Customer Support', 'permissions' => ['quotes.view', 'orders.view', 'leads.view', 'leads.update']],
        'compliance' => ['name' => 'Compliance', 'permissions' => ['orders.view']],
        'tech' => ['name' => 'Technical Admin', 'permissions' => ['providers.view', 'providers.update', 'api_logs.view']],
        'readonly' => ['name' => 'Read Only', 'permissions' => ['providers.view', 'quotes.view', 'orders.view']],
    ],

    'permissions' => $all,

    // Lead pipeline (prototype: New, Contacted, Quoted, Won, Lost).
    'lead_statuses' => ['new' => 'New', 'contacted' => 'Contacted', 'quoted' => 'Quoted', 'won' => 'Won', 'lost' => 'Lost'],

    'per_page' => 25,

];
