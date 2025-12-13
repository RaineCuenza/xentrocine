@extends('layouts.app')

@section('title', 'Create account — XentroCine')
@section('page', 'login')

@section('content')
<div class="auth-page">
  <div class="auth-card">
    <div class="auth-card__header">
      <h1 class="auth-title">Create your account</h1>
      <p class="auth-subtitle">Sign up to start booking movies.</p>
    </div>
    <form id="signupForm" action="{{ route('auth.signup.submit') }}" method="POST" class="authform" novalidate>
      @csrf
      @if ($errors->any())
        <div class="form-error">{{ $errors->first() }}</div>
      @endif
      <label>Full name
        <input name="name" type="text" minlength="2" placeholder="Juan Dela Cruz" value="{{ old('name') }}" required/>
      </label>
      <label>Email
        <input name="email" type="email" placeholder="you@example.com" value="{{ old('email') }}" required/>
      </label>
      <label>Password
        <input name="password" type="password" minlength="6" placeholder="Min 6 chars" required/>
      </label>
      <button id="signupBtn" class="btn btn--primary" type="submit">Create account</button>
      <div class="form-help">After signup, check your email for a verification link.</div>
      <div class="form-help">Have an account? <a href="{{ route('login') }}">Log in</a></div>
    </form>
  </div>
  <div class="auth-spacer" aria-hidden="true"></div>
  </div>
@push('scripts')
<script>
  (function(){
    const form = document.getElementById('signupForm');
    const btn = document.getElementById('signupBtn');
    if(form && btn){
      form.addEventListener('submit', ()=>{ btn.disabled = true; btn.textContent = 'Creating…'; });
    }
  })();
</script>
@endpush
@endsection
