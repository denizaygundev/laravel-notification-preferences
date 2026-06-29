@extends('notification-preferences::unsubscribe.layout')

@php
    /** @var \Denizaygundev\NotificationPreferences\Models\NotificationType|null $type */
    $label = $type?->name ?? __('notification-preferences::notification-preferences.these_notifications');
@endphp

@section('title', __('notification-preferences::notification-preferences.unsubscribe'))

@section('content')
    <h1>{{ __('notification-preferences::notification-preferences.confirm_title') }}</h1>
    <p>{{ __('notification-preferences::notification-preferences.confirm_body', ['label' => $label]) }}</p>
    <form method="POST" action="{{ route('notification-preferences.unsubscribe.process', ['token' => $token]) }}">
        <button type="submit">{{ __('notification-preferences::notification-preferences.confirm_button') }}</button>
    </form>
@endsection
