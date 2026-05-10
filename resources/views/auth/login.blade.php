<x-guest-layout>
    <div class="auth-card card">
        {{-- Header --}}
        <div class="card-header">
            <div class="brand-logo">
                <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" fill="none" viewBox="0 0 24 24">
                    <path fill="#fff"
                        d="M12 2a10 10 0 1 0 0 20A10 10 0 0 0 12 2Zm0 3a3 3 0 1 1 0 6 3 3 0 0 1 0-6Zm0 14.2a7.2 7.2 0 0 1-6-3.22c.03-1.99 4-3.08 6-3.08s5.97 1.09 6 3.08A7.2 7.2 0 0 1 12 19.2Z" />
                </svg>
            </div>
            <h4>Selamat Datang</h4>
            <p>Masuk ke akun Anda untuk melanjutkan</p>
        </div>

        {{-- Body --}}
        <div class="card-body">
            @if (session('status'))
                <div class="alert alert-success alert-dismissible fade show py-2 mb-3" role="alert">
                    {{ session('status') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf

                {{-- Email --}}
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input id="email" type="email" name="email"
                        class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" required
                        autofocus autocomplete="username" placeholder="nama@email.com">
                    @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Password --}}
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label for="password" class="form-label mb-0">Password</label>
                        @if (Route::has('password.request'))
                            <a href="{{ route('password.request') }}" class="text-decoration-none small"
                                style="color:#435ebe;font-size:.8rem">
                                Lupa password?
                            </a>
                        @endif
                    </div>
                    <input id="password" type="password" name="password"
                        class="form-control @error('password') is-invalid @enderror" required
                        autocomplete="current-password" placeholder="••••••••">
                    @error('password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Remember Me --}}
                <div class="mb-4">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="remember_me" name="remember">
                        <label class="form-check-label small text-muted" for="remember_me">
                            Ingat saya
                        </label>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-auth w-100">
                    Masuk
                </button>
            </form>
        </div>

        {{-- Footer --}}
        @if (Route::has('register'))
            <div class="card-footer">
                <span class="text-muted">Belum punya akun?</span>
                <a href="{{ route('register') }}" class="ms-1">Daftar sekarang</a>
            </div>
        @endif
    </div>
</x-guest-layout>
