@extends('layouts.app')

@section('content')
    <x-page-banner title="Image Credits" subtitle="Photographs used on this website and their licences." :crumbs="['Image credits' => null]" />

    <section class="section pt-12!">
        <div class="container-site max-w-4xl">
            <ul class="grid gap-4">
                @forelse ($media as $item)
                    <li class="card flex flex-col gap-4 p-4 sm:flex-row sm:items-center">
                        <x-img :media="$item" alt="" class="h-24 w-full shrink-0 rounded-xl sm:w-36" />
                        <p class="min-w-0 text-sm break-all text-ink-soft sm:break-words">{{ $item->credit }}</p>
                    </li>
                @empty
                    <li class="text-ink-soft">No attributed images.</li>
                @endforelse
            </ul>
        </div>
    </section>
@endsection
