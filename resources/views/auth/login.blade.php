<x-layout title="Masuk Pustakawan">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card shadow-sm"><div class="card-body p-4">
                <h1 class="h4 mb-3">Masuk Pustakawan</h1>
                <form action="{{ route('login.attempt') }}" method="POST">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email"
                               value="{{ old('email', request()->cookie('del_last_user_email')) }}"
                               class="form-control @error('email') is-invalid @enderror">
                        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Kata Sandi</label>
                        <input type="password" name="password"
                               class="form-control @error('password') is-invalid @enderror">
                        @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="form-check mb-3">
                        <input type="checkbox" name="remember" value="1" class="form-check-input" id="remember">
                        <label class="form-check-label" for="remember">Ingat saya</label>
                    </div>

                    <button class="btn btn-del w-100">Masuk</button>
                </form>
            </div></div>
        </div>
    </div>
</x-layout>