@extends('layouts.app')

@section('title', 'Log in / Sign up — XentroCine')
@section('page', 'login')

@section('content')
<div class="auth-page">
  <div class="auth-card">
    <div class="auth-card__header">
      <h1 class="auth-title">Welcome back</h1>
      <p class="auth-subtitle">Sign in to continue booking.</p>
    </div>
    <form id="loginForm" action="{{ route('auth.login.submit') }}" method="POST" class="authform" novalidate>
      @csrf
      @if (session('error'))
        <div class="form-error">{{ session('error') }}</div>
      @endif
      @if ($errors->any())
        <div class="form-error">{{ $errors->first() }}</div>
      @endif
      <label>Email
        <input name="email" type="email" placeholder="you@example.com" value="{{ old('email') }}" required/>
      </label>
      <label>Password
        <div class="input-with-action">
          <input id="passwordField" name="password" type="password" placeholder="Your password" required/>
          <button type="button" id="togglePassword" class="input-action" aria-label="Show password" aria-controls="passwordField">
            <!-- eye icon -->
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
          </button>
        </div>
      </label>
      <div class="form-row">
        <a class="forgot-link" href="{{ route('password.request') }}">Forgot password?</a>
      </div>
      <button id="loginBtn" class="btn btn--primary" type="submit">Log in</button>
      <div class="form-help">New here? <a href="{{ route('signup') }}">Create an account</a></div>
    </form>
  </div>
  <div class="auth-spacer" aria-hidden="true"></div>
</div>
@push('scripts')
<script>
  (function(){
    const pw = document.getElementById('passwordField');
    const tog = document.getElementById('togglePassword');
    if(pw && tog){
      let shown = false;
      const updateIcon = () => {
        tog.setAttribute('aria-label', shown ? 'Hide password' : 'Show password');
        // swap icon paths by toggling class
        tog.classList.toggle('is-on', shown);
      };
      tog.addEventListener('click', (e)=>{
        e.preventDefault();
        shown = !shown;
        pw.type = shown ? 'text' : 'password';
        updateIcon();
      });
      updateIcon();
    }

    const form = document.getElementById('loginForm');
    const btn = document.getElementById('loginBtn');
    if(form && btn){
      form.addEventListener('submit', ()=>{
        btn.disabled = true;
        btn.textContent = 'Logging in…';
      });
    }
  })();
</script>
@endpush
@endsection
