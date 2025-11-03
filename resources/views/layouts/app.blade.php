<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'XentroCine')</title>
    {{-- <script src="http://192.168.0.197:5173/resources/js/app-api.js"></script> --}}
    @vite(['resources/css/styles.css', 'resources/js/app-api.js'])
    @stack('styles')
</head>

<body data-page="@yield('page', 'home')">
    <header class="site-header">
        <a class="brand" href="{{ route('home') }}">XentroCine</a>
        
        <!-- Mobile: Account icon (left of hamburger) -->
        @auth
            <button type="button" id="mobileAccountToggle" class="mobile-account-icon" aria-label="Account menu" aria-expanded="false">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                    <circle cx="12" cy="7" r="4"/>
                </svg>
            </button>
            <div id="mobileAccountMenu" class="mobile-account-menu" style="display:none">
                <div class="mobile-account-menu__header">{{ auth()->user()->name }}</div>
                <form action="{{ route('auth.logout') }}" method="POST" style="margin:0">
                    @csrf
                    <button type="submit" class="mobile-account-menu__logout">Log out</button>
                </form>
            </div>
        @else
            <a href="{{ route('login') }}" class="mobile-account-icon" aria-label="Log in">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                    <circle cx="12" cy="7" r="4"/>
                </svg>
            </a>
        @endauth
        
        <button id="mobileMenuToggle" class="mobile-toggle" aria-label="Toggle menu" aria-expanded="false" aria-controls="primaryNav">
            <span class="mobile-toggle__bar"></span>
            <span class="mobile-toggle__bar"></span>
            <span class="mobile-toggle__bar"></span>
        </button>
        
        <nav id="primaryNav" class="nav">
            <a class="nav__link @if (Route::currentRouteName() == 'home') nav__link--active @endif"
                href="{{ route('home') }}">Home</a>
            @guest
                <a class="nav__link @if (Route::currentRouteName() == 'booking') nav__link--active @endif"
                    href="{{ route('booking') }}"><span class="hide-mobile">Booking & Reservation</span><span class="show-mobile">Booking</span></a>
            @else
                @if (auth()->user()->role === 'client')
                    <a class="nav__link @if (Route::currentRouteName() == 'booking') nav__link--active @endif"
                        href="{{ route('booking') }}"><span class="hide-mobile">Booking & Reservation</span><span class="show-mobile">Booking</span></a>
                @endif
            @endguest
            <a class="nav__link @if (Route::currentRouteName() == 'about') nav__link--active @endif"
                href="{{ route('about') }}">About</a>
            @auth
                @if (auth()->user()->role === 'staff')
                    <a class="nav__link @if (Route::currentRouteName() == 'staff') nav__link--active @endif" href="{{ route('staff') }}">Cashier</a>
                @endif
                @if (auth()->user()->role === 'admin')
                    <a class="nav__link @if (Route::currentRouteName() == 'admin') nav__link--active @endif" href="{{ route('admin') }}">Manage</a>
                @endif
            @endauth
        </nav>
        <div class="account">
            @auth
                <span class="account__who">{{ auth()->user()->name }}</span>
                <form action="{{ route('auth.logout') }}" method="POST" style="margin:0">
                    @csrf
                    <button type="submit" class="btn">Log out</button>
                </form>
            @else
                <span class="account__who">Guest</span>
                <a class="btn btn--primary" href="{{ route('login') }}">Log in / Sign up</a>
            @endauth
        </div>
    </header>

    <main class="container">
        @yield('content')
    </main>

    <footer class="site-footer">
        <small>© <span id="year"></span> XentroCine</small>
    </footer>

    @stack('scripts')
    
    <script>
        // Mobile menu toggle
        const mobileToggle = document.getElementById('mobileMenuToggle');
        const siteHeader = document.querySelector('.site-header');
        const nav = document.querySelector('.nav');
        
        if(mobileToggle && nav){
            const closeMenu = () => {
                siteHeader.classList.remove('menu-open');
                mobileToggle.classList.remove('mobile-toggle--open');
                mobileToggle.setAttribute('aria-expanded', 'false');
                document.body.style.overflow = '';
            };

            const openMenu = () => {
                siteHeader.classList.add('menu-open');
                mobileToggle.classList.add('mobile-toggle--open');
                mobileToggle.setAttribute('aria-expanded', 'true');
                document.body.style.overflow = 'hidden';
            };

            mobileToggle.addEventListener('click', () => {
                const isOpen = siteHeader.classList.contains('menu-open');
                if(isOpen){
                    closeMenu();
                }else{
                    openMenu();
                }
            });
            
            // Close nav when clicking backdrop or close button
            nav.addEventListener('click', (e) => {
                // Close if clicking on nav itself (backdrop via ::after) or the X (::before)
                if(e.target === nav || e.target.classList.contains('nav')){
                    closeMenu();
                }
            });

            // Close after selecting a link on mobile
            nav.querySelectorAll('.nav__link').forEach(link => {
                link.addEventListener('click', () => {
                    if(window.innerWidth <= 720 && siteHeader.classList.contains('menu-open')){
                        closeMenu();
                    }
                });
            });

            // Close on Escape
            document.addEventListener('keydown', (event) => {
                if(event.key === 'Escape' && siteHeader.classList.contains('menu-open')){
                    closeMenu();
                }
            });
        }
        
        // Mobile account menu toggle
        const accountToggle = document.getElementById('mobileAccountToggle');
        const accountMenu = document.getElementById('mobileAccountMenu');
        if(accountToggle && accountMenu){
            accountToggle.addEventListener('click', (e) => {
                e.stopPropagation();
                const isOpen = accountMenu.style.display === 'block';
                accountMenu.style.display = isOpen ? 'none' : 'block';
                accountToggle.setAttribute('aria-expanded', !isOpen);
            });
            
            // Close menu when clicking outside
            document.addEventListener('click', (e) => {
                if(!accountToggle.contains(e.target) && !accountMenu.contains(e.target)){
                    accountMenu.style.display = 'none';
                    accountToggle.setAttribute('aria-expanded', 'false');
                }
            });
        }
        
        // Set current year
        const yearEl = document.getElementById('year');
        if(yearEl) yearEl.textContent = new Date().getFullYear();
    </script>
</body>

</html>
