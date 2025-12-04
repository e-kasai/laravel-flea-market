@extends("layouts.app")

@push("styles")
    <link rel="stylesheet" href="{{ asset("css/profile.css") }}" />
    <link rel="stylesheet" href="{{ asset("css/tab.css") }}" />
    <link rel="stylesheet" href="{{ asset("css/grid.css") }}" />
@endpush

@section("content")
    <section class="profile">
        <div class="profile__info">
            <img
                class="avatar"
                src="{{
                    $profile->avatar_path
                        ? asset("storage/" . $profile->avatar_path)
                        : asset("img/noimage.png")
                }}"
                alt="プロフィール画像"
            />
            <div class="profile__text">
                <h1 class="profile__name">{{ $user->name }}</h1>
                @if ($averageRating)
                    <div class="profile__rating">
                        @for ($i = 1; $i <= 5; $i++)
                            @if ($i <= $averageRating)
                                <span class="star star--active">★</span>
                            @else
                                <span class="star">★</span>
                            @endif
                        @endfor
                    </div>
                @endif
            </div>
        </div>
        <div class="profile__link">
            <a class="profile__link-edit" href="{{ route("profile.edit") }}">プロフィールを編集</a>
        </div>
    </section>

    <nav class="tabs">
        @php
            $activeTab = request("page", "sell");
            $tabs = [
                ["label" => "出品した商品", "href" => route("profile.show", ["page" => "sell"]), "active" => $activeTab === "sell" ? "is-active" : ""],
                ["label" => "購入した商品", "href" => route("profile.show", ["page" => "buy"]), "active" => $activeTab === "buy"],
                ["label" => "取引中の商品", "href" => route("profile.show", ["page" => "wip"]), "active" => $activeTab === "wip", "badge" => $totalUnread],
            ];
        @endphp

        <x-tabs.nav :items="$tabs" />
    </nav>

    {{-- 出品一覧（共通グリッド） --}}
    @if ($activeTab === "sell")
        <section class="tabs__content">
            <x-grid.item :items="$items" />
        </section>
    @endif

    {{-- 購入一覧（共通グリッド） --}}
    @if ($activeTab === "buy")
        <div class="tabs__content">
            <x-grid.item :items="$purchasedItems" />
        </div>
    @endif

    {{-- 取引中一覧（共通グリッド） --}}
    @if ($activeTab === "wip")
        <div class="tabs__content">
            <x-grid.item :items="$wipItems" linkRoute="messages.show" />
        </div>
    @endif
@endsection
