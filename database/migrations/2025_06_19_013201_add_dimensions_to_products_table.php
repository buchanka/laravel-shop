<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('height', 8, 1)->nullable()->comment('Height in cm');
            $table->decimal('width', 8, 1)->nullable()->comment('Width in cm');
            $table->decimal('length', 8, 1)->nullable()->comment('Length in cm');
            $table->decimal('burn_time', 8, 1)->nullable()->comment('Burn time in hours');
        });
    }

    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['height', 'width', 'length', 'burn_time']);
        });
    }
};
