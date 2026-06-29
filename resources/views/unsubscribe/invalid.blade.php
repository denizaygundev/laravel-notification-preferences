@extends('notification-preferences::unsubscribe.layout')

@section('title', __('notification-preferences::notification-preferences.invalid_title'))

@section('content')
    <h1>{{ __('notification-preferences::notification-preferences.invalid_title') }}</h1>
    <p>{{ __('notification-preferences::notification-preferences.invalid_body') }}</p>
@endsection
