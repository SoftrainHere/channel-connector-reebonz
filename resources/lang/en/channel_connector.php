<?php declare(strict_types = 1);

return [
    'title' => 'Reebonz',
    'api' => [
        'get' => [
            'brands'  => '/product/brands.json',
            'categories' => '/product/categories.json',
            'ordered_item' => '/order/ordered_items/:ordered_item_id.json',
            'ordered_items' => '/order/ordered_items.json',
            'product_meta_info' => '/product/product_meta_info.json',
            'product' => '/product/products/:product_id.json',
            'products' => '/product/products.json',
            'code_duplicated' => '/product/products/code_duplicated.json',
            'templates' => '/product/templates.json'
        ],
        'post' => [
            'deliveries' => '/order/deliveries/create_or_update.json',
            'partner_confirm_complete' => '/order/ordered_items/:ordered_item_id/partner_confirm_complete.json',
            'request_cancel' => '/order/ordered_items/:ordered_item_id/request_cancel.json',
            'products' => '/product/products.json',
            'token' => '/api/token'
        ],
        'put' => [
            'products' => '/product/products/:product_id.json',
            'stock_update' => '/product/products/:product_id/stock_update.json'
        ]
    ],

    'errors' => [
        'no_api_endpoint' => 'Api-endpoint was not specified',
        'price_set_update' => 'Price-set update error for Vendor(:vendor_id) / Product(:product_id)',
        'inventory_set_update' => 'Inventory-set update error for Vendor(:vendor_id) / Product(:product_id)',
        'no_category_found' => 'Category(:category_id) was not found from package',
        'no_category_id_mapped_exist' => 'No category-id mapped exists for category(:category_id) of product(:product_id)',
        'no_category_in_product' => 'Product(:product_id) does not have default category',
        'no_medium_in_product' => 'Product(:product_id) does not have default medium',
        'no_product_found' => 'Product(:product_id) was not found from package',
        'wrong_credential_for_channel' => 'Failed to renew token from channel-api with given credential',
    ],
];
