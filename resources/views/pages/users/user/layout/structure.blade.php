{{-- resources/views/pages/users/user/layout/structure.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>@yield('title','Dashboard')</title>

  <!-- Bootstrap 5 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"/>

  <!-- Icons -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet"/>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.5/font/bootstrap-icons.min.css" rel="stylesheet"/>

  <!-- SweetAlert2 -->
  <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet"/>

  <!-- Layout styles -->
  <link rel="stylesheet" href="{{ asset('assets/css/common/main.css') }}">
  <link rel="stylesheet" href="{{ asset('/assets/css/pages/layout/structure.css') }}">

  <!-- Optional runtime theme stylesheet hook (kept for future) -->
  <link id="dynamicTheme" rel="stylesheet" href=""/>

  @stack('styles')

  <style>

    /* lock scroll while overlay is shown */
    body.classmai-mastructure--locked{
      overflow: hidden !important;
      height: 100vh;
    }

    .classmai-mastructureOverlay{
      position: fixed;
      inset: 0;
      z-index: 2147483000; /* crazy high so no one covers it */
      display: grid;
      place-items: center;
      background:
        radial-gradient(1300px 700px at 20% 10%, rgba(255,255,255,0.92), rgba(255,255,255,0.98)),
        linear-gradient(135deg, rgba(158,54,58,0.12), rgba(32,58,67,0.08));
      transition: opacity .35s ease, visibility .35s ease;
      opacity: 1;
      visibility: visible;
    }
    .classmai-mastructureOverlay.is-hidden{
      opacity: 0;
      visibility: hidden;
      pointer-events: none;
    }

    .classmai-mastructureOverlay__card{
      background: #fff;
      border-radius: 1.25rem;
      padding: 1.5rem 1.75rem;
      box-shadow: 0 12px 40px rgba(0,0,0,0.08);
      width: min(92vw, 520px);
      text-align: center;
      border: 1px solid rgba(0,0,0,0.06);
    }
    .classmai-mastructureOverlay__brand{
      display:flex; align-items:center; justify-content:center;
      gap:.75rem; margin-bottom:.75rem;
      color: var(--overlay-ink);
      font-weight: 700; letter-spacing:.2px;
    }
    .classmai-mastructureOverlay__spinner{
      width: 64px; height: 64px; margin: .5rem auto 1rem;
      border-radius: 50%;
      border: 4px solid rgba(0,0,0,0.08);
      border-top-color: var(--primary-color);
      animation: classmai-rot 1s linear infinite;
    }
    @keyframes classmai-rot{ to{ transform: rotate(360deg);} }

    .classmai-mastructureOverlay__bar{
      height: 6px; width: 100%;
      background: rgba(0,0,0,0.06);
      border-radius: 999px;
      overflow: hidden; margin: .25rem 0 1rem;
    }
    .classmai-mastructureOverlay__bar > i{
      display:block; height:100%; width:35%;
      background: linear-gradient(90deg, var(--primary-color), rgba(158,54,58,.6));
      border-radius: 999px;
      animation: classmai-load 1.4s ease-in-out infinite;
    }
    @keyframes classmai-load{
      0%{ transform: translateX(-100%); }
      50%{ transform: translateX(15%); }
      100%{ transform: translateX(180%); }
    }

    .classmai-mastructureOverlay__note{
      font-size:.925rem; color:#495057; line-height:1.35;
    }
    .classmai-mastructureOverlay__hint{
      margin-top:.5rem; font-size:.8rem; color:#6c757d;
    }
  </style>
</head>
<body class="classmai-mastructure--locked">
  <!-- ===== UNIQUE OVERLAY ===== -->
  <div class="classmai-mastructureOverlay" id="classmai-mastructureOverlay" aria-live="polite">
    <div class="classmai-mastructureOverlay__card">
      <div class="classmai-mastructureOverlay__brand">
        <svg width="22" height="22" viewBox="0 0 24 24" aria-hidden="true">
          <circle cx="12" cy="12" r="10" fill="currentColor" style="opacity:.12"></circle>
          <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
        </svg>
        <span>Loading your workspace theme…</span>
      </div>
      <div class="classmai-mastructureOverlay__spinner" role="status" aria-label="Loading"></div>
      <div class="classmai-mastructureOverlay__bar"><i></i></div>
      <div class="classmai-mastructureOverlay__note">
        Applying colors, fonts, and assets for your account.
      </div>
      <div class="classmai-mastructureOverlay__hint">
        This is quick—just polishing things up.
      </div>
    </div>
  </div>
  <!-- ===== END UNIQUE OVERLAY ===== -->

  <div class="layout">
    <aside class="dashboard-sidebar" id="sidebar">
      <button id="closeSidebar" class="close-sidebar">
        <i class="bi bi-x-lg"></i>
      </button>

      <div class="sidebar-logo text-center">
        <img id="sidebarLogo" src="{{ asset('assets/media/web_assets/logo1.png') }}" alt="Logo"/>
      </div>

      <nav class="sidebar-nav flex-grow-1">
        <a href="/user/dashboard" class="nav-link">
          <i class="fas fa-home"></i><span>Dashboard</span>
        </a>
        <a href="/user/list/manage" class="nav-link">
          <i class="fas fa-list-ul"></i>
          <span>List</span>
        </a>
        <a href="/user/template/manage" class="nav-link">
          <i class="fas fa-envelope-open-text"></i>
          <span>Template</span>
        </a>

        <div class="nav-group">
          <a href="#" class="nav-link group-toggle" data-target="campaignMenu">
            <i class="fa-solid fa-bullhorn"></i><span>Campaign</span>
            <i class="fas fa-chevron-down ms-auto"></i>
          </a>
          <div id="campaignMenu" class="submenu">
            <a href="/user/campaign/create" class="nav-link"><span>Campaign</span></a>
            <a href="/user/campaign/view" class="nav-link"><span>View Campaign</span></a>
          </div>
        </div>

        <a href="/campaigns/report/all" class="nav-link">
          <i class="fas fa-chart-line"></i>
          <span>Report</span>
        </a>

        <div class="nav-group">
          <a href="#" class="nav-link group-toggle" data-target="settingsMenu">
            <i class="fa-solid fa-gear"></i><span>Settings</span>
            <i class="fas fa-chevron-down ms-auto"></i>
          </a>
          <div id="settingsMenu" class="submenu">
            <a href="/mailer/manage" class="nav-link"><span>SMTP</span></a>
            <a href="/user/profile/manage" class="nav-link"><span>Profile</span></a>
            <a href="/media" class="nav-link"><span>Media</span></a>
            {{-- <a href="/theme" class="nav-link"><span>Theme</span></a> --}}
            <a href="/plans" class="nav-link"><span>Plan</span></a>
          </div>
        </div>
      </nav>

      <div class="sidebar-auth p-3">
        <a href="#" id="logoutBtnSidebar" class="auth-link">
          <i class="fas fa-sign-out-alt me-2"></i><span>Logout</span>
        </a>
      </div>
    </aside>

    <div id="sidebarOverlay" class="sidebar-overlay"></div>

    <div class="right-panel" id="rightPanel">
      <header class="admin-header d-flex align-items-center px-3 shadow-sm">
        <button class="btn btn-link d-block d-lg-none p-0 me-2" id="toggleSidebar">
          <i class="bi bi-list fs-3"></i>
        </button>

        <div class="ms-auto dropdown">
          <button class="btn btn-link text-decoration-none p-0" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="fa-solid fa-user-circle fa-2x text-dark"></i>
          </button>
          <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
            <li><a class="dropdown-item" href="/student/profile"><i class="fas fa-user me-2"></i>Profile</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="#" id="logoutBtnMobile"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
          </ul>
        </div>
      </header>

      <main class="main-content">
        @yield('content')
      </main>
    </div>
  </div>

  <!-- Core scripts -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  @stack('scripts')
  @yield('scripts')

  <script>
    // ————————————————
    // Helpers
    // ————————————————
    function getToken() {
      return sessionStorage.getItem('token') || localStorage.getItem('token');
    }

    function setVar(name, val) {
      if (val && typeof val === 'string' && val.trim() !== '') {
        document.documentElement.style.setProperty(name, val.trim());
      }
    }

    function applyTheme(theme) {
      if (!theme || typeof theme !== 'object') return { logoChanged:false };

      // App name → prefix page title once
      if (theme.app_name) {
        const baseTitle = document.title || 'Dashboard';
        if (!baseTitle.startsWith(theme.app_name)) {
          document.title = `${theme.app_name} — ${baseTitle}`;
        }
      }

      // CSS variables (kept flexible with your field names)
      setVar('--primary-color',   theme.primary_color);
      setVar('--secondary-color', theme.secondary_color);
      setVar('--accent-color',    theme.accent_color);
      setVar('--light-color',     theme.light_color);
      setVar('--border-color',    theme.border_color);
      setVar('--text-color',      theme.text_color);
      setVar('--bg-body',         theme.bg_body);

      setVar('--info-color',      theme.info_color);
      setVar('--success-color',   theme.success_color);
      setVar('--warning-color',   theme.warning_color);
      setVar('--danger-color',    theme.danger_color);

      // Optional: fonts (if your CSS uses these vars)
      setVar('--font-sans', theme.font_sans);
      setVar('--font-head', theme.font_head);

      // Logo swap if provided
      let logoChanged = false;
      if (theme.logo_url && typeof theme.logo_url === 'string' && theme.logo_url.trim() !== '') {
        const img = document.getElementById('sidebarLogo');
        if (img && img.src !== theme.logo_url.trim()) {
          img.src = theme.logo_url.trim();
          logoChanged = true;
        }
      }
      return { logoChanged };
    }

    function hideOverlay() {
      const overlay = document.getElementById('classmai-mastructureOverlay');
      overlay?.classList.add('is-hidden');
      document.body.classList.remove('classmai-mastructure--locked');
    }

    function waitForImageLoad(imgEl, timeoutMs = 3500) {
      return new Promise(resolve => {
        if (!imgEl) return resolve();
        if (imgEl.complete && imgEl.naturalWidth > 0) return resolve();
        const onDone = () => { cleanup(); resolve(); };
        const cleanup = () => {
          imgEl.removeEventListener('load', onDone);
          imgEl.removeEventListener('error', onDone);
        };
        imgEl.addEventListener('load', onDone, { once:true });
        imgEl.addEventListener('error', onDone, { once:true });
        setTimeout(onDone, timeoutMs);
      });
    }

    function abortableFetch(resource, options = {}, timeoutMs = 5000) {
      const controller = new AbortController();
      const id = setTimeout(() => controller.abort(), timeoutMs);
      return fetch(resource, { ...options, signal: controller.signal })
        .finally(() => clearTimeout(id));
    }

    async function fetchMyThemeAndApply() {
      const token = getToken();
      if (!token) {
        // No theme for guests → quick release
        setTimeout(hideOverlay, 400);
        return;
      }

      try {
        const res = await abortableFetch('/api/my-theme', {
          method: 'GET',
          headers: {
            'Accept': 'application/json',
            'Authorization': `Bearer ${token}`
          }
        }, 6500);

        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        const json = await res.json();
        console.log('[my-theme] response:', json);

        if (json?.data?.theme) {
          const { logoChanged } = applyTheme(json.data.theme);

          // wait a frame for CSS vars to paint
          await new Promise(r => requestAnimationFrame(() => requestAnimationFrame(r)));

          // if logo changed, wait for it to load (with timeout)
          if (logoChanged) {
            await waitForImageLoad(document.getElementById('sidebarLogo'), 3500);
          }

          hideOverlay();
        } else {
          console.info('[my-theme] No active theme assigned for this user.');
          hideOverlay();
        }
      } catch (err) {
        console.error('[my-theme] Fetch failed:', err);
        hideOverlay(); // fail open
      }
    }

    // ————————————————
    // Layout interactions
    // ————————————————
    document.addEventListener('DOMContentLoaded', () => {
      const sidebar   = document.getElementById('sidebar');
      const overlayBg = document.getElementById('sidebarOverlay');
      const rightPanel= document.getElementById('rightPanel');

      const openSidebar  = () => {
        sidebar.classList.add('active');
        overlayBg.classList.add('active');
        rightPanel.classList.add('shifted');
      };
      const closeSidebar = () => {
        sidebar.classList.remove('active');
        overlayBg.classList.remove('active');
        rightPanel.classList.remove('shifted');
      };

      document.getElementById('toggleSidebar')?.addEventListener('click', openSidebar);
      document.getElementById('closeSidebar')?.addEventListener('click', closeSidebar);
      overlayBg?.addEventListener('click', closeSidebar);

      // Submenu toggles
      document.querySelectorAll('.group-toggle').forEach(btn => {
        btn.addEventListener('click', e => {
          e.preventDefault();
          const menu = document.getElementById(btn.dataset.target);
          const icon = btn.querySelector('.fa-chevron-down, .fa-chevron-up');
          const isOpen = menu.classList.toggle('open');
          menu.style.display = isOpen ? 'flex' : 'none';
          icon.classList.toggle('fa-chevron-up', isOpen);
          icon.classList.toggle('fa-chevron-down', !isOpen);
        });
      });

      // Highlight active link
      const highlightActiveLink = () => {
        const path = window.location.pathname;
        document.querySelectorAll('.sidebar-nav .nav-link').forEach(link => {
          link.classList.toggle('active', link.getAttribute('href') === path);
        });
        // open parent submenu if child active
        document.querySelectorAll('.submenu').forEach(sub => {
          const activeChild = sub.querySelector(`.nav-link[href="${path}"]`);
          if (activeChild) {
            sub.classList.add('open');
            sub.style.display = 'flex';
            const toggleIcon = sub.closest('.nav-group')?.querySelector('.group-toggle .fa-chevron-down');
            if (toggleIcon) {
              toggleIcon.classList.replace('fa-chevron-down', 'fa-chevron-up');
            }
          }
        });
      };
      highlightActiveLink();

      // Logout handler
      const doLogout = async () => {
        Swal.fire({
          title: 'Logging out…',
          didOpen: () => Swal.showLoading(),
          allowOutsideClick: false
        });
        try {
          const token = getToken();
          const res = await fetch('/api/logout', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'Authorization': `Bearer ${token}`
            }
          });
          if (!res.ok) throw new Error('Logout failed');

          Swal.close();
          await Swal.fire({
            icon: 'success',
            title: 'Logged out',
            text: 'You have been logged out successfully.',
            timer: 1500,
            showConfirmButton: false
          });

          sessionStorage.removeItem('token');
          localStorage.removeItem('token');
          window.location.href = '/';
        } catch (err) {
          Swal.fire('Error', err.message, 'error');
        }
      };

      document.getElementById('logoutBtnSidebar')?.addEventListener('click', e => {
        e.preventDefault(); doLogout();
      });
      document.getElementById('logoutBtnMobile')?.addEventListener('click', e => {
        e.preventDefault(); doLogout();
      });

      // Safety valve: hard cap to avoid overlay stuck forever (e.g., script errors)
      setTimeout(() => {
        const o = document.getElementById('classmai-mastructureOverlay');
        if (o && !o.classList.contains('is-hidden')) {
          console.warn('[my-theme] Hard-cap release overlay after timeout.');
          hideOverlay();
        }
      }, 9000);

      // Finally: fetch & apply theme for logged-in user
      fetchMyThemeAndApply();
    });
  </script>
</body>
</html>
