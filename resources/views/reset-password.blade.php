@extends('layouts.app')

@section('title', 'Reset password — XentroCine')
@section('page', 'login')

@section('content')
<div class="auth-page">
  <div class="auth-card">
    <div class="auth-card__header">
      <h1 class="auth-title">Reset your password</h1>
      <p class="auth-subtitle">Choose a new password to secure your account.</p>
    </div>
    <form id="resetForm" action="{{ route('password.update') }}" method="POST" class="authform" novalidate>
      @csrf
      @if (session('error'))
        <div class="form-error">{{ session('error') }}</div>
      @endif
      @if ($errors->any())
        <div class="form-error">{{ $errors->first() }}</div>
      @endif
      <input type="hidden" name="token" value="{{ $token }}" />
      <label>Email
        <input name="email" type="email" placeholder="you@example.com" value="{{ old('email', $email ?? '') }}" required/>
      </label>
      <label>New password
        <input name="password" type="password" minlength="6" placeholder="Min 6 chars" required/>
      </label>
      <label>Confirm password
        <input name="password_confirmation" type="password" minlength="6" placeholder="Re-enter password" required/>
      </label>
      <button id="resetBtn" class="btn btn--primary" type="submit">Reset password</button>
      <div class="form-help"><a href="{{ route('login') }}">Back to login</a></div>
    </form>
  </div>
  <div class="auth-spacer" aria-hidden="true"></div>
</div>
@push('scripts')
<script>
  (function(){
    const form = document.getElementById('resetForm');
    const btn = document.getElementById('resetBtn');
    if(form && btn){ form.addEventListener('submit', ()=>{ btn.disabled = true; btn.textContent = 'Saving…'; }); }
  })();
</script>
@endpush
@endsection
