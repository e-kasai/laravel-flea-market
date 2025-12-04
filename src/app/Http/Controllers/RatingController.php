<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Rating;
use App\Models\Transaction;

class RatingController extends Controller
{
    public function store(Transaction $transaction, Request $request)
    {
        $transaction->load('item');

        $request->validate([
            'score' => 'required|integer|min:1|max:5',
        ]);

        $userId = auth()->id();
        $buyerId  = $transaction->buyer_id;
        $sellerId = $transaction->item->seller_id;

        // すでに自分が評価している = 再評価不可
        $alreadyRated = Rating::where('transaction_id', $transaction->id)
            ->where('from_user_id', $userId)
            ->exists();

        if ($alreadyRated) {
            abort(403, 'すでに評価済みです');
        }

        // 出品者
        if ($userId === $sellerId) {
            if ($transaction->status !== Transaction::STATUS_CONFIRMED) {
                abort(403, '取引完了後に評価できます');
            }

            // 購入者
        } elseif ($userId === $buyerId) {
            if ($transaction->status !== Transaction::STATUS_CONFIRMED) {
                abort(403, '取引完了後に評価できます');
            }

            // それ以外
        } else {
            abort(403, 'この取引の評価権限がありません');
        }

        // 購入者が完了ボタンを押して評価した場合、取引を完了させる
        if ($userId === $buyerId) {
            $transaction->update(['status' => Transaction::STATUS_CONFIRMED]);
        }


        Rating::create([
            'transaction_id' => $transaction->id,
            'from_user_id'   => $userId,                // 評価した人
            'to_user_id'     => ($userId === $buyerId)
                ? $sellerId             // 購入者 → 出品者を評価
                : $buyerId,             // 出品者 → 購入者を評価
            'score'          => $request->score,
        ]);

        return redirect('/')
            ->with('message', '評価が完了しました');
    }
}
