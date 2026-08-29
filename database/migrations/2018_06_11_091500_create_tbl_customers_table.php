<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTblCustomersTable extends Migration
{
    public function up()
    {
        Schema::create('tbl_customers', function (Blueprint $table) {
            $table->increments('id');
            $table->string('kode', 20);
            $table->string('nama');
            $table->string('tipe', 20)->default('retail'); // retail / grosir
            $table->text('alamat')->nullable();
            $table->string('kota', 100)->nullable();
            $table->string('telp', 30)->nullable();
            $table->string('email')->nullable();
            $table->string('npwp', 30)->nullable();
            $table->string('status', 20)->default('aktif');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('tbl_customers');
    }
}
