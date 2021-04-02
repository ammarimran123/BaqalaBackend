<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddingAdditionalColumnsIntoServiceFees extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('service_fees', function (Blueprint $table) {
            //
            $table->double('service_fees',);
            $table->integer('applicable')->nullable()->unsigned();

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('service_fees', function (Blueprint $table) {
            //
            $table->dropColumn('service_fees');
            $table->dropColumn('applicable');
        });
    }
}
