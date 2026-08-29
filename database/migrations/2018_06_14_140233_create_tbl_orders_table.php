<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTblOrdersTable extends Migration
{
    public function up()
    {
        Schema::create('tbl_orders', function (Blueprint $table) {
            $table->increments('id');
            $table->string('no_order', 30);
            $table->integer('customer_id');
            $table->integer('user_id');
            $table->dateTime('tgl_order');
            $table->string('status', 20)->default('baru'); // baru, proses, selesai, batal, deleted
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('diskon_persen', 5, 2)->default(0);
            $table->decimal('diskon', 15, 2)->default(0);
            $table->decimal('ppn', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            $table->text('catatan')->nullable();
            $table->string('invoice_file')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('tbl_orders');
    }
}
