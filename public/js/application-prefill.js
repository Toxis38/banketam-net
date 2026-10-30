// Prefill application form from URL params (from occupancy plan)
(function() {
  function applyPrefill() {
    const params = new URLSearchParams(window.location.search);
    const room = params.get('room');
    const date = params.get('date');
    const tables = params.get('tables');

    if (room) {
      const sel = document.getElementById('room_type');
      if (sel) {
        sel.value = room;
        sel.dispatchEvent(new Event('change'));
      }
    }
    if (date) {
      const dateInput = document.getElementById('banquet_date');
      if (dateInput) dateInput.value = date;
    }
    if (tables) {
      const nums = tables.split(',').map(n => parseInt(n, 10)).filter(n => !isNaN(n));
      // Wait a bit for tables to render
      setTimeout(() => {
        if (typeof selectedTables !== 'undefined') {
          nums.forEach(n => selectedTables.add(n));
          document.querySelectorAll('.table-btn').forEach(btn => {
            const num = parseInt(btn.dataset.num, 10);
            if (nums.includes(num) && !btn.classList.contains('busy')) {
              btn.classList.add('selected');
            }
          });
        }
      }, 400);
    }
    if (typeof updatePrice === 'function') setTimeout(updatePrice, 500);
  }

  // Run after rooms are loaded — poll briefly
  let tries = 0;
  const t = setInterval(() => {
    tries++;
    const sel = document.getElementById('room_type');
    if (sel && sel.options.length > 1) {
      clearInterval(t);
      applyPrefill();
    }
    if (tries > 30) clearInterval(t);
  }, 100);
})();
