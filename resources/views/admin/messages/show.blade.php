@extends('admin.layout')

@section('title', 'Message from '.$message->name)

@section('content')
    <a href="{{ route('admin.messages.index') }}">← All messages</a>
    <section class="acard" style="margin-top:12px;max-width:820px">
        <dl class="detail-grid">
            <div><dt>Name</dt><dd>{{ $message->name }}</dd></div>
            <div><dt>Phone</dt><dd><a href="tel:{{ preg_replace('/[^0-9+]/', '', $message->phone) }}">{{ $message->phone }}</a></dd></div>
            <div><dt>Email</dt><dd>@if ($message->email)<a href="mailto:{{ $message->email }}">{{ $message->email }}</a>@else — @endif</dd></div>
            <div><dt>Received</dt><dd>{{ $message->created_at->format('d M Y, H:i') }}</dd></div>
        </dl>
        <h3 class="acard__title" style="margin-top:20px">{{ $message->subject ?: 'Message' }}</h3>
        <p style="white-space:pre-line">{{ $message->message }}</p>
        <x-admin.delete :action="route('admin.messages.destroy', $message)" />
    </section>
@endsection
