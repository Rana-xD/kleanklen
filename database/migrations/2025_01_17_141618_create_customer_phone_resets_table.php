<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('customer_phone_resets', function (Blueprint $table) {
            $table->id();
            $table->string('phone')->index();
            $table->string('token');
            $table->string('otp_code', 6)->nullable();
            $table->timestamp('expires_at');
            $table->timestamps();
            
            $table->index(['phone', 'token']);
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('customer_phone_resets');
    }
};
