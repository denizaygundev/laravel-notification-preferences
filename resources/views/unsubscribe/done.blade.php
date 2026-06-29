@extends('notification-preferences::unsubscribe.layout')

@php
    /** @var \Denizaygundev\NotificationPreferences\Models\NotificationType|null $type */
    $label = $type?->name ?? __('notification-preferences::notification-preferences.these_notifications');
@endphp

@section('title', __('notification-preferences::notification-preferences.done_title'))

@section('content')
    <h1>{{ __('notification-preferences::notification-preferences.done_title') }}</h1>
    <p>{{ __('notification-preferences::notification-preferences.done_body', ['label' => $label]) }}</p>
@endsection
