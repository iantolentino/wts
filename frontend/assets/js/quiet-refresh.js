(() => {
  const interval = 300000;
  let lastUpdate = Date.now();
  let running = false;
  async function refresh() {
    if (document.hidden || running || Date.now() - lastUpdate < interval) return;
    const dashboard = document.querySelector('.dashboard-canvas');
    const tickets = document.querySelector('.tickets-screen .ticket-table-panel');
    const target = dashboard || tickets;
    if (!target || target.contains(document.activeElement)) return;
    running = true;
    try {
      const response = await fetch(location.href, { credentials: 'same-origin', cache: 'no-store' });
      if (!response.ok || response.redirected) return;
      const page = new DOMParser().parseFromString(await response.text(), 'text/html');
      const replacement = page.querySelector(dashboard ? '.dashboard-canvas' : '.tickets-screen .ticket-table-panel');
      if (!replacement || document.hidden || target.contains(document.activeElement)) return;
      if (dashboard) {
        const oldDetails = dashboard.querySelector('details');
        const newDetails = replacement.querySelector('details');
        if (oldDetails && newDetails) newDetails.open = oldDetails.open;
      } else {
        const oldWrap = target.querySelector('.table-wrap');
        const newWrap = replacement.querySelector('.table-wrap');
        if (oldWrap && newWrap) newWrap.scrollLeft = oldWrap.scrollLeft;
      }
      target.replaceWith(replacement);
      lastUpdate = Date.now();
    } catch (_) { /* Keep the current view and retry later. */ }
    finally { running = false; }
  }
  setInterval(refresh, interval);
  document.addEventListener('visibilitychange', refresh);
})();
