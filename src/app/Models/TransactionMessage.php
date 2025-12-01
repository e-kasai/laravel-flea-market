<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransactionMessage extends Model
{
    use HasFactory;

    // fillableで操作可能なデータを指定
    protected $fillable = [
        'transaction_id',
        'user_id',
        'message',
    ];

    //メッセージは1件の取引（Transaction）に属する
    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    //メッセージは1人のユーザーに属する
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
