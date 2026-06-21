/* ============================================================
   Crochet Craft – Main JavaScript
   ============================================================ */

document.addEventListener('DOMContentLoaded', () => {

  // ── Mobile nav toggle ──────────────────────────────────
  const hamburger = document.getElementById('hamburger');
  const navLinks  = document.querySelector('.nav-links');

  if (hamburger && navLinks) {
    hamburger.addEventListener('click', () => {
      navLinks.classList.toggle('open');
    });
    // close on outside click
    document.addEventListener('click', (e) => {
      if (!hamburger.contains(e.target) && !navLinks.contains(e.target)) {
        navLinks.classList.remove('open');
      }
    });
  }

  // ── Search overlay ─────────────────────────────────────
  const searchToggle  = document.getElementById('searchToggle');
  const searchOverlay = document.getElementById('searchOverlay');
  const searchClose   = document.getElementById('searchClose');

  if (searchToggle && searchOverlay) {
    searchToggle.addEventListener('click', () => {
      searchOverlay.classList.add('active');
      searchOverlay.querySelector('input')?.focus();
    });
    searchClose?.addEventListener('click', () => searchOverlay.classList.remove('active'));
    searchOverlay.addEventListener('click', (e) => {
      if (e.target === searchOverlay) searchOverlay.classList.remove('active');
    });

    // Search submits to browse page
    searchOverlay.querySelector('form')?.addEventListener('submit', (e) => {
      // form action handles it; just close overlay
    });
  }

  // ── Role picker (register page) ────────────────────────
  document.querySelectorAll('.role-opt').forEach(opt => {
    opt.addEventListener('click', () => {
      document.querySelectorAll('.role-opt').forEach(o => o.classList.remove('active'));
      opt.classList.add('active');
      const inp = opt.querySelector('input[type=radio]');
      if (inp) inp.checked = true;
    });
  });

  // ── Image upload preview ────────────────────────────────
  const fileInput = document.getElementById('productImage');
  const preview   = document.getElementById('imagePreview');

  if (fileInput && preview) {
    fileInput.addEventListener('change', () => {
      const file = fileInput.files[0];
      if (file && file.type.startsWith('image/')) {
        const reader = new FileReader();
        reader.onload = (e) => {
          preview.src = e.target.result;
          preview.style.display = 'block';
        };
        reader.readAsDataURL(file);
      }
    });
  }

  // ── Cart quantity controls (AJAX) ──────────────────────
  document.querySelectorAll('.qty-btn').forEach(btn => {
    btn.addEventListener('click', async () => {
      const cartId = btn.dataset.cartId;
      const action  = btn.dataset.action; // 'inc' | 'dec'
      const qtyEl   = document.getElementById(`qty-${cartId}`);
      if (!qtyEl) return;

      let qty = parseInt(qtyEl.textContent, 10);
      if (action === 'dec' && qty <= 1) return;

      qty = action === 'inc' ? qty + 1 : qty - 1;
      qtyEl.textContent = qty;

      try {
        const res  = await fetch(`/crochet_craft/user/cart_api.php`, {
          method:  'POST',
          headers: { 'Content-Type': 'application/json' },
          body:    JSON.stringify({ cart_id: cartId, quantity: qty }),
        });
        const data = await res.json();
        if (data.subtotal !== undefined) {
          const subEl = document.getElementById(`sub-${cartId}`);
          if (subEl) subEl.textContent = 'NPR ' + data.subtotal.toLocaleString();
        }
        if (data.total !== undefined) {
          const totEl = document.getElementById('cart-total');
          if (totEl) totEl.textContent = 'NPR ' + data.total.toLocaleString();
          const badgeEl = document.getElementById('cart-count');
          if (badgeEl) badgeEl.textContent = data.cart_count;
        }
      } catch (err) {
        console.error('Cart update failed', err);
        // revert on failure
        qtyEl.textContent = action === 'inc' ? qty - 1 : qty + 1;
      }
    });
  });

  // ── Delete confirmation dialogs ────────────────────────
  document.querySelectorAll('[data-confirm]').forEach(el => {
    el.addEventListener('click', (e) => {
      const msg = el.dataset.confirm || 'Are you sure?';
      if (!confirm(msg)) e.preventDefault();
    });
  });

  // ── Auto-dismiss alerts ────────────────────────────────
  document.querySelectorAll('.alert[data-auto-dismiss]').forEach(alert => {
    setTimeout(() => {
      alert.style.transition = 'opacity .4s';
      alert.style.opacity = '0';
      setTimeout(() => alert.remove(), 400);
    }, 4000);
  });

  // ── Flash message from URL param ──────────────────────
  const params = new URLSearchParams(window.location.search);
  if (params.get('success')) {
    // handled server-side; this is a fallback
  }

  // ── Sticky table header scroll hint ───────────────────
  const tableWraps = document.querySelectorAll('.table-wrap');
  tableWraps.forEach(wrap => {
    if (wrap.scrollWidth > wrap.clientWidth) {
      const hint = document.createElement('p');
      hint.textContent = '← Scroll to see more →';
      hint.style.cssText = 'font-size:12px;color:#999;text-align:center;padding:6px 0;';
      wrap.after(hint);
    }
  });

});
