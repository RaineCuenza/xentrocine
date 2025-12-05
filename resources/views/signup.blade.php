@extends('layouts.app')

@section('title', 'Create account — XentroCine')
@section('page', 'login')

@section('content')
<div style="display:flex; flex-direction:column; align-items:center; padding:3rem 2rem; min-height:900px; margin:0 auto;">
<h1 style="text-align:center; margin-bottom:1rem;" >Create your account</h1>
<form action="{{ route('auth.signup.submit') }}" method="POST" class="authform" novalidate style="width:100%; max-width:700px;">
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
  <button class="btn btn--primary" type="submit">Create account</button>
  <div class="form-help">After signup, check your email for a verification link.</div>
  <div class="form-help">Have an account? <a href="{{ route('login') }}">Log in</a></div>
</form>
@endsection
