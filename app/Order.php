<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $table = 'tbl_orders';
    protected $guarded = [];

    public function customer()
    {
        return $this->belongsTo('App\Customer', 'customer_id');
    }

    public function user()
    {
        return $this->belongsTo('App\User', 'user_id');
    }

    public function items()
    {
        return $this->hasMany('App\OrderDetail', 'order_id');
    }

    // alias, dipake di view lama
    public function details()
    {
        return $this->hasMany('App\OrderDetail', 'order_id');
    }
}
