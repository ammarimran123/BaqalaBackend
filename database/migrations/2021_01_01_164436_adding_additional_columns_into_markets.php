<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddingAdditionalColumnsIntoMarkets extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('markets', function (Blueprint $table) {
            //
            $table->string('delivery_time',24)->default('0');
            $table->tinyInteger('special_offer')->default('0');
            $table->string('start_time',50)->default('09:00');
            $table->string('end_time',50)->default('17:00');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('markets', function (Blueprint $table) {
            //
            $table->dropColumn('deliver_time');
            $table->dropColumn('special_offer');
            $table->dropColumn('start_time');
            $table->dropColumn('end_time');
        });
    }
}
