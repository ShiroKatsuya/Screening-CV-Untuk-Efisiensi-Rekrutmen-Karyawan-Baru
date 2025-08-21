@extends('layouts.app')

@section('title', 'Upload CV')

@section('content')
<div class="max-w-3xl mx-auto" data-animate>
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-2xl font-semibold tracking-tight">Upload CV</h1>
        <a href="{{ route('candidates.index') }}" class="text-sky-700 hover:underline text-sm">Kembali</a>
    </div>
    <form action="{{ route('candidates.store') }}" method="post" enctype="multipart/form-data" class="bg-white dark:bg-slate-900 rounded-xl ring-1 ring-slate-200 dark:ring-slate-800 shadow-sm p-6 space-y-5">
        @csrf
        <div class="grid md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm mb-1">Nama</label>
                <input name="name" value="{{ old('name') }}" class="w-full rounded-lg border border-slate-300 dark:border-slate-700 focus:ring-2 focus:ring-sky-500/50 focus:border-sky-500 py-2.5 px-3 bg-white dark:bg-slate-900 dark:text-slate-100 shadow-sm" required />
            </div>
            <div>
                <label class="block text-sm mb-1">Email</label>
                <input name="email" type="email" value="{{ old('email') }}" class="w-full rounded-lg border border-slate-300 dark:border-slate-700 focus:ring-2 focus:ring-sky-500/50 focus:border-sky-500 py-2.5 px-3 bg-white dark:bg-slate-900 dark:text-slate-100 shadow-sm" />
            </div>
            <div>
                <label class="block text-sm mb-1">Telepon</label>
                <input name="phone" value="{{ old('phone') }}" class="w-full rounded-lg border border-slate-300 dark:border-slate-700 focus:ring-2 focus:ring-sky-500/50 focus:border-sky-500 py-2.5 px-3 bg-white dark:bg-slate-900 dark:text-slate-100 shadow-sm" />
            </div>
            <div>
                <label class="block text-sm mb-1">Posisi Dilamar</label>
                <input name="position_applied" value="{{ old('position_applied') }}" class="w-full rounded-lg border border-slate-300 dark:border-slate-700 focus:ring-2 focus:ring-sky-500/50 focus:border-sky-500 py-2.5 px-3 bg-white dark:bg-slate-900 dark:text-slate-100 shadow-sm" required />
            </div>
        </div>

        <div>
            <label class="block text-sm mb-1">Skill (pisahkan dengan koma)</label>
            <textarea name="skills" rows="2" class="w-full rounded-lg border border-slate-300 dark:border-slate-700 focus:ring-2 focus:ring-sky-500/50 focus:border-sky-500 py-2.5 px-3 bg-white dark:bg-slate-900 dark:text-slate-100 shadow-sm">{{ old('skills') }}</textarea>
        </div>

        <div class="grid md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm mb-1">Pengalaman (tahun)</label>
                <input name="years_experience" type="number" min="0" value="{{ old('years_experience', 0) }}" class="w-full rounded-lg border border-slate-300 dark:border-slate-700 focus:ring-2 focus:ring-sky-500/50 focus:border-sky-500 py-2.5 px-3 bg-white dark:bg-slate-900 dark:text-slate-100 shadow-sm" />
            </div>
            <div>
                <label class="block text-sm mb-1">Pendidikan</label>
                <input name="education_level" value="{{ old('education_level') }}" class="w-full rounded-lg border border-slate-300 dark:border-slate-700 focus:ring-2 focus:ring-sky-500/50 focus:border-sky-500 py-2.5 px-3 bg-white dark:bg-slate-900 dark:text-slate-100 shadow-sm" />
            </div>
        </div>

        <div>
            <label class="block text-sm mb-1">File CV (PDF/DOCX)</label>
            <input id="cvFileInput" name="cv_file" type="file" accept=".pdf,.doc,.docx" class="w-full rounded-lg border border-slate-300 dark:border-slate-700 focus:ring-2 focus:ring-sky-500/50 focus:border-sky-500 py-2 px-2 bg-white dark:bg-slate-900 dark:text-slate-100 shadow-sm" required />
            <p id="cvFileHint" class="text-xs text-slate-500 mt-1">Maks 5 MB</p>
        </div>

        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('candidates.index') }}" class="px-4 py-2 rounded-lg ring-1 ring-slate-200 dark:ring-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800/50">Batal</a>
            <button class="px-4 py-2 rounded-lg bg-sky-600 text-white shadow hover:bg-sky-700 active:bg-sky-800">Upload & Analisis</button>
        </div>
    </form>
</div>
@endsection


