
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
  {{-- <link id="themeStylesheet" rel="stylesheet" href="{{ asset('assets/css/common/' . $themeFile) }}"> --}}

  <link rel="stylesheet" href="{{ asset('/assets/css/pages/layout/structure.css') }}">
  <link id="dynamicTheme" rel="stylesheet" href="" />

  

  @stack('styles')
</head>
<body>
  <div class="layout">
    <aside class="dashboard-sidebar" id="sidebar">
      <button id="closeSidebar" class="close-sidebar">
        <i class="bi bi-x-lg"></i>
      </button>
      <div class="sidebar-logo text-center">
        <img src="{{ asset('assets/media/web_assets/logo1.png') }}" alt="Logo"/>
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
          <button
            class="btn btn-link text-decoration-none p-0"
            id="userDropdown"
            data-bs-toggle="dropdown"
            aria-expanded="false"
          >
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
  document.addEventListener('DOMContentLoaded', () => {
    // Sidebar toggles
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const rightPanel = document.getElementById('rightPanel');
    const openSidebar = () => {
      sidebar.classList.add('active');
      overlay.classList.add('active');
      rightPanel.classList.add('shifted');
    };
    const closeSidebar = () => {
      sidebar.classList.remove('active');
      overlay.classList.remove('active');
      rightPanel.classList.remove('shifted');
    };
    document.getElementById('toggleSidebar')?.addEventListener('click', openSidebar);
    document.getElementById('closeSidebar').addEventListener('click', closeSidebar);
    overlay.addEventListener('click', closeSidebar);

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
          const toggle = sub.closest('.nav-group').querySelector('.group-toggle .fa-chevron-down');
          toggle.classList.replace('fa-chevron-down','fa-chevron-up');
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
    const token = sessionStorage.getItem('token');
    const res = await fetch('/api/logout', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${token}`
      }
    });
    if (!res.ok) throw new Error('Logout failed');

    // Hide the loading dialog
    Swal.close();

    // Show success message
    await Swal.fire({
      icon: 'success',
      title: 'Logged out',
      text: 'You have been logged out successfully.',
      timer: 1500,
      showConfirmButton: false
    });

    // Clear token and redirect
    sessionStorage.removeItem('token');
    window.location.href = '/';
  } catch (err) {
    Swal.fire('Error', err.message, 'error');
  }
};


    document.getElementById('logoutBtnSidebar')?.addEventListener('click', e => {
      e.preventDefault();
      doLogout();
    });
    document.getElementById('logoutBtnMobile')?.addEventListener('click', e => {
      e.preventDefault();
      doLogout();
    });
  });
  </script>
</body>
</html>
