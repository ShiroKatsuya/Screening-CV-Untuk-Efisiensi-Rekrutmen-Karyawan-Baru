@extends('layouts.app')

@section('title', 'Dashboard Kandidat')

@section('content')
<div class="flex items-center justify-between mb-6" data-animate>
    <div>
        <h1 class="text-2xl md:text-3xl font-semibold tracking-tight">Dashboard Kandidat</h1>
        <p class="text-sm text-slate-600 dark:text-slate-300">Pantau hasil penilaian CV</p>
        <!-- <span class=" text-slate-500 tooltip" data-tip="Cari berdasarkan nama kandidat atau posisi yang dilamar.">ⓘ</span> -->
    </div>
    <a href="{{ route('candidates.create') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-md bg-sky-600 text-white shadow hover:bg-sky-700 active:bg-sky-800 transition">
        <svg viewBox="0 0 24 24" fill="none" class="w-4 h-4"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
        <span>Upload CV</span>
    </a>
    </div>

<form method="get" class="mb-6" data-animate>
    <div class="flex gap-2">
        <div class="flex-1 relative">
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari nama / posisi" class="w-full rounded-lg border border-slate-300 dark:border-slate-700 focus:ring-2 focus:ring-sky-500/50 focus:border-sky-500 pr-10 py-2.5 px-3 bg-white dark:bg-slate-900 dark:text-slate-100 shadow-sm" />
            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400">⌕</span>
        </div>
        <button class="px-4 rounded-lg bg-slate-900 text-white dark:bg-slate-800 shadow hover:bg-slate-800">Cari</button>
    </div>
    <p class="text-xs text-slate-600 dark:text-slate-400 mt-2">Total: {{ $candidates->total() }} kandidat</p>
 </form>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <div class="bg-white dark:bg-slate-900 rounded-xl ring-1 ring-slate-200 dark:ring-slate-800 p-4 shadow-sm" data-animate>
        <div class="flex items-center justify-between mb-2">
            <h2 class="font-medium">Distribusi Skor</h2>
            <button type="button" onclick="openModal('modal-scoring')" class="text-sm px-3 py-1.5 rounded-md bg-sky-50 text-sky-700 ring-1 ring-sky-200 hover:bg-sky-100 dark:bg-sky-900/30 dark:text-sky-300 dark:ring-sky-800">Tentang Penilaian</button>
        </div>
        <canvas id="scoreChart" height="120"></canvas>
    </div>
    <div class="bg-white dark:bg-slate-900 rounded-xl ring-1 ring-slate-200 dark:ring-slate-800 p-4 shadow-sm" data-animate>
        <h2 class="font-medium mb-2">Ringkasan</h2>
        <ul class="text-sm space-y-1">
            <li><strong>Rata-rata Skor:</strong> {{ number_format($candidates->avg('score') ?? 0, 2) }}</li>
            <li><strong>Posisi Teratas:</strong> {{ $candidates->first()?->position_applied ?? '-' }}</li>
        </ul>
    </div>
    <div class="md:col-span-2 bg-white dark:bg-slate-900 rounded-xl ring-1 ring-slate-200 dark:ring-slate-800 overflow-hidden" data-animate>
        <table class="min-w-full text-sm">
            <thead class="bg-slate-100/70 dark:bg-slate-800/50">
                <tr>
                    <th class="px-3 py-2 text-left">Nama</th>
                    <th class="px-3 py-2 text-left">Posisi</th>
                    <th class="px-3 py-2 text-left">Pengalaman</th>
                    <th class="px-3 py-2 text-left">Skor</th>
                    <th class="px-3 py-2 text-left">Rekomendasi</th>
                    <th class="px-3 py-2"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($candidates as $c)
                <tr class="border-t border-slate-100 dark:border-slate-800 hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors">
                    <td class="px-3 py-2">{{ $c->name }}</td>
                    <td class="px-3 py-2">{{ $c->position_applied }}</td>
                    <td class="px-3 py-2">{{ $c->years_experience }} th</td>
                    <td class="px-3 py-2 font-medium">
                        @if($c->score)
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold
                                {{ $c->score >= 80 ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200' : ($c->score >= 60 ? 'bg-amber-50 text-amber-700 ring-1 ring-amber-200' : 'bg-rose-50 text-rose-700 ring-1 ring-rose-200') }}">
                                {{ number_format($c->score,2) }}
                            </span>
                        @else
                            -
                        @endif
                    </td>
                    <td class="px-3 py-2 line-clamp-1">{{ $c->recommendation ?? '-' }}</td>
                    <td class="px-3 py-2 text-right">
                        <a href="{{ route('candidates.show', $c) }}" class="inline-flex items-center gap-1 text-sky-700 hover:underline">Detail
                            <svg viewBox="0 0 24 24" fill="none" class="w-4 h-4"><path d="M9 6l6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-3 py-6 text-center text-slate-500">Belum ada data kandidat.</td></tr>
                @endforelse
            </tbody>
        </table>

        <div class="p-3">{{ $candidates->links() }}</div>
    </div>
    
</div>

<div id="modal-scoring" class="fixed inset-0 hidden">
  <div class="absolute inset-0 bg-black/50" onclick="closeModal('modal-scoring')"></div>
  <div class="relative max-w-lg mx-auto mt-24 bg-white dark:bg-slate-900 rounded-xl ring-1 ring-slate-200 dark:ring-slate-800 shadow-xl p-6 animate-[scaleIn_.25s_ease]" data-animate>
    <div class="flex items-center justify-between mb-3">
      <h3 class="text-lg font-semibold">Tentang Penilaian Skor</h3>
      <button class="text-slate-500 hover:text-slate-700 dark:hover:text-slate-300" onclick="closeModal('modal-scoring')">✕</button>
    </div>
    <p class="text-sm text-slate-600 dark:text-slate-300 mb-2">Skor dihitung oleh layanan ML menggunakan Random Forest Regression berdasarkan:</p>
    <ul class="list-disc list-inside text-sm text-slate-700 dark:text-slate-200 space-y-1">
      <li>Kepadatan kata kunci teknis (Python, JavaScript, SQL, NLP, ML) dalam teks CV</li>
      <li>Lama pengalaman kerja</li>
      <li>Tingkat pendidikan</li>
      <li>Kesesuaian kata kunci dengan posisi yang dilamar</li>
      <li>Panjang dan kualitas tokenisasi teks CV</li>
    </ul>
  </div>
</div>

@push('scripts')
<script>
const scores = @json($candidates->pluck('score')->filter()->values());
if (window.Chart) {
  new Chart(document.getElementById('scoreChart'), {
    type: 'bar',
    data: { labels: scores.map((_,i)=>`K${i+1}`), datasets: [{ label: 'Skor', data: scores, backgroundColor: 'rgba(14,165,233,0.5)', borderColor: 'rgb(14,165,233)', borderWidth: 1 }] },
    options: { scales: { y: { beginAtZero: true, max: 100 } }, plugins: { legend: { display: false } } }
  });
}
</script>
@endpush
@endsection


