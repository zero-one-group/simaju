<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTblLogStokTable extends Migration
{
    public function up()
    {
        Schema::create('tbl_log_stok', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('product_id');
            $table->integer('order_id')->nullable();
            $table->integer('qty');
            $table->string('tipe', 10); // in / out
            $table->string('keterangan')->nullable();
            $table->integer('user_id')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('tbl_log_stok');
    }
}
