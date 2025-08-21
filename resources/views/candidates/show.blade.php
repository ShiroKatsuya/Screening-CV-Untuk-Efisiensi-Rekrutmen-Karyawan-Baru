@extends('layouts.app')

@section('title', 'Detail Kandidat')

@section('content')
<div class="max-w-5xl mx-auto grid md:grid-cols-3 gap-6">
    <div class="md:col-span-2 space-y-4" data-animate>
        <div class="bg-white dark:bg-slate-900 rounded-xl ring-1 ring-slate-200 dark:ring-slate-800 p-4 shadow-sm">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h1 class="text-xl font-semibold mb-1">{{ $candidate->name }}</h1>
                    <p class="text-sm text-slate-600 dark:text-slate-300">Posisi: {{ $candidate->position_applied }} • Pengalaman: {{ $candidate->years_experience }} th • Pendidikan: {{ $candidate->education_level ?? '-' }}</p>
                </div>
                <a href="{{ route('candidates.index') }}" class="text-sm text-sky-700 hover:underline">Kembali</a>
            </div>
        </div>
        <div class="bg-white dark:bg-slate-900 rounded-xl ring-1 ring-slate-200 dark:ring-slate-800 p-4 shadow-sm">
            <h2 class="font-medium mb-2">Teks CV (parsial)</h2>
            <div class="prose max-w-none text-sm whitespace-pre-wrap">{{ \Illuminate\Support\Str::limit($candidate->cv_text, 4000) }}</div>
            <a target="_blank" href="{{ asset('storage/'.$candidate->cv_file_path) }}" class="text-sky-700 hover:underline text-sm mt-2 inline-block">Unduh CV</a>
        </div>
    </div>
    <div class="space-y-4" data-animate>
        <div class="bg-white dark:bg-slate-900 rounded-xl ring-1 ring-slate-200 dark:ring-slate-800 p-4 shadow-sm">
            <h2 class="font-medium mb-2">Skor & Rekomendasi</h2>
            <p class="text-3xl font-bold">{{ $candidate->score ? number_format($candidate->score,2) : '-' }}</p>
            <p class="text-sm text-slate-600 dark:text-slate-300">{{ $candidate->recommendation ?? 'Tidak ada rekomendasi.' }}</p>
        </div>
        <div class="bg-white dark:bg-slate-900 rounded-xl ring-1 ring-slate-200 dark:ring-slate-800 p-4 shadow-sm">
            <h2 class="font-medium mb-2">Fitur</h2>
            <ul class="text-sm space-y-1">
                @foreach(($candidate->features ?? []) as $k => $v)
                    <li><strong>{{ $k }}</strong>: {{ is_array($v) ? json_encode($v) : $v }}</li>
                @endforeach
            </ul>
        </div>
    </div>
</div>
@endsection


