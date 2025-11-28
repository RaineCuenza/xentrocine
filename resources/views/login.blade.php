@extends('layouts.app')

@section('title', 'Log in / Sign up — XentroCine')
@section('page', 'login')

@section('content')
<div style="display:flex; flex-direction:column; align-items:center; padding:3rem 2rem; min-height:900px; margin:0 auto;">
  <h1 style="text-align:center; margin-bottom:1rem;">Welcome back!</h1>
  <form action="{{ route('auth.login.submit') }}" method="POST" class="authform" novalidate style="width:100%; max-width:700px;">
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
    <input name="password" type="password" placeholder="Your password" required/>
  </label>
  <button class="btn btn--primary" type="submit">Log in</button>
  <div class="form-help">New here? <a href="{{ route('signup') }}">Create an account</a></div>
  </form>
</div>
@endsection
