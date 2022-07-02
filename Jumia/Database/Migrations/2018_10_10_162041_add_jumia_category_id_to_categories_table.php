<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddJumiaCategoryIdToCategoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
      Schema::table('categories', function (Blueprint $table) {
            $table->string('jumia_cat_id')->nullable()->after('created_by');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->string('jumia_product_id')->nullable()->after('created_by');
        });
        Schema::table('brands', function (Blueprint $table) {
            $table->string('jumia_brand')->nullable()->after('created_by');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->string('jumia_order_id')->nullable()->after('created_by');
        }); /* */

        Schema::table('transaction_sell_lines', function (Blueprint $table) {
            $table->string('jumia_line_items_id')->nullable()->after('sell_line_note');
        });/**/
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
    }
}
