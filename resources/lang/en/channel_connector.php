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
        'balance_currency_not_match' => 'Different currency ' .
            'between balance(:balance_currency_id) and variant(:variant_currency_id) in order(:channel_order_id)',
        'no_api_endpoint' => 'Api-endpoint was not specified',
        'price_set_update' => 'Price-set update error for Vendor(:vendor_id) / Product(:product_id)',
        'inventory_set_update' => 'Inventory-set update error for Vendor(:vendor_id) / Product(:product_id)',
        'no_category_found' => 'Category(:category_id) was not found from package',
        'no_category_id_mapped_exist' => 'No category-id mapped exists for category(:category_id) of product(:product_id)',
        'no_category_in_product' => 'Product(:product_id) does not have default category',
        'no_medium_in_product' => 'Product(:product_id) does not have default medium',
        'no_product_found' => 'Product(:product_id) was not found from package',
        'no_supply_price_history' => 'No supply_price history found for product(:product_id) or variant(:variant_id)',
        'not_enough_balance' => 'Balance (:balance_id) is not enough for order(:order_id)',
        'order_not_dealable' => 'Channel order(:order_id_from_channel) ' .
            'variant.override.id_from_remote(:variant_overrided_id) is not dealable',
        'order_already_saved' => 'Channel order(:order_id_from_channel) has been already saved to cc with(:order_id)',
        'order_validation_failed' => 'Channel order(:order_id_from_channel) validation failed',
        'product_not_connected' => 'Product ID(:product_id_from_channel) from remote does not exist in override',
        'product_creation_error' => 'Failed to create product(:product_id) -> :message',
        'product_update_error' => 'Failed to update product(:product_id) -> :message',
        'product_without_image'  => 'Product(:product_id) does not have image',
        'supplied_price_not_match' => 'Channel order(:order_id_from_channel) has been cancelled' .
            '(history supply_price(:supply_price_history) does not match with ' .
            'current supply_price(:supply_price_current))',
        'variant_not_connected' => 'Variant ID(:variant_id_from_channel) from remote does not exist in override',
        'variant_not_active' => 'Failed to update stock, Variant ID(:variant_id) is not active',
        'wrong_credential_for_channel' => 'Failed to renew token from channel-api with given credential',
    ],
];
