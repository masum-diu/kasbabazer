<?php

return [
    // Where sellers send subscription payments (manual bKash/Nagad "Send Money"), confirmed by an admin.
    'bkash_number' => env('BILLING_BKASH_NUMBER', '01XXXXXXXXX'),
    'nagad_number' => env('BILLING_NAGAD_NUMBER'),

    // A confirmed subscription lasts this many days.
    'period_days' => 30,

    // A boost keeps a listing at the top of the feed for this many hours.
    'boost_hours' => 24,
];
