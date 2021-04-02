<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ConvertTablesIntoInnodb extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        $tables = [
            'users',
            'user_markets',
            'uploads',
            'service_fees',
            'roles',
            'role_has_permissions',
            'products',
            'product_reviews',
            'product_orders',
            'product_order_options',
            'pre_users',
            'permissions',
            'payments',
            'password_resets',
            'orders',
            'order_statuses',
            'options',
            'option_groups',
            'notifications',
            'model_has_roles',
            'model_has_permissions',
            'migrations',
            'media',
            'markets_payouts',
            'markets',
            'market_reviews',
            'market_fields',
            'galleries',
            'fields',
            'favorites',
            'favorite_options',
            'faqs',
            'faq_categories',
            'earnings',
            'drivers_payouts',
            'drivers',
            'driver_markets',
            'delivery_addresses',
            'custom_fields',
            'custom_field_values',
            'currencies',
            'categories',
            'carts',
            'cart_options',
            'app_settings',
        ];
        foreach ($tables as $table) {
            DB::statement('ALTER TABLE ' . $table . ' ENGINE = InnoDB');
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
        // $tables = [
        //     'users',
        //     'user_markets',
        //     'uploads',
        //     'service_fees',
        //     'roles',
        //     'role_has_permissions',
        //     'products',
        //     'product_reviews',
        //     'product_orders',
        //     'product_order_options',
        //     'pre_users',
        //     'permissions',
        //     'payments',
        //     'password_resets',
        //     'orders',
        //     'order_statuses',
        //     'options',
        //     'option_groups',
        //     'notifications',
        //     'model_has_roles',
        //     'model_has_permissions',
        //     'migrations',
        //     'media',
        //     'markets_payouts',
        //     'markets',
        //     'market_reviews',
        //     'market_fields',
        //     'galleries',
        //     'fields',
        //     'favorites',
        //     'favorite_options',
        //     'faqs',
        //     'faq_categories',
        //     'earnings',
        //     'drivers_payouts',
        //     'drivers',
        //     'driver_markets',
        //     'delivery_addresses',
        //     'custom_fields',
        //     'custom_field_values',
        //     'currencies',
        //     'categories',
        //     'carts',
        //     'cart_options',
        //     'app_settings',
        // ];
        // foreach ($tables as $table) {
        //     DB::statement('ALTER TABLE ' . $table . ' ENGINE = MyISAM');
        // }

    }
}
