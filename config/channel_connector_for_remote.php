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
    'channel_connector_identifier' => env('CHANNEL_CONNECTOR_IDENTIFIER', 'api_mxn'),
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
    ],

    'order_status' => [
        ['name' => '주문완료', 'code' => 'completed'],
        ['name' => '파트너 확인', 'code' => 'partner_confirm'],
        ['name' => '주문취소(배송전)', 'code' => 'canceled'],
        ['name' => '교환완료', 'code' => 'changed'],
        ['name' => '반품완료', 'code' => 'refunded'],
        ['name' => '교환신청', 'code' => 'request_change'],
        ['name' => '반품신청', 'code' => 'request_refund'],
        ['name' => '주문실패', 'code' => 'system_canceled'],
        ['name' => 'TO리본즈배송', 'code' => 'to_reebonz_delivery']
    ],

    'delivery_status' =>  [
        ['name' => '배송준비중', 'code' => 'ready'],
        ['name' => '배송중', 'code' => 'ing'],
        ['name' => 'TO리본즈배송', 'code' => 'complete']
    ],

    'vintage_status' =>  [
        ['unused' => '깨끗하게 보존된 새 상품'],
        ['perfect' => '새 상품과 비슷한 수준의 상품'],
        ['excellent' => '사용감이 있지만 대체적으로 깨끗한 상품'],
        ['very-Good' => '손상부분 없이 손때를 탄 상품'],
        ['good' => '약한 스크래치, 탈색, 오염이 있는 상품'],
        ['normal' => '눈에 띄는 스크래치, 탈색, 오염이 있는 상품']
    ],
    'img_src' => env('IMG_SRC','/images/default_image_large.jpg'),
    'local_currency_code' => env('CHANNEL_CURRENCY_CODE','JPY'),
    'maximum_variants' => env('MAXIMUM_VARIANTS',30),
];
