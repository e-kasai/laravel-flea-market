<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Item;

class Transaction extends Model
{
    use HasFactory;

    //セキュリティ観点からbuyer_idは除外
    protected $fillable = [
        'item_id',
        'purchase_price',
        'payment_method',
        'shipping_postal_code',
        'shipping_address',
        'shipping_building',
    ];

    protected $casts = [
        'payment_method' => 'integer',
    ];


    public function buyer()
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    // この取引に紐づく全メッセージ
    public function messages()
    {
        return $this->hasMany(TransactionMessage::class);
    }
}
