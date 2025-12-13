@extends('layouts.app')

@section('title', 'Forgot password — XentroCine')
@section('page', 'login')

@section('content')
<div class="auth-page">
  <div class="auth-card">
    <div class="auth-card__header">
      <h1 class="auth-title">Forgot password</h1>
      <p class="auth-subtitle">Enter your email to receive a reset link.</p>
    </div>
    <form id="forgotForm" action="{{ route('password.email') }}" method="POST" class="authform" novalidate>
      @csrf
      @if (session('status'))
        <div class="form-help" role="status">{{ session('status') }}</div>
      @endif
      @if (session('error'))
        <div class="form-error">{{ session('error') }}</div>
      @endif
      @if ($errors->any())
        <div class="form-error">{{ $errors->first() }}</div>
      @endif
      <label>Email
        <input name="email" type="email" placeholder="you@example.com" value="{{ old('email') }}" required/>
      </label>
      <button id="forgotBtn" class="btn btn--primary" type="submit">Send reset link</button>
      <div class="form-help"><a href="{{ route('login') }}">Back to login</a></div>
    </form>
  </div>
  <div class="auth-spacer" aria-hidden="true"></div>
</div>
@push('scripts')
<script>
  (function(){
    const form = document.getElementById('forgotForm');
    const btn = document.getElementById('forgotBtn');
    if(form && btn){
      form.addEventListener('submit', ()=>{ btn.disabled = true; btn.textContent = 'Sending…'; });
    }
  })();
</script>
@endpush
@endsection
