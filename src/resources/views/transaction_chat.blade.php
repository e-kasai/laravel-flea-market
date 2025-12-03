@extends("layouts.app")

@push("styles")
    <link rel="stylesheet" href="{{ asset("css/transaction_chat.css") }}" />
@endpush

@section("content")
    <main class="transactions">
        {{-- 左サイドバー：その他の取引一覧 --}}
        <aside class="transactions-sidebar">
            <h1 class="transactions-sidebar__title">その他の取引</h1>

            <ul class="transactions-sidebar__list">
                @foreach ($wipTransactions as $other)
                    <li class="transactions-sidebar__item {{ $other->id === $transaction->id ? "is-active" : "" }}">
                        <a class="transactions-sidebar__link" href="{{ route("messages.show", $other->id) }}">
                            <div class="transactions-sidebar__text">
                                <h2 class="transactions-sidebar__name">{{ $other->item->item_name }}</h2>
                                @if (($other->unread_count ?? 0) > 0)
                                    <span class="transaction-sidebar__badge">{{ $other->unread_count }}</span>
                                @endif
                            </div>
                        </a>
                    </li>
                @endforeach
            </ul>
        </aside>

        {{-- 右側メインエリア --}}
        <section class="transactions-main">
            {{-- 上部ヘッダー --}}
            <header class="transactions-header">
                <h1 class="transactions-header__title">「{{ $partner->name }}」さんとの取引画面</h1>

                {{-- 取引完了ボタン（購入者のみ表示） --}}
                @if (auth()->id() === $transaction->buyer_id)
                    {{--
                        <form method="POST" action="{{ route("transactions.complete", $transaction->id) }}">
                        @csrf
                        @method("patch")
                        <button type="submit" class="transactions-header__complete">取引を完了する</button>
                        </form>
                    --}}
                @endif
            </header>

            {{-- 商品情報エリア --}}
            <section class="transactions-item">
                <div class="transactions-item__image">
                    <img src="{{ $transaction->item->image_url }}" alt="{{ $transaction->item->item_name }}" />
                </div>
                <div class="transactions-item__info">
                    <p class="transactions-item__name">{{ $transaction->item->item_name }}</p>
                    <p class="transactions-item__price">{{ number_format($transaction->item->price) }}円</p>
                </div>
            </section>

            {{-- メッセージ一覧 --}}
            <section class="transactions-messages">
                @foreach ($transaction->messages as $message)
                    <div
                        class="transactions-message {{ $message->user_id === auth()->id() ? "transactions-message--me" : "transactions-message--other" }}"
                    >
                        <div class="transactions-message__profile">
                            {{-- プロフィール画像と名前 --}}
                            <span class="transactions-message__user">{{ $message->user->name }}</span>
                            <span class="transactions-message__avatar-circle"></span>
                        </div>
                        <div class="transactions-message__body">
                            {{-- 日時と本文 --}}
                            <div class="transactions-message__header">
                                <span class="transactions-message__time">{{ $message->created_at->format("Y/m/d H:i") }}</span>
                            </div>
                            {{-- <p class="transactions-message__text">{{ $message->body }}</p> --}}
                            <p id="message-body-{{ $message->id }}" class="transactions-message__text">
                                {{ $message->body }}
                            </p>
                            {{-- 画像 --}}
                            @if ($message->image_path)
                                <img
                                    class="transactions-message__image"
                                    src="{{ asset("storage/" . $message->image_path) }}"
                                    alt="送信画像"
                                />
                            @endif

                            @if ($message->user_id === auth()->id())
                                <div class="transactions-message__actions">
                                    {{-- 編集 --}}
                                    @if ($transaction->status === \App\Models\Transaction::STATUS_WIP && $message->user_id === auth()->id())
                                        <button
                                            class="message-edit-btn"
                                            data-update-url="{{ route("messages.update", ["transaction" => $transaction->id, "message" => $message->id]) }}"
                                        >
                                            編集
                                        </button>
                                    @endif

                                    {{-- 削除 --}}
                                    @if ($transaction->status === \App\Models\Transaction::STATUS_WIP && $message->user_id === auth()->id())
                                        <form
                                            class="message-delete-form"
                                            method="POST"
                                            action="{{ route("messages.destroy", ["transaction" => $transaction->id, "message" => $message->id]) }}"
                                        >
                                            @csrf
                                            @method("DELETE")
                                            <button type="submit" class="message-delete-btn">削除</button>
                                        </form>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </section>

            {{-- 入力フォーム（画面下部） --}}
            <section class="transactions-input">
                {{-- エラー表示 --}}
                @if ($errors->any())
                    <div class="transactions-errors">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- 新規投稿時のフォーム --}}
                <form
                    method="POST"
                    action="{{ route("messages.store", $transaction->id) }}"
                    enctype="multipart/form-data"
                    class="transactions-input__form"
                >
                    @csrf

                    <textarea
                        name="body"
                        class="transactions-input__textarea"
                        placeholder="取引メッセージを記入してください"
                        rows="2"
                    >
{{ old("body") }}</textarea
                    >
                    <div class="transactions-input__footer">
                        <label class="transactions-input__image-button">
                            画像を追加
                            <input type="file" name="image_path" class="transactions-input__file" />
                        </label>

                        <button type="submit" class="transactions-input__send">送信</button>
                    </div>
                </form>

                {{-- 投稿後の編集用フォーム --}}
                <form id="message-edit-form" method="POST" style="display: none">
                    @csrf
                    @method("PUT")
                    <textarea name="body" id="message-edit-body"></textarea>
                    <button type="submit">更新</button>
                </form>
            </section>
        </section>
    </main>
@endsection

{{-- 本文入力保持 --}}
@push("scripts")
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const textarea = document.querySelector('textarea[name="body"]');
            const key = 'transaction_draft_{{ $transaction->id }}';

            // ページ表示時：localStorage に残っている下書きをセット
            const saved = localStorage.getItem(key);
            if (saved) {
                textarea.value = saved;
            }

            // 入力するたびに保存
            textarea.addEventListener('input', () => {
                localStorage.setItem(key, textarea.value);
            });

            // 送信時：下書きを削除
            const form = textarea.closest('form');
            form.addEventListener('submit', () => {
                localStorage.removeItem(key);
            });
        });

        document.addEventListener('click', function (e) {
            if (e.target.classList.contains('message-edit-btn')) {
                const url = e.target.dataset.updateUrl;

                const bodyEl = e.target.closest('.transactions-message').querySelector('.transactions-message__text');
                const body = bodyEl.innerText;

                const form = document.getElementById('message-edit-form');
                const textarea = document.getElementById('message-edit-body');

                textarea.value = body;
                form.action = url;

                form.style.display = 'block';
            }
        });
    </script>
@endpush
