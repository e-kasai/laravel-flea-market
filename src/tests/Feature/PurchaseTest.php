<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Item;
use App\Models\Profile;
use App\Models\Transaction;
use App\Models\Rating;

class PurchaseTest extends TestCase
{
    use RefreshDatabase;

    // 購入完了テスト
    public function test_purchase_completes_and_transaction_data_stored_to_the_database()
    {
        // 出品者・購入者をファクトリで作成
        $seller = User::factory()->create();
        $buyer  = User::factory()->create();

        // 商品を作成
        $item = Item::factory()->create([
            'seller_id' => $seller->id,
            'image_path' => 'https://example.com/test.jpg',
            'is_sold'   => false,
        ]);

        // 購入者プロフィール
        Profile::factory()->create([
            'user_id'     => $buyer->id,
            'postal_code' => '123-4567',
            'address'     => '東京都テスト区1-2-3',
        ]);

        // loginユーザーとして購入
        $this->actingAs($buyer);
        $response = $this->post(route('purchase.item', $item), [
            'payment_method' => 1,
            'purchase_price' => $item->price,
            'shipping_postal_code' => '123-4567',
            'shipping_address' => '東京都テスト区1-2-3',
        ]);


        // 期待：購入完了 DBに購入レコードが追加される
        $this->assertDatabaseHas('transactions', [
            'item_id'  => $item->id,
            'buyer_id' => $buyer->id,
            'payment_method' => 1,
            'purchase_price' => $item->price,
            'shipping_postal_code' => '123-4567',
            'shipping_address' => '東京都テスト区1-2-3',
        ]);
    }

    //商品一覧ページで購入した商品にsoldが表示されるかのテスト
    public function test_purchased_item_indicate_sold_in_top_page()
    {
        $seller = User::factory()->create();
        $buyer  = User::factory()->create();

        $item = Item::factory()->create([
            'seller_id'  => $seller->id,
            'image_path' => 'https://example.com/test.jpg',
            'is_sold'    => false,
            'price'      => 1200,
            'item_name'  => 'テスト商品',
        ]);

        Profile::factory()->create([
            'user_id'     => $buyer->id,
            'postal_code' => '123-4567',
            'address'     => '東京都テスト区1-2-3',
        ]);

        // ログインユーザーとして購入処理を実行
        $this->actingAs($buyer);
        $this->post(route('purchase.item', $item), [
            'payment_method'       => 1,
            'purchase_price'       => $item->price,
            'shipping_postal_code' => '123-4567',
            'shipping_address'     => '東京都テスト区1-2-3',
        ]);

        // 一覧ページを表示
        $response = $this->actingAs($buyer)->get(route('items.index'));

        // 期待: 購入した商品にSOLD表示がある
        $response->assertSee('テスト商品');
        $response->assertSeeText('SOLD');
    }


    // マイページの購入した商品一覧に購入した商品が表示されるかのテスト
    public function test_purchased_item_appears_in_profile_purchased_list()
    {
        $seller = User::factory()->create();
        $buyer  = User::factory()->create();

        $item = Item::factory()->create([
            'seller_id'  => $seller->id,
            'item_name'  => 'HDD',
            'price'      => 1200,
            'is_sold'    => false,
            'image_path' => 'https://example.com/img.jpg',
        ]);

        Profile::factory()->create([
            'user_id'     => $buyer->id,
            'postal_code' => '123-4567',
            'address'     => '東京都テスト区1-2-3',
        ]);

        // ログインユーザーとして購入処理を実行
        $this->actingAs($buyer);
        $this->post(route('purchase.item', $item), [
            'payment_method'       => 1,
            'purchase_price'       => $item->price,
            'shipping_postal_code' => '123-4567',
            'shipping_address'     => '東京都テスト区1-2-3',
        ]);

        // 双方の評価が完了した前提
        $transaction = Transaction::first();
        $this->post(route('transactions.complete', $transaction));
        Rating::create([
            'transaction_id' => $transaction->id,
            'from_user_id' => $buyer->id,
            'to_user_id' => $seller->id,
            'score' => 3
        ]);

        Rating::create([
            'transaction_id' => $transaction->id,
            'from_user_id' => $seller->id,
            'to_user_id' => $buyer->id,
            'score' => 3
        ]);

        $transaction->update([
            'status' => Transaction::STATUS_COMPLETED
        ]);


        // プロフィールの購入した商品一覧タブを開く
        $response = $this->actingAs($buyer)->get('/mypage?page=buy');

        // 期待: 購入した商品名が購入した標品一覧ページに追加されている
        $response->assertSee('HDD');
    }
}
