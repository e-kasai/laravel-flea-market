<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Models\Transaction;
use App\Models\TransactionMessage;
use App\Http\Requests\TransactionMessageRequest;


class TransactionMessageController extends Controller
{
    // 取引チャット画面を表示
    public function show(Transaction $transaction)
    {
        $user = auth()->user();

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
            ->where('status', Transaction::STATUS_WIP)
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

        return view('transaction_chat', compact('transaction', 'wipTransactions', 'partner'));
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
}
