<?php

namespace App\Http\Controllers;

use App\Models\Profile;
use App\Models\Transaction;
use App\Http\Requests\ProfileRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    //プロフィール画面の表示
    public function showProfilePage()
    {
        $user = Auth::user();
        $profile = Profile::firstOrNew(['user_id' => auth()->id()]);

        // 出品商品
        $items = $user->items()->latest()->get();

        //購入済み商品
        $transactions = $user->transactions()
            ->where('status', Transaction::STATUS_COMPLETED)
            ->with('item')
            ->get();
        $purchasedItems = $transactions->pluck('item')->filter();

        //取引中商品
        $wipTransactions = Transaction::where(function ($query) use ($user) {
            $query->where('buyer_id', $user->id)
                ->orWhere('seller_id', $user->id);
        })
            ->where('status', Transaction::STATUS_WIP)
            ->with('item')
            ->get();

        $wipItems = $wipTransactions->pluck('item')->filter();

        return view('profile', compact('profile', 'user', 'items', 'purchasedItems', 'wipItems'));
    }

    //プロフィール編集画面の表示
    public function showProfileEditPage()
    {
        $profile = Profile::firstOrNew(['user_id' => auth()->id()]);
        $user = Auth::user();
        return view('edit_profile', compact('profile', 'user'));
    }

    //プロフィール更新処理
    public function updateProfile(ProfileRequest $request)
    {
        DB::transaction(function () use ($request) {
            $validated = $request->validated();

            $user = auth()->user();
            $user->update(['name' => $validated['name']]);

            // プロフィール取得 or 新規生成
            $profile = Profile::firstOrNew(['user_id' => auth()->id()]);

            //プロフィール画像アップロードの処理
            $oldPath = $profile->avatar_path;
            if ($request->hasFile('avatar_path') && $request->file('avatar_path')->isValid()) {
                $path = $request->file('avatar_path')->store('material_images', 'public');
                $profile->avatar_path = $path;
            }

            $profile->fill([
                'postal_code' => $validated['postal_code'] ?? null,
                'address'     => $validated['address'] ?? null,
                'building'    => $validated['building'] ?? null,
            ]);
            $profile->save();

            // コミット成功後だけ古い画像を削除（ロールバック時は実行されない）
            if ($oldPath && $oldPath !== $profile->avatar_path) {
                DB::afterCommit(function () use ($oldPath) {
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($oldPath);
                });
            }
        });

        return redirect()->route('profile.show')->with('message', 'プロフィールを更新しました。');
    }
}
