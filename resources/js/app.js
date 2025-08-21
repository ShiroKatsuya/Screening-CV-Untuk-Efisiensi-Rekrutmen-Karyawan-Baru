import './bootstrap';
import Chart from 'chart.js/auto';
window.Chart = Chart;

// Enhance file input hint with selected filename
document.addEventListener('DOMContentLoaded', () => {
  const input = document.getElementById('cvFileInput');
  const hint = document.getElementById('cvFileHint');
  if (input && hint) {
    input.addEventListener('change', () => {
      const file = input.files && input.files[0];
      if (file) {
        const sizeMb = (file.size / (1024 * 1024)).toFixed(2);
        hint.textContent = `${file.name} • ${sizeMb} MB`;
      } else {
        hint.textContent = 'Maks 5 MB';
      }
    });
  }
});
