<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('reserved_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->string('session_id')->nullable(); 
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade'); 
            $table->integer('quantity')->unsigned()->default(1);
            $table->timestamp('expires_at')->nullable(); 
            $table->timestamps();

            // Индексы для быстрого поиска
            $table->index(['product_id', 'session_id']);
            $table->index(['product_id', 'user_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('reserved_stocks');
    }
};
