@extends('layouts.app')

@section('content')
    <section class="section bg-mint">
        <div class="container-site max-w-3xl">
            <div class="card p-8 text-center sm:p-12">
                <span class="mx-auto grid size-20 place-items-center rounded-full bg-fresh text-white ring-8 ring-mint" aria-hidden="true"><x-icon name="check" :size="40" :stroke="3" /></span>
                <h1 class="mt-6 text-3xl font-extrabold sm:text-4xl">Enrollment received</h1>
                <p class="mt-3 text-lg text-ink-soft">Thank you, {{ $enrollment->first_name }}. Your details have been submitted for verification.</p>
                <p class="mt-6"><span class="inline-block rounded-2xl border-2 border-dashed border-gold bg-gold-50 px-6 py-3 font-display text-2xl font-extrabold tracking-wider text-forest">{{ $enrollment->reference }}</span></p>
                <p class="mx-auto mt-5 max-w-xl text-sm text-ink-soft">Your driver account stays inactive until our team verifies your documents. We will call you on your mobile number — after activation, sign in to the driver app with it.</p>

                <dl class="mt-8 grid gap-3 text-left sm:grid-cols-2">
                    @foreach (['Driver' => $enrollment->fullName(), 'Mobile' => '+91 '.$enrollment->phone, 'Vehicle type' => $enrollment->model_name ?? '—', 'Vehicle number' => $enrollment->taxi_no, 'Status' => 'Inactive — awaiting verification'] as $label => $value)
                        <div class="rounded-xl bg-mint px-4 py-3"><dt class="text-xs font-semibold tracking-wide text-ink-soft uppercase">{{ $label }}</dt><dd class="mt-1 font-semibold text-forest">{{ $value }}</dd></div>
                    @endforeach
                </dl>

                <div class="mt-8 flex flex-wrap justify-center gap-3">
                    <a href="{{ url('/') }}" class="btn btn-outline">Back to home</a>
                    @if ($settings->get('phone'))
                        <a href="{{ $settings->telLink() }}" class="btn btn-gold"><x-icon name="phone" :size="18" /> Call {{ $settings->get('phone') }}</a>
                    @endif
                </div>
            </div>
        </div>
    </section>
@endsection
