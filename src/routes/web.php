<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\ExhibitController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\RatingController;
use App\Http\Controllers\TransactionMessageController;
use App\Http\Controllers\TransactionCompleteController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;

//商品一覧画面表示
Route::get('/', [ItemController::class, 'index'])->name('items.index');

//ユーザー登録
Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register.show');
    Route::post('/register', [RegisterController::class, 'store'])->name('register.store');
});

//プロフィール関連
Route::prefix('mypage')
    ->middleware(['auth', 'verified'])
    ->group(function () {
        Route::get('/', [ProfileController::class, 'showProfilePage'])->name('profile.show');
        Route::get('/profile', [ProfileController::class, 'showProfileEditPage'])->name('profile.edit');
        Route::patch('/profile', [ProfileController::class, 'updateProfile'])->name('profile.update');
    });


//出品
Route::prefix('sell')
    ->middleware(['auth', 'verified'])
    ->group(function () {
        Route::get('/', [ExhibitController::class, 'showExhibitForm'])->name('exhibit.show');
        Route::post('/', [ExhibitController::class, 'storeExhibitItem'])->name('exhibit.store');
    });


//商品詳細
Route::get('/item/{item}', [ItemController::class, 'showItemDetail'])
    ->name('details.show');


//コメント機能
Route::prefix('item')
    ->middleware(['auth', 'verified'])
    ->group(function () {
        Route::post('/{item}/comment', [CommentController::class, 'storeComment'])
            ->name('comments.store');
    });

//いいね機能
Route::prefix('item')
    ->group(function () {
        Route::post('/{item}/favorite', [FavoriteController::class, 'setItemFavorite'])
            ->name('favorite.store');
        Route::delete('/{item}/favorite', [FavoriteController::class, 'setItemUnfavorite'])
            ->name('favorite.destroy');
    });


//stripe決済画面へ遷移
Route::post('/checkout/{item}', [PurchaseController::class, 'startPayment'])
    ->name('stripe.checkout.create')
    ->middleware(['auth', 'verified']);


//購入
Route::prefix('purchase')->middleware(['auth', 'verified'])->group(function () {
    Route::get('/complete', [PurchaseController::class, 'finalizeTransaction'])->name('purchase.complete');
    Route::get('/{item}', [PurchaseController::class, 'showPurchasePage'])->name('purchase.show');
    Route::post('/{item}', [PurchaseController::class, 'purchaseItem'])->name('purchase.item');

    // 配送先
    Route::get('/address/{item}', [PurchaseController::class, 'showShippingAddress'])->name('address.show');
    Route::patch('/address/{item}', [PurchaseController::class, 'updateShippingAddress'])->name('address.update');
});


//メール認証案内ページ
Route::get('/email/verify', function () {
    return view('auth.verify_email');
})->middleware('auth')->name('verification.notice');


//メール認証リンクアクセス
Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
    $request->fulfill();
    return redirect()->route('items.index');
})->middleware(['auth', 'signed', 'throttle:6,1'])->name('verification.verify');


//認証メール再送
Route::post('/email/verification-notification', function (Request $request) {
    $request->user()->sendEmailVerificationNotification();
    return back()->with('message', '認証メールを再送しました。');
})->middleware(['auth', 'throttle:6,1'])->name('verification.send');


//取引メッセージ（Message）まわり
Route::prefix('transactions/{transaction}/messages')->middleware(['auth', 'verified'])->group(function () {

    // チャット画面（FN002）
    Route::get('/', [TransactionMessageController::class, 'show'])
        ->name('messages.show');

    // 取引チャット投稿（US002）
    Route::post('/', [TransactionMessageController::class, 'store'])
        ->name('messages.store');

    // 編集（FN010）
    Route::put('/{message}', [TransactionMessageController::class, 'update'])
        ->name('messages.update');

    // 削除（FN011）
    Route::delete('/{message}', [TransactionMessageController::class, 'destroy'])
        ->name('messages.destroy');
});


//取引完了
Route::post('/transactions/{transaction}/complete', [TransactionCompleteController::class, 'complete'])
    ->name('transactions.complete')
    ->middleware(['auth', 'verified']);

// ユーザー評価
Route::post('/transactions/{transaction}/rating', [RatingController::class, 'store'])
    ->name('rating.store')
    ->middleware(['auth', 'verified']);
