<?php

defined('ABSPATH') || exit;

function ces_get_main_categories() {
    return get_terms([
        'taxonomy'   => 'product_cat',
        'hide_empty' => false,
        'parent'     => 0,
    ]);
}
