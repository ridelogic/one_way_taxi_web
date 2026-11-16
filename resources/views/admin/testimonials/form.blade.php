@extends('admin.layout')

@section('title', $testimonial->exists ? 'Edit testimonial' : 'Add testimonial')

@section('content')
    <a href="{{ route('admin.testimonials.index') }}">← All testimonials</a>
    <form method="POST" action="{{ $testimonial->exists ? route('admin.testimonials.update', $testimonial) : route('admin.testimonials.store') }}" class="acard" style="margin-top:12px;max-width:860px">
        @csrf
        @if ($testimonial->exists) @method('PUT') @endif
        <div class="aform-grid">
            <x-admin.input name="customer_name" label="Customer name" :value="$testimonial->customer_name" required />
            <x-admin.input name="location" label="Location" :value="$testimonial->location" />
            <x-admin.input name="trip" label="Trip (e.g. Chennai to Ooty)" :value="$testimonial->trip" />
            <x-admin.select name="rating" label="Rating" :options="[5 => '5 stars', 4 => '4 stars', 3 => '3 stars', 2 => '2 stars', 1 => '1 star']" :value="$testimonial->rating" placeholder="No rating" />
        </div>
        <x-admin.textarea name="quote" label="Testimonial" :value="$testimonial->quote" rows="5" required />
        <x-admin.input name="sort_order" label="Order" type="number" min="0" :value="$testimonial->sort_order" />
        <x-admin.checkbox name="is_active" label="Show on website" :checked="$testimonial->is_active" />
        <x-admin.checkbox name="is_sample" label="This is sample / demonstration content (shows a “Sample review” label)" :checked="$testimonial->is_sample" />
        <button type="submit" class="abtn abtn--primary">{{ $testimonial->exists ? 'Save' : 'Add testimonial' }}</button>
        <a href="{{ route('admin.testimonials.index') }}" class="abtn">Cancel</a>
    </form>
@endsection
