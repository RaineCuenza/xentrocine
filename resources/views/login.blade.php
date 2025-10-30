@extends('layouts.app')

@section('title', 'Log in / Sign up — XentroCine')
@section('page', 'auth')

@section('content')
<h1>Welcome</h1>
<div class="tabs">
  <button class="tab tab--active" data-tab="loginTab">Log in</button>
  <button class="tab" data-tab="signupTab">Sign up</button>
</div>

<div id="loginTab" class="tabcontent tabcontent--active">
  <form id="loginForm" class="authform" novalidate>
    @csrf
    <label>Email
      <input id="loginEmail" type="email" placeholder="you@example.com" required/>
    </label>
    <label>Password
      <input id="loginPassword" type="password" placeholder="Your password" required/>
    </label>
    <button class="btn btn--primary" type="submit">Log in</button>
    <div class="form-help">Default Admin: <code>admin@xentrocine.local</code> / <code>Admin123!</code></div>
    <div id="loginErr" class="form-error"></div>
  </form>
</div>

<div id="signupTab" class="tabcontent">
  <form id="signupForm" class="authform" novalidate>
    @csrf
    <label>Full name
      <input id="suName" type="text" minlength="2" placeholder="Juan Dela Cruz" required/>
    </label>
    <label>Email
      <input id="suEmail" type="email" placeholder="you@example.com" required/>
    </label>
    <label>Password
      <input id="suPassword" type="password" minlength="6" placeholder="Min 6 chars" required/>
    </label>
    <button class="btn btn--primary" type="submit">Create account</button>
    <div class="form-help">Demo only: data stays in your browser.</div>
    <div id="signupErr" class="form-error"></div>
  </form>
</div>
@endsection
