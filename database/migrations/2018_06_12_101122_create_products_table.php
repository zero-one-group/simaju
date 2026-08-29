<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProductsTable extends Migration
{
    public function up()
    {
        Schema::create('products', function (Blueprint $table) {
            $table->increments('id');
            $table->string('kode_barang', 30);
            $table->string('nama_barang');
            $table->string('kategori', 50)->nullable();
            $table->string('satuan', 20)->default('pcs');
            $table->decimal('harga_beli', 15, 2)->default(0);
            $table->decimal('harga_jual', 15, 2)->default(0);
            $table->integer('stok')->default(0);
            $table->integer('stok_minimum')->default(10);
            $table->string('status', 20)->default('aktif');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('products');
    }
}
