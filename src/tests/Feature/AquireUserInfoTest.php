<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Item;
use App\Models\User;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AquireUserInfoTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_shows_avatar_username_sold_and_purchased_lists()
    {
        $user = User::factory()->create([
            'name' => 'テストユーザー',
            'email_verified_at' => now(),
        ]);

        $user->profile()->create([
            'postal_code' => '100-0001',
            'address'     => 'テスト区1-2-3',
            'building'    => 'タワー101',
            'avatar_path' => 'avatars/sample.png',
        ]);

        // 出品した商品
        $myItem = Item::factory()->create([
            'seller_id'  => $user->id,
            'item_name'  => '出品した商品',
            'image_path' => 'items/a.jpg',
            'is_sold'    => false,
        ]);

        $seller = User::factory()->create();
        $purchasedItem = Item::factory()->create([
            'seller_id'  => $seller->id,
            'item_name'  => '購入した商品',
            'image_path' => 'items/b.jpg',
            'is_sold'    => true,
        ]);

        $transaction = new Transaction();
        $transaction->item_id              = $purchasedItem->id;
        $transaction->buyer_id             = $user->id;
        $transaction->seller_id            = $purchasedItem->seller_id;
        $transaction->status               = 1;
        $transaction->payment_method       = 1;
        $transaction->purchase_price       = 1000;
        $transaction->shipping_postal_code = $user->profile->postal_code;
        $transaction->shipping_address     = $user->profile->address;
        $transaction->shipping_building    = $user->profile->building;
        $transaction->save();

        $response = $this->actingAs($user)->get(route('profile.show'));

        $response->assertSeeText('テストユーザー');
        $response->assertSee('img');
        $response->assertSee('avatars/sample.png');

        // 出品一覧に自分の出品がある
        $sell = $this->actingAs($user)->get('/mypage?page=sell');
        $sell->assertSeeText('出品した商品');

        // 購入一覧に購入済した商品がある
        $buy = $this->actingAs($user)->get('/mypage?page=buy');
        $buy->assertSeeText('購入した商品');
    }
}
