<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Comment;
use App\Models\Category;
use App\Models\User;
use Illuminate\Support\Str;

class Item extends Model
{
    use HasFactory;

    protected $fillable = [
        //セキュリティ観点からseller_idは除外
        'item_name',
        'brand_name',
        'price',
        'color',
        'condition',
        'description',
        'image_path',
        'is_sold',
    ];

    protected $casts = [
        'price' => 'integer',
        'condition' => 'integer',
        'is_sold' => 'boolean',
    ];


    public function user()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function transaction()
    {
        return $this->hasOne(Transaction::class);
    }


    public function categories()
    {
        return $this->belongsToMany(Category::class, 'category_item', 'item_id', 'category_id');
    }

    //中間テーブルfavoritesのリレーション
    //usersではメソッド内容がわかりにくい為favoritedByUsersと命名
    public function favoritedByUsers()
    {
        return $this->belongsToMany(User::class, 'favorites', 'item_id', 'user_id');
    }

    //アクセサ
    public function getConditionLabelAttribute(): string
    {
        return match ($this->condition) {
            0 => '状態が悪い',
            1 => 'やや傷や汚れあり',
            2 => '目立った傷や汚れなし',
            3 => '良好',
        };
    }

    //S3(フルURL)ならそのまま返し、ローカルストレージならstorage配下に変換
    public function getImageUrlAttribute(): string
    {
        if (empty($this->image_path)) {
            return asset('img/noimage.png');
        }
        return Str::startsWith($this->image_path, ['http://', 'https://'])
            ? $this->image_path
            : asset('storage/' . $this->image_path);
    }
}
