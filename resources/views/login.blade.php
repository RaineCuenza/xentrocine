@extends('layouts.app')

@section('title', 'Log in / Sign up — XentroCine')
@section('page', 'login')

@section('content')
<h1>Welcome back</h1>
<form action="{{ route('auth.login.submit') }}" method="POST" class="authform" novalidate>
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
@endsection
