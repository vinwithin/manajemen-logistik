<x-guest-layout>
    <div class="auth-card card">
        {{-- Header --}}
        <div class="card-header">
            <div class="brand-logo">
                <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" fill="none" viewBox="0 0 24 24">
                    <path fill="#fff"
                        d="M15 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4Zm-9-2V7H4v3H1v2h3v3h2v-3h3v-2H6Zm9 4c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4Z" />
                </svg>
            </div>
            <h4>Buat Akun Baru</h4>
            <p>Isi data di bawah untuk mendaftar</p>
        </div>

        {{-- Body --}}
        <div class="card-body">
            <form method="POST" action="{{ route('register') }}">
                @csrf

                {{-- Name --}}
                <div class="mb-3">
                    <label for="name" class="form-label">Nama Lengkap</label>
                    <input id="name" type="text" name="name"
                        class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required
                        autofocus autocomplete="name" placeholder="Nama lengkap Anda">
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Email --}}
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input id="email" type="email" name="email"
                        class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" required
                        autocomplete="username" placeholder="nama@email.com">
                    @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Password --}}
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input id="password" type="password" name="password"
                        class="form-control @error('password') is-invalid @enderror" required
                        autocomplete="new-password" placeholder="Min. 8 karakter">
                    @error('password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Confirm Password --}}
                <div class="mb-4">
                    <label for="password_confirmation" class="form-label">Konfirmasi Password</label>
                    <input id="password_confirmation" type="password" name="password_confirmation"
                        class="form-control @error('password_confirmation') is-invalid @enderror" required
                        autocomplete="new-password" placeholder="Ulangi password">
                    @error('password_confirmation')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <button type="submit" class="btn btn-primary btn-auth w-100">
                    Daftar
                </button>
            </form>
        </div>

        {{-- Footer --}}
        <div class="card-footer">
            <span class="text-muted">Sudah punya akun?</span>
            <a href="{{ route('login') }}" class="ms-1">Masuk di sini</a>
        </div>
    </div>
</x-guest-layout>
