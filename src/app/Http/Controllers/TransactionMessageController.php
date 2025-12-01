<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Models\Transaction;
use App\Models\TransactionMessage;

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
            ->with('item')
            ->withCount([
                'messages as unread_count' => function ($query) use ($user) {
                    $query->where('is_read', false)
                        ->where('to_user_id', $user->id);
                }
            ])
            ->get();

        // 相手ユーザー情報
        $partner = $transaction->buyer_id === $user->id
            ? $transaction->seller
            : $transaction->buyer;

        return view('transaction_chat', compact('transaction', 'wipTransactions', 'partner'));
    }
}
