{{-- resources/views/dashboard.blade.php --}}

@extends('pages.users.user.layout.structure')

@section('title', 'Dashboard')
@section('header', 'Dashboard')

@section('content')
    @include('modules.subscriptionPlan.plans')
@endsection

@section('scripts')
<script>
  // On DOM ready, verify token; if missing, redirect home
  document.addEventListener('DOMContentLoaded', function() {
    if (!sessionStorage.getItem('token')) {
      window.location.href = '/';
    }
  });
</script>
@endsection
