<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Models\Transaction;
use App\Models\TransactionMessage;
use App\Models\Rating;
use App\Http\Requests\TransactionMessageRequest;


class TransactionMessageController extends Controller
{
    // 取引チャット画面を表示
    public function show(Transaction $transaction)
    {
        $user = auth()->user();
        $buyerId  = $transaction->buyer_id;
        $sellerId = $transaction->seller_id;

        // 購入者でも出品者でもない場合は４０３
        if ($transaction->buyer_id !== $user->id && $transaction->seller_id !== $user->id) {
            abort(403);
        }

        // メイン取引（messages と item を読み込む）
        $transaction->load([
            'item',
            'messages.user',
        ]);

        // サイドバー：ユーザーが関係する取引中一覧
        $wipTransactions = Transaction::where(function ($query) use ($user) {
            $query->where('buyer_id', $user->id)
                ->orWhere('seller_id', $user->id);
        })
            ->whereIn('status', [
                Transaction::STATUS_WIP,
                Transaction::STATUS_CONFIRMED
            ])
            ->where('id', '!=', $transaction->id)
            ->with('item')
            ->withCount([
                'messages as unread_count' => function ($query) use ($user) {
                    $query->where('is_read', false)
                        ->where('to_user_id', $user->id);
                }
            ])
            ->orderByDesc(
                TransactionMessage::select('created_at')
                    ->whereColumn('transaction_id', 'transactions.id')
                    ->latest()
                    ->take(1)
            )
            ->get();

        // 相手ユーザー情報
        $partner = $transaction->buyer_id === $user->id
            ? $transaction->seller
            : $transaction->buyer;

        // 未読メッセージを既読に変更
        TransactionMessage::where('transaction_id', $transaction->id)
            ->where('to_user_id', $user->id)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        // 購入者が評価済
        $buyerHasRated = Rating::where('transaction_id', $transaction->id)
            ->where('from_user_id', $buyerId)
            ->exists();

        // 出品者が評価済
        $sellerHasRated = Rating::where('transaction_id', $transaction->id)
            ->where('from_user_id', $sellerId)
            ->exists();

        // 購入者のモーダル表示条件
        $showBuyerModal = ($user->id === $buyerId)
            && !$buyerHasRated
            && $transaction->status === Transaction::STATUS_CONFIRMED;

        // 出品者のモーダル表示条件
        $showSellerModal = ($user->id === $sellerId) && $buyerHasRated && !$sellerHasRated;

        return view('transaction_chat', compact('transaction', 'wipTransactions', 'partner', 'showBuyerModal', 'showSellerModal'));
    }

    // 取引画面チャット投稿
    public function store(TransactionMessageRequest $request, Transaction $transaction)
    {
        $validated = $request->validated();
        // 受信者（to_user_id）を決める
        $toUserId = ($transaction->buyer_id === auth()->id())
            ? $transaction->seller_id
            : $transaction->buyer_id;

        // 画像の保存
        $imagePath = null;
        if ($request->hasFile('image_path')) {
            $imagePath = $request->file('image_path')->store(
                'transaction_images',
                'public'
            );
        }

        // メッセージ保存
        TransactionMessage::create([
            'transaction_id' => $transaction->id,
            'user_id'        => auth()->id(),
            'to_user_id'     => $toUserId,
            'body'           => $validated['body'],
            'image_path'     => $imagePath,
        ]);

        return back();
    }

    //投稿済みメッセージ編集
    public function update(TransactionMessageRequest $request, Transaction $transaction, TransactionMessage $message)
    {
        $validated = $request->validated();

        // 自分のメッセージ以外は編集禁止
        if ($message->user_id !== auth()->id()) {
            abort(403);
        }

        // 取引が「取引中（WIP）」でない場合は編集禁止
        if ($transaction->status !== \App\Models\Transaction::STATUS_WIP) {
            abort(403, 'この取引は完了しているため編集できません。');
        }

        // 更新実行
        $message->update([
            'body' => $validated['body'],
        ]);

        return back()->with('message', 'メッセージを更新しました');
    }

    // 投稿済みメッセージ削除
    public function destroy(Transaction $transaction, TransactionMessage $message)
    {
        // 取引とメッセージの紐づきチェック
        if ($message->transaction_id !== $transaction->id) {
            abort(404);
        }

        // 取引完了後はメッセージ削除不可
        if ($transaction->status !== Transaction::STATUS_WIP) {
            abort(403, '取引完了後の為削除できません');
        }

        // 自分の投稿メッセージのみ削除可能
        if ($message->user_id !== Auth::id()) {
            abort(403);
        }

        $message->delete();
        return back()->with('message', 'メッセージを削除しました');;
    }
}
