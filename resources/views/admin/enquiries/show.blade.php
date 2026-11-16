@extends('admin.layout')

@php($routeBase = $type === 'corporate' ? 'admin.corporate-enquiries' : 'admin.partner-enquiries')

@section('title', ($type === 'corporate' ? 'Corporate enquiry ' : 'Partner registration ').$enquiry->reference)

@section('content')
    <div class="page-head">
        <div>
            <a href="{{ route($routeBase.'.index') }}">← All {{ $type === 'corporate' ? 'corporate enquiries' : 'partner registrations' }}</a>
            <h2 style="margin:6px 0 0"><span class="ref">{{ $enquiry->reference }}</span> <span class="abadge abadge--{{ $enquiry->status->tone() }}">{{ $enquiry->status->label() }}</span></h2>
            <p>Received {{ $enquiry->created_at->format('l, j M Y \a\t H:i') }}.</p>
        </div>
        <a class="abtn abtn--primary" href="tel:{{ $enquiry->phone }}"><x-icon name="phone" :size="16" /> Call</a>
    </div>

    <div class="layout-2">
        <section class="acard">
            <dl class="detail-grid">
                @if ($type === 'corporate')
                    <div><dt>Company</dt><dd>{{ $enquiry->company_name }}</dd></div>
                    <div><dt>Contact</dt><dd>{{ $enquiry->contact_name }} @if ($enquiry->designation)<small class="muted">({{ $enquiry->designation }})</small>@endif</dd></div>
                    <div><dt>Phone</dt><dd><a href="tel:{{ $enquiry->phone }}">{{ $enquiry->phone }}</a></dd></div>
                    <div><dt>Email</dt><dd><a href="mailto:{{ $enquiry->email }}">{{ $enquiry->email }}</a></dd></div>
                    <div><dt>City</dt><dd>{{ $enquiry->city ?: '—' }}</dd></div>
                    <div><dt>Requirement</dt><dd>{{ $enquiry->requirementLabel() }}</dd></div>
                    <div><dt>Trips per month</dt><dd>{{ $enquiry->monthly_trips ?: 'Not specified' }}</dd></div>
                @else
                    <div><dt>Name</dt><dd>{{ $enquiry->name }}</dd></div>
                    <div><dt>Phone</dt><dd><a href="tel:{{ $enquiry->phone }}">{{ $enquiry->phone }}</a></dd></div>
                    <div><dt>Email</dt><dd>@if ($enquiry->email)<a href="mailto:{{ $enquiry->email }}">{{ $enquiry->email }}</a>@else — @endif</dd></div>
                    <div><dt>City</dt><dd>{{ $enquiry->city }}</dd></div>
                    <div><dt>Partner type</dt><dd>{{ $enquiry->partnerTypeLabel() }}</dd></div>
                    <div><dt>Vehicle</dt><dd>{{ $enquiry->vehicleTypeLabel() }} @if ($enquiry->vehicle_count) × {{ $enquiry->vehicle_count }}@endif</dd></div>
                    <div><dt>Experience</dt><dd>{{ $enquiry->experience_years !== null ? $enquiry->experience_years.' years' : '—' }}</dd></div>
                    <div><dt>Commercial licence</dt><dd>{{ $enquiry->has_commercial_licence ? 'Yes (self-declared)' : 'No / not stated' }}</dd></div>
                @endif
                <div style="grid-column:1/-1"><dt>Message</dt><dd style="white-space:pre-line;font-weight:500">{{ $enquiry->message ?: '—' }}</dd></div>
            </dl>
        </section>
        <aside>
            <form method="POST" action="{{ route($routeBase.'.update', $enquiry) }}" class="acard">
                @csrf @method('PUT')
                <h3 class="acard__title">Update</h3>
                <x-admin.select name="status" label="Status" :options="\App\Enums\EnquiryStatus::options()" :value="$enquiry->status->value" />
                <x-admin.textarea name="admin_notes" label="Internal notes" :value="$enquiry->admin_notes" rows="6" />
                <button type="submit" class="abtn abtn--primary" style="width:100%">Save</button>
            </form>
            <x-admin.delete :action="route($routeBase.'.destroy', $enquiry)" label="Delete" />
        </aside>
    </div>
@endsection
