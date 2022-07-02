<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;

class AddJumiaPermissions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Permission::create(['name' => 'jumia.syc_categories']);
        Permission::create(['name' => 'jumia.sync_products']);
        Permission::create(['name' => 'jumia.sync_orders']);
      
        Permission::create(['name' => 'jumia.access_jumia_api_settings']);
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
