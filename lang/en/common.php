<?php

return [
    /**
     * COMMON TRANSLATIONS
     */
    'error' => [
        '500' => 'Internal Server Error',
        'token' => 'Email/username or password not matched.',
        'token_not_parsed' => 'Token was not included in your request. Parsing failed.',
        'token_expired' => 'Token has expired. Request for a new token.',
        'token_invalid' => 'Token was invalid. Parsing failed.',
        'product_limit_reached' => 'Maximum product limit has been reached. You cannot create a new product.',
        'check_quota' => 'Checking quota failed. Type :type is not valid.',
        'not_found' => 'Route :route was not found.',
        'wrong_credentials' => "Wrong credentials given. Cannot generate new access token.",
        's13_null_data' => 'No data found for year :year.',
        's13_upload_failed' => 'Cannot generate a download link.',
        'no_access' => 'You have no access to do this action.'
    ],
    'message' => [
        'product_toggle' => 'Product :productid has been :action.',
        'product_update' => 'Product :productid has been updated.',
        'product_unit_empty' => 'Empty product unit. Please create a new one first.',
        'product_tag_empty' => 'Empty product tag. Please create a new one first.',
        'product_category_empty' => 'Empty product category. Please create a new one first.',
        'logged_out' => 'You are logged out.'
    ],
    'state' => [
        'disabled' => 'disabled',
        'enabled' => 'enabled',
        'success' => 'Success',
        'failed' => 'Failed'
    ]
];
