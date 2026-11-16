@extends('layouts.app', ['seo' => \App\Support\Seo::make('Page not found')->noindex()])

@section('content')
    <section class="section bg-mint">
        <div class="container-site max-w-2xl py-10 text-center">
            <p class="font-display text-8xl font-extrabold text-gold sm:text-9xl">404</p>
            <h1 class="mt-4 text-3xl font-extrabold">This road doesn’t go anywhere</h1>
            <p class="mt-3 text-lg text-ink-soft">The page you’re looking for has moved or no longer exists.</p>
            <div class="mt-8 flex flex-wrap justify-center gap-3">
                <a href="{{ route('home') }}" class="btn btn-gold">Back to home</a>
                <a href="{{ route('routes.index') }}" class="btn btn-outline">Browse routes</a>
            </div>
        </div>
    </section>
@endsection
