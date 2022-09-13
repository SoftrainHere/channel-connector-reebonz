<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Reebonz
    |--------------------------------------------------------------------------
    |
    */
    'api_root' => env('CHANNEL_API_ROOT', 'http://dev.reebonz.co.kr:3007'),
    'api_market_root' => env(
        'CHANNEL_API_MARKETPLACE_ROOT',
        env('CHANNEL_API_ROOT', 'http://dev.reebonz.co.kr:3007') . '/api/marketplace'
    ),
    'login_credential' => [
        "grant_type" => env('CHANNEL_GRANT_TYPE', "password"),
        "username" => env('CHANNEL_USERNAME'),
        "password" => env('CHANNEL_PASSWORD')
    ],
    'commission' => env('CHANNEL_COMMISSION', 1),

    'logistics' => [
        [ 'name' => 'CJ GLS', 'code' => 1 ],
        [ 'name' => '우체국택배', 'code' => 2 ],
        [ 'name' => '우체국EMS', 'code' => 3 ],
        [ 'name' => 'FedEx', 'code' => 4 ],
        [ 'name' => '로젠택배', 'code' => 5 ],
        [ 'name' => '대한통운', 'code' => 6 ],
        [ 'name' => '한진택배', 'code' => 7 ],
        [ 'name' => '롯데택배', 'code' => 8 ],
        [ 'name' => 'DHL', 'code' => 9 ],
        [ 'name' => '동부익스프레스', 'code' => 10 ],
        [ 'name' => 'KGB택배', 'code' => 11 ],
        [ 'name' => '대신택배', 'code' => 12 ],
        [ 'name' => 'UPS', 'code' => 13 ],
        [ 'name' => 'KG옐로우캡택배', 'code' => 14 ],
        [ 'name' => '경동택배', 'code' => 15 ],
        [ 'name' => '대한통운(국제택배)', 'code' => 16 ],
        [ 'name' => '드림택배(구 KG로지스)', 'code' => 17 ],
        [ 'name' => 'TNT', 'code' => 20 ],
        [ 'name' => 'USPS', 'code' => 21 ],
        [ 'name' => '일양로지스', 'code' => 22 ],
        [ 'name' => 'GSMNTON', 'code' => 24 ],
        [ 'name' => 'DGF', 'code' => 25 ]
    ]
];
