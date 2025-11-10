<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Manage Themes</title>

  <!-- Bootstrap & Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet"/>

  <meta name="csrf-token" content="{{ csrf_token() }}">
  <link rel="stylesheet" href="{{ asset('assets/css/common/main.css') }}">

  <style>
    body { background: #f5f6f8; }
    .summary-card { background:#fff;border-radius:8px;box-shadow:0 2px 4px rgba(0,0,0,.05); }
    .summary-card .icon { font-size:1.25rem;color:var(--secondary-color,#26a69a); }
    .user-avatar{ width:38px;height:38px;border-radius:50%;background:#6366f1;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.9rem }
    .table-actions button{ margin-right:.25rem }
    .form-text-xs{ font-size:.75rem;color:#6b7280 }
    .color-input{ display:flex;gap:6px;align-items:center }
    .color-input input[type=color]{ width:40px;height:34px;padding:0;border:1px solid #ced4da;border-radius:.25rem;background:#fff }
    .color-input input[type=text]{ flex:1 }
    .img-thumb{ width:42px;height:42px;object-fit:contain;border:1px solid #e5e7eb;border-radius:.25rem;background:#fff }
    .badge-active{ background:#198754 }
    .badge-inactive{ background:#dc3545 }
    .btn-spinner{ display:inline-flex;gap:.5rem;align-items:center }
    .btn-spinner i{ display:none }
    .btn-spinner.loading i{ display:inline-block }
    .btn-spinner.loading span{ opacity:.75 }
    .overlay-loading{ position:absolute; inset:0; background:rgba(255,255,255,.6); display:flex; align-items:center; justify-content:center; border-radius:.5rem; z-index:5 }
    .logo-grid{ display:grid;grid-template-columns:repeat(auto-fill,minmax(96px,1fr));gap:12px }
    .logo-card{ border:1px solid #e5e7eb;border-radius:8px;background:#fff;padding:8px;display:flex;flex-direction:column;align-items:center;gap:6px;cursor:pointer;transition:.15s }
    .logo-card:hover{ box-shadow:0 2px 10px rgba(0,0,0,.06); transform: translateY(-1px); }
    .logo-img{ width:72px;height:72px;object-fit:contain;background:#fff }
    .logo-url{ font-size:.7rem; word-break:break-all; white-space:normal; color:#6b7280; text-align:center }
  </style>
</head>
<body>
  <div class="container py-4">

    <div class="d-flex justify-content-between align-items-center mb-3">
      <h4 class="mb-0"><i class="fa-solid fa-palette me-2"></i>Manage Themes</h4>
      <div class="d-flex gap-2">
        <button id="refreshAllBtn" class="btn btn-outline-secondary btn-spinner" title="Refresh everything">
          <i class="fa-solid fa-spinner fa-spin"></i><span>Refresh</span>
        </button>
        <button id="newThemeBtn" class="btn btn-primary">
          <i class="fa-solid fa-plus me-1"></i> New Theme
        </button>
      </div>
    </div>

    <!-- Tabs -->
    <ul class="nav nav-tabs" id="mainTabs" role="tablist">
      <li class="nav-item" role="presentation">
        <button class="nav-link active" id="users-tab" data-bs-toggle="tab" data-bs-target="#usersTab" type="button" role="tab">
          Users
        </button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="themes-tab" data-bs-toggle="tab" data-bs-target="#themesTab" type="button" role="tab">
          Themes
        </button>
      </li>
    </ul>

    <div class="tab-content border border-top-0 rounded-bottom bg-white p-3 position-relative" id="mainTabsContent">

      <!-- USERS TAB -->
      <div class="tab-pane fade show active" id="usersTab" role="tabpanel" aria-labelledby="users-tab">
        <!-- Summary -->
        <div class="row row-cols-1 row-cols-md-4 g-3 mb-3 text-center">
          <div class="col">
            <div class="summary-card p-3 h-100">
              <div class="icon mb-1"><i class="fa-solid fa-users"></i></div>
              <h6 id="sumTotalUsers" class="mb-0">0</h6>
              <small>Total Users</small>
            </div>
          </div>
          <div class="col">
            <div class="summary-card p-3 h-100">
              <div class="icon mb-1"><i class="fa-solid fa-toggle-on"></i></div>
              <h6 id="sumActiveUsers" class="mb-0">0</h6>
              <small>Active</small>
            </div>
          </div>
          <div class="col">
            <div class="summary-card p-3 h-100">
              <div class="icon mb-1"><i class="fa-solid fa-toggle-off"></i></div>
              <h6 id="sumInactiveUsers" class="mb-0">0</h6>
              <small>Inactive</small>
            </div>
          </div>
          <div class="col">
            <div class="summary-card p-3 h-100">
              <div class="icon mb-1"><i class="fa-solid fa-layer-group"></i></div>
              <h6 id="sumUsersWithTheme" class="mb-0">0</h6>
              <small>Users with Theme</small>
            </div>
          </div>
        </div>

        <!-- Filter / Search -->
        <div class="d-flex justify-content-between align-items-center mb-2">
          <input id="userSearch" type="text" class="form-control form-control-sm w-50" placeholder="Search users by name/email/phone..." />
          <button id="reloadUsersBtn" class="btn btn-sm btn-outline-secondary btn-spinner">
            <i class="fa-solid fa-spinner fa-spin"></i><span>Reload</span>
          </button>
        </div>

        <div class="table-responsive position-relative" id="usersTableWrap">
          <table class="table table-hover align-middle" id="usersTable">
            <thead class="table-light">
              <tr>
                <th>User</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Status</th>
                <th>Theme</th>
                <th>Assigned</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody><!-- loading row inserted dynamically --></tbody>
          </table>
        </div>
        <div id="usersPager" class="pagination justify-content-center"></div>
      </div>

      <!-- THEMES TAB -->
      <div class="tab-pane fade" id="themesTab" role="tabpanel" aria-labelledby="themes-tab">

        <div class="row g-3 mb-2">
          <div class="col-md-4">
            <input id="themeSearch" class="form-control form-control-sm" placeholder="Search themes by name/app name...">
          </div>
          <div class="col text-end">
            <button id="reloadThemesBtn" class="btn btn-sm btn-outline-secondary btn-spinner">
              <i class="fa-solid fa-spinner fa-spin"></i><span>Reload</span>
            </button>
          </div>
        </div>

        <div class="table-responsive position-relative" id="themesTableWrap">
          <table class="table table-hover align-middle" id="themesTable">
            <thead class="table-light">
              <tr>
                <th>Name</th>
                <th>Brand</th>
                <th>Logo</th>
                <th>Primary</th>
                <th>Secondary</th>
                <th>Accent</th>
                <th>Updated</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody><!-- loading row inserted dynamically --></tbody>
          </table>
        </div>

      </div>
    </div>
  </div>

  <!-- ASSIGN THEME MODAL -->
  <div class="modal fade" id="assignThemeModal" tabindex="-1">
    <div class="modal-dialog">
      <form class="modal-content" id="assignThemeForm">
        <div class="modal-header">
          <h5 class="modal-title"><i class="fa-solid fa-link me-1"></i>Assign Theme</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" id="assignUserId">
          <div class="mb-2">
            <label class="form-label">User</label>
            <input type="text" id="assignUserName" class="form-control" readonly>
          </div>
          <div class="mb-2">
            <label class="form-label">Theme</label>
            <select id="assignThemeSelect" class="form-select" required></select>
          </div>
          <div class="mb-1">
            <label class="form-label">Status</label>
            <select id="assignStatus" class="form-select">
              <option value="active" selected>Active</option>
              <option value="inactive">Inactive</option>
            </select>
            <div class="form-text-xs">This is the status of the mapping (user ↔ theme), not the user.</div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
          <button id="assignSaveBtn" class="btn btn-primary btn-spinner" type="submit">
            <i class="fa-solid fa-spinner fa-spin"></i><span>Save</span>
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- THEME CRUD MODAL -->
  <div class="modal fade" id="themeModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
      <form class="modal-content" id="themeForm">
        <div class="modal-header">
          <h5 class="modal-title" id="themeModalTitle">New Theme</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" id="themeId">

          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Theme Name *</label>
              <input id="thName" type="text" class="form-control" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">App Name</label>
              <input id="thAppName" type="text" class="form-control" placeholder="e.g., Horizon Alienz">
            </div>

            <!-- Single Logo -->
            <div class="col-md-12">
              <label class="form-label">Logo URL</label>
              <div class="d-flex gap-2 align-items-center">
                <img id="thLogoPreview" class="img-thumb" alt="">
                <input id="thLogoUrl" type="text" class="form-control" placeholder="https://..." >
                <div class="btn-group">
                  {{-- <button class="btn btn-outline-secondary" type="button" id="btnOpenLogoLib">
                    <i class="fa-solid fa-images"></i>
                  </button> --}}
                  <button class="btn btn-outline-secondary" type="button" id="btnOpenUploadDirect">
                    <i class="fa-solid fa-upload"></i>
                  </button>
                </div>
              </div>
              <div class="form-text-xs mt-1">Pick an existing logo or upload a new one (stored under <code>web_assets/logo</code>).</div>
            </div>

            <!-- Colors -->
            <div class="col-md-6">
              <label class="form-label">Primary</label>
              <div class="color-input">
                <input id="thPrimary" type="color" value="#263b47">
                <input id="thPrimaryText" type="text" class="form-control" value="#263b47">
              </div>
            </div>
            <div class="col-md-6">
              <label class="form-label">Secondary</label>
              <div class="color-input">
                <input id="thSecondary" type="color" value="#26a69a">
                <input id="thSecondaryText" type="text" class="form-control" value="#26a69a">
              </div>
            </div>
            <div class="col-md-6">
              <label class="form-label">Accent</label>
              <div class="color-input">
                <input id="thAccent" type="color" value="#4f46e5">
                <input id="thAccentText" type="text" class="form-control" value="#4f46e5">
              </div>
            </div>
            <div class="col-md-6">
              <label class="form-label">Body BG</label>
              <div class="color-input">
                <input id="thBgBody" type="color" value="#ffffff">
                <input id="thBgBodyText" type="text" class="form-control" value="#ffffff">
              </div>
            </div>

            <div class="col-md-6">
              <label class="form-label">Text</label>
              <div class="color-input">
                <input id="thText" type="color" value="#374151">
                <input id="thTextText" type="text" class="form-control" value="#374151">
              </div>
            </div>
            <div class="col-md-6">
              <label class="form-label">Border</label>
              <div class="color-input">
                <input id="thBorder" type="color" value="#d1d5db">
                <input id="thBorderText" type="text" class="form-control" value="#d1d5db">
              </div>
            </div>

            <!-- Semantic -->
            <div class="col-md-6">
              <label class="form-label">Info</label>
              <div class="color-input">
                <input id="thInfo" type="color" value="#3b82f6">
                <input id="thInfoText" type="text" class="form-control" value="#3b82f6">
              </div>
            </div>
            <div class="col-md-6">
              <label class="form-label">Success</label>
              <div class="color-input">
                <input id="thSuccess" type="color" value="#10b981">
                <input id="thSuccessText" type="text" class="form-control" value="#10b981">
              </div>
            </div>
            <div class="col-md-6">
              <label class="form-label">Warning</label>
              <div class="color-input">
                <input id="thWarning" type="color" value="#f59e0b">
                <input id="thWarningText" type="text" class="form-control" value="#f59e0b">
              </div>
            </div>
            <div class="col-md-6">
              <label class="form-label">Danger</label>
              <div class="color-input">
                <input id="thDanger" type="color" value="#ef4444">
                <input id="thDangerText" type="text" class="form-control" value="#ef4444">
              </div>
            </div>

            <!-- Fonts -->
            <div class="col-md-6">
              <label class="form-label">Sans Font</label>
              <input id="thFontSans" type="text" class="form-control" placeholder="'Inter', sans-serif">
            </div>
            <div class="col-md-6">
              <label class="form-label">Heading Font</label>
              <input id="thFontHead" type="text" class="form-control" placeholder="'Poppins', sans-serif">
            </div>
          </div>

          <input id="hiddenUploadInput" type="file" class="d-none" accept=".png,.jpg,.jpeg,.webp,.svg,.ico">
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
          <button id="saveThemeBtn" class="btn btn-primary btn-spinner" type="submit">
            <i class="fa-solid fa-spinner fa-spin"></i><span>Save Theme</span>
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- LOGO LIBRARY MODAL -->
  <div class="modal fade" id="logoLibraryModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
      <div class="modal-content" id="logoLibContent">
        <div class="modal-header">
          <h5 class="modal-title"><i class="fa-solid fa-images me-1"></i>Select Logo</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body position-relative">
          <div class="d-flex gap-2 mb-3">
            <input id="logoSearch" class="form-control" placeholder="Search by URL">
            <button id="logoReloadBtn" class="btn btn-outline-secondary btn-spinner">
              <i class="fa-solid fa-spinner fa-spin"></i><span>Reload</span>
            </button>
            <div class="ms-auto">
              <input id="logoUploadInput" type="file" class="d-none" accept=".png,.jpg,.jpeg,.webp,.svg,.ico">
              <button id="logoUploadBtn" class="btn btn-primary btn-spinner">
                <i class="fa-solid fa-spinner fa-spin"></i><span><i class="fa-solid fa-upload me-1"></i>Upload New</span>
              </button>
            </div>
          </div>
          <div id="logoGridWrap" class="position-relative">
            <div id="logoGrid" class="logo-grid"></div>
            <div id="logoEmpty" class="text-center text-muted py-4 d-none">
              <i class="fa-regular fa-image fa-2x mb-2"></i><br>No logos found yet.
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Scripts -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script>
    /* =========================
       AUTH / CORE
       ========================= */
    const token = sessionStorage.getItem('token') || localStorage.getItem('token');
    if (!token) location.href = '/';

    const assignThemeModal = new bootstrap.Modal('#assignThemeModal');
    const themeModal       = new bootstrap.Modal('#themeModal');
    const logoLibraryModal = new bootstrap.Modal('#logoLibraryModal');

    function setBtnLoading(btn, loading=true){
      if(!btn) return;
      btn.classList.toggle('loading', !!loading);
      btn.disabled = !!loading;
    }
    document.getElementById('thLogoUrl').addEventListener('input', (e) => {
    const v = e.target.value.trim();
    document.getElementById('thLogoPreview').src = v || '';
  });
    function setWrapOverlay(wrap, on=true){
      if(!wrap) return;
      let overlay = wrap.querySelector('.overlay-loading');
      if(on){
        if(!overlay){
          overlay = document.createElement('div');
          overlay.className = 'overlay-loading';
          overlay.innerHTML = '<div class="spinner-border" role="status"></div>';
          wrap.appendChild(overlay);
        }
      }else{
        overlay && overlay.remove();
      }
    }
    function showTableLoading(tbody, colspan){
      tbody.innerHTML = `
        <tr>
          <td colspan="${colspan}" class="py-4 text-center">
            <div class="spinner-border" role="status"></div>
          </td>
        </tr>`;
    }

    async function api(url, {method='GET', body, headers={}} = {}) {
      const base = { Accept: 'application/json', Authorization: `Bearer ${token}` };
      if (body && !(body instanceof FormData)) {
        base['Content-Type'] = 'application/json';
        body = JSON.stringify(body);
      }
      const res = await fetch(url, { method, headers: { ...base, ...headers }, body });
      const ct = res.headers.get('content-type') || '';
      let data = {};
      if (ct.includes('application/json')) {
        try { data = await res.json(); } catch {}
      }
      if (!res.ok) {
        const msg = data.message || data.error || `HTTP ${res.status}`;
        const err = new Error(msg); err.status = res.status; err.payload = data; throw err;
      }
      return data;
    }

    function hexSync(colorPickerId, textId) {
      const cp = document.getElementById(colorPickerId);
      const tx = document.getElementById(textId);
      cp.addEventListener('input', () => tx.value = cp.value);
      tx.addEventListener('input', () => { if (/^#[0-9a-fA-F]{3,6}$/.test(tx.value)) cp.value = tx.value; });
    }

    /* =========================
       GLOBAL STATE
       ========================= */
    let allUsers   = [];
    let filteredUsers = [];
    let allThemes  = [];
    let filteredThemes = [];
    let currentPage = 1;
    const rowsPerPage = 10;

    // logo library
    let logoListAll = [];

    /* =========================
       USERS TAB
       ========================= */
    function userInitials(name='') {
      return name.trim().split(/\s+/).map(n => n[0]).join('').slice(0,2).toUpperCase() || 'U';
    }
    function fmtDate(s){ try { return new Date(s).toLocaleString(); } catch { return s || ''; } }
    function esc(s=''){ return s.replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m])); }

    async function loadUsers(showOverlay=true) {
      const wrap = document.getElementById('usersTableWrap');
      const btn = document.getElementById('reloadUsersBtn');
      const tbody = document.querySelector('#usersTable tbody');
      showTableLoading(tbody, 7);
      if(showOverlay) setWrapOverlay(wrap,true), setBtnLoading(btn,true);
      try {
        const json = await api('/api/admin/users');
        const users = json.data?.users || [];
        const maps = await Promise.allSettled(users.map(u => api(`/api/users/${u.id}/theme`)));
        const hasMap = new Map();
        maps.forEach((r,i) => {
          if (r.status === 'fulfilled') {
            hasMap.set(users[i].id, r.value?.data?.theme?.name || null);
          } else {
            hasMap.set(users[i].id, null);
          }
        });
        allUsers = users.map(u => ({...u, theme_name: hasMap.get(u.id)}));
        renderUsers();
        updateUserSummary();
      } catch (e) {
        console.error(e);
        tbody.innerHTML = `<tr><td colspan="7" class="text-danger text-center py-4">${esc(e.message || 'Failed to load users')}</td></tr>`;
      } finally {
        setWrapOverlay(wrap,false); setBtnLoading(btn,false);
      }
    }

    function updateUserSummary() {
      const total = allUsers.length;
      const active = allUsers.filter(u => u.status === 'active').length;
      const withTheme = allUsers.filter(u => !!u.theme_name).length;

      document.getElementById('sumTotalUsers').textContent   = total;
      document.getElementById('sumActiveUsers').textContent  = active;
      document.getElementById('sumInactiveUsers').textContent= total - active;
      document.getElementById('sumUsersWithTheme').textContent = withTheme;
    }

    function renderUsers() {
      const term = document.getElementById('userSearch').value.trim().toLowerCase();
      filteredUsers = allUsers.filter(u =>
        (u.name||'').toLowerCase().includes(term) ||
        (u.email||'').toLowerCase().includes(term) ||
        (u.phone||'').toLowerCase().includes(term)
      );

      const tbody = document.querySelector('#usersTable tbody');
      tbody.innerHTML = '';
      if (!filteredUsers.length) {
        tbody.innerHTML = `<tr><td colspan="7" class="text-center text-muted py-4">No users found</td></tr>`;
        return;
      }

      const start = (currentPage - 1) * rowsPerPage;
      const pageData = filteredUsers.slice(start, start + rowsPerPage);

      for (const u of pageData) {
        const initials = userInitials(u.name);
        const themeLabel = u.theme_name ? `<span class="badge bg-primary">${esc(u.theme_name)}</span>` : `<span class="text-muted">—</span>`;
        tbody.insertAdjacentHTML('beforeend', `
          <tr>
            <td>
              <div class="d-flex align-items-center">
                <div class="user-avatar me-2">${initials}</div>
                <div class="fw-semibold">${esc(u.name)}</div>
              </div>
            </td>
            <td>${esc(u.email)}</td>
            <td>${esc(u.phone || 'N/A')}</td>
            <td><span class="badge ${u.status==='active'?'badge-active':'badge-inactive'}">${u.status}</span></td>
            <td>${themeLabel}</td>
            <td>${fmtDate(u.updated_at)}</td>
            <td class="table-actions">
              <button class="btn btn-sm btn-outline-primary btn-assign" data-id="${u.id}" title="Assign Theme">
                <i class="fa-solid fa-link"></i>
              </button>
            </td>
          </tr>
        `);
      }
      renderPager('usersPager', filteredUsers.length, (p)=>{ currentPage=p; renderUsers(); });
    }

    // Delegate Assign button
    document.getElementById('usersTable').addEventListener('click', (e)=>{
      const btn = e.target.closest('.btn-assign');
      if(!btn) return;
      const id = Number(btn.dataset.id);
      openAssign(id);
    });

    function renderPager(containerId, total, onPage) {
      const pages = Math.max(1, Math.ceil(total / rowsPerPage));
      const ctr = document.getElementById(containerId);
      ctr.innerHTML = '';
      const mk = (html, p, dis=false) => {
        const b = document.createElement('button');
        b.className = 'btn btn-outline-primary btn-sm mx-1';
        b.innerHTML = html; b.disabled = dis;
        if (!dis) b.onclick = () => onPage(p);
        return b;
      };
      const cur = currentPage;
      ctr.append(mk('&laquo;', 1, cur===1), mk('&lsaquo;', cur-1, cur===1),
                 mk(`${cur} / ${pages}`, cur, true),
                 mk('&rsaquo;', cur+1, cur===pages), mk('&raquo;', pages, cur===pages));
    }

    async function openAssign(userId) {
      const user = allUsers.find(u => u.id == userId);
      if (!user) return;
      await ensureThemesLoaded();
      document.getElementById('assignUserId').value = user.id;
      document.getElementById('assignUserName').value = `${user.name} (${user.email})`;

      const sel = document.getElementById('assignThemeSelect');
      sel.innerHTML = `<option value="">-- choose theme --</option>` +
        allThemes.map(t => `<option value="${t.id}">${esc(t.name)}${t.app_name? ' — '+esc(t.app_name):''}</option>`).join('');

      try {
        const map = await api(`/api/users/${userId}/theme`);
        const themeId = map?.data?.theme?.id || '';
        if (themeId) sel.value = themeId;
        document.getElementById('assignStatus').value = (map?.data?.mapping?.status || 'active');
      } catch {}

      assignThemeModal.show();
    }

    document.getElementById('assignThemeForm').addEventListener('submit', async (e)=>{
      e.preventDefault();
      const btn = document.getElementById('assignSaveBtn');
      setBtnLoading(btn,true);
      const userId = document.getElementById('assignUserId').value;
      const themeId = document.getElementById('assignThemeSelect').value;
      const status  = document.getElementById('assignStatus').value;
      if (!themeId) { setBtnLoading(btn,false); return Swal.fire('Error','Please select a theme','error'); }

      try {
        const res = await api(`/api/users/${userId}/theme`, {method:'POST', body:{ theme_id: Number(themeId), status }});
        assignThemeModal.hide();
        await loadUsers(false);
        Swal.fire('Success', res.message || 'Theme assigned', 'success');
      } catch (err) {
        Swal.fire('Error', err.message || 'Failed to assign theme', 'error');
      } finally {
        setBtnLoading(btn,false);
      }
    });

    /* =========================
       THEMES TAB
       ========================= */
    async function ensureThemesLoaded() {
      if (allThemes.length) return;
      await loadThemes(false);
    }

    async function loadThemes(showOverlay=true) {
      const wrap = document.getElementById('themesTableWrap');
      const btn = document.getElementById('reloadThemesBtn');
      const tbody = document.querySelector('#themesTable tbody');
      showTableLoading(tbody, 8);
      if(showOverlay) setWrapOverlay(wrap,true), setBtnLoading(btn,true);
      try {
        const json = await api('/api/themes');
        allThemes = json.data || [];
        renderThemes();
      } catch (e) {
        console.error(e);
        tbody.innerHTML = `<tr><td colspan="8" class="text-danger text-center py-4">${esc(e.message || 'Failed to load themes')}</td></tr>`;
      } finally {
        setWrapOverlay(wrap,false); setBtnLoading(btn,false);
      }
    }

    function renderThemes() {
      const term = document.getElementById('themeSearch').value.trim().toLowerCase();
      filteredThemes = allThemes.filter(t =>
        (t.name||'').toLowerCase().includes(term) ||
        (t.app_name||'').toLowerCase().includes(term)
      );

      const tbody = document.querySelector('#themesTable tbody');
      tbody.innerHTML = '';
      if (!filteredThemes.length) {
        tbody.innerHTML = `<tr><td colspan="8" class="text-center text-muted py-4">No themes found</td></tr>`;
        return;
      }

      for (const t of filteredThemes) {
        tbody.insertAdjacentHTML('beforeend', `
          <tr>
            <td><strong>${esc(t.name)}</strong></td>
            <td>${esc(t.app_name || '')}</td>
            <td class="text-nowrap">
              ${t.logo_url ? `<img src="${esc(t.logo_url)}" class="img-thumb" alt="logo">` : '<span class="text-muted">—</span>'}
            </td>
            <td><span class="badge" style="background:${t.primary_color||'#ccc'}"> &nbsp; </span> ${esc(t.primary_color||'-')}</td>
            <td><span class="badge" style="background:${t.secondary_color||'#ccc'}"> &nbsp; </span> ${esc(t.secondary_color||'-')}</td>
            <td><span class="badge" style="background:${t.accent_color||'#ccc'}"> &nbsp; </span> ${esc(t.accent_color||'-')}</td>
            <td>${fmtDate(t.updated_at)}</td>
            <td class="table-actions">
              <button class="btn btn-sm btn-outline-secondary btn-edit" data-id="${t.id}" title="Edit">
                <i class="fa-regular fa-pen-to-square"></i>
              </button>
              <button class="btn btn-sm btn-outline-danger btn-delete" data-id="${t.id}" title="Delete">
                <i class="fa-solid fa-trash-can"></i>
              </button>
            </td>
          </tr>
        `);
      }
    }

    // THEMES: delegate Edit/Delete (NO JSON inline)
    document.getElementById('themesTable').addEventListener('click', (e)=>{
      const editBtn = e.target.closest('.btn-edit');
      const delBtn  = e.target.closest('.btn-delete');
      if(editBtn){
        const id = Number(editBtn.dataset.id);
        const theme = allThemes.find(t => Number(t.id) === id);
        openThemeModal('edit', theme || null);
      } else if (delBtn){
        const id = Number(delBtn.dataset.id);
        deleteTheme(id);
      }
    });

    function setThemeForm(t = null) {
      document.getElementById('themeModalTitle').textContent = t ? 'Edit Theme' : 'New Theme';
      document.getElementById('themeId').value = t?.id || '';

      const set = (id, val) => document.getElementById(id).value = val ?? '';

      set('thName', t?.name || '');
      set('thAppName', t?.app_name || '');
      set('thLogoUrl', t?.logo_url || '');

      set('thPrimary', t?.primary_color || '#263b47');
      set('thPrimaryText', t?.primary_color || '#263b47');
      set('thSecondary', t?.secondary_color || '#26a69a');
      set('thSecondaryText', t?.secondary_color || '#26a69a');
      set('thAccent', t?.accent_color || '#4f46e5');
      set('thAccentText', t?.accent_color || '#4f46e5');
      set('thBgBody', t?.bg_body || '#ffffff');
      set('thBgBodyText', t?.bg_body || '#ffffff');
      set('thText', t?.text_color || '#374151');
      set('thTextText', t?.text_color || '#374151');
      set('thBorder', t?.border_color || '#d1d5db');
      set('thBorderText', t?.border_color || '#d1d5db');

      set('thInfo', t?.info_color || '#3b82f6');
      set('thInfoText', t?.info_color || '#3b82f6');
      set('thSuccess', t?.success_color || '#10b981');
      set('thSuccessText', t?.success_color || '#10b981');
      set('thWarning', t?.warning_color || '#f59e0b');
      set('thWarningText', t?.warning_color || '#f59e0b');
      set('thDanger', t?.danger_color || '#ef4444');
      set('thDangerText', t?.danger_color || '#ef4444');

      set('thFontSans', t?.font_sans || "'Inter', sans-serif");
      set('thFontHead', t?.font_head || "'Poppins', sans-serif");

      document.getElementById('thLogoPreview').src = t?.logo_url || '';
    }

    function openThemeModal(mode, theme=null) {
      setThemeForm(mode === 'edit' ? theme : null);
      themeModal.show();
    }

    document.getElementById('themeForm').addEventListener('submit', async (e)=>{
      e.preventDefault();
      const btn = document.getElementById('saveThemeBtn');
      setBtnLoading(btn,true);
      const id = document.getElementById('themeId').value;
      const payload = collectThemePayload();
      try {
        const res = await api(id ? `/api/themes/${id}` : '/api/themes', { method: id ? 'PUT' : 'POST', body: payload });
        themeModal.hide();
        await loadThemes(false);
        Swal.fire('Success', res.message || 'Saved', 'success');
      } catch (err) {
        Swal.fire('Error', err.message || 'Failed to save theme', 'error');
      } finally {
        setBtnLoading(btn,false);
      }
    });

    function collectThemePayload() {
      const gx = id => document.getElementById(id).value.trim() || null;
      const hx = id => {
        const val = document.getElementById(id).value.trim();
        return val ? val : null;
      };
      return {
        name: gx('thName'),
        app_name: hx('thAppName'),
        logo_url: hx('thLogoUrl'),

        primary_color: hx('thPrimaryText'),
        secondary_color: hx('thSecondaryText'),
        accent_color: hx('thAccentText'),
        light_color: null,
        border_color: hx('thBorderText'),
        text_color: hx('thTextText'),
        bg_body: hx('thBgBodyText'),

        info_color: hx('thInfoText'),
        success_color: hx('thSuccessText'),
        warning_color: hx('thWarningText'),
        danger_color: hx('thDangerText'),

        font_sans: hx('thFontSans'),
        font_head: hx('thFontHead'),
      };
    }

    async function deleteTheme(id) {
      const { isConfirmed } = await Swal.fire({ title:'Delete this theme?', icon:'warning', showCancelButton:true });
      if (!isConfirmed) return;
      const wrap = document.getElementById('themesTableWrap');
      setWrapOverlay(wrap,true);
      try {
        const res = await api(`/api/themes/${id}`, { method: 'DELETE' });
        await loadThemes(false);
        Swal.fire('Deleted', res.message || 'Theme deleted', 'success');
      } catch (err) {
        Swal.fire('Error', err.message || 'Delete failed', 'error');
      } finally {
        setWrapOverlay(wrap,false);
      }
    }

    /* =========================
       LOGO LIBRARY (single logo field)
       ========================= */
    function openLogoLibrary(){
      document.getElementById('logoSearch').value = '';
      renderLogoGrid([]); // clear
      logoLibraryModal.show();
      loadLogos();
    }
    // document.getElementById('btnOpenLogoLib').addEventListener('click', openLogoLibrary);
    document.getElementById('btnOpenUploadDirect').addEventListener('click', openUploadDirect);

    async function loadLogos(){
      const wrap = document.getElementById('logoGridWrap');
      setWrapOverlay(wrap,true);
      setBtnLoading(document.getElementById('logoReloadBtn'),true);
      try{
        const res = await api('/api/themes/logos');
        logoListAll = res?.data?.all || [];
        renderLogoGrid(logoListAll);
      }catch(e){
        console.error(e);
        Swal.fire('Error', e.message || 'Failed to load logos', 'error');
        renderLogoGrid([]);
      }finally{
        setWrapOverlay(wrap,false);
        setBtnLoading(document.getElementById('logoReloadBtn'),false);
      }
    }

    function renderLogoGrid(list){
      const grid = document.getElementById('logoGrid');
      const empty = document.getElementById('logoEmpty');
      grid.innerHTML = '';
      if(!list.length){
        empty.classList.remove('d-none');
        return;
      }
      empty.classList.add('d-none');
      for(const url of list){
        const card = document.createElement('div');
        card.className = 'logo-card';
        card.innerHTML = `
          <img class="logo-img" src="${esc(url)}" alt="">
          <div class="logo-url">${esc(url)}</div>
          <button class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-check"></i> Select</button>
        `;
        card.querySelector('button').onclick = () => selectLogo(url);
        grid.appendChild(card);
      }
    }

    function filterLogos(){
      const q = document.getElementById('logoSearch').value.trim().toLowerCase();
      const filtered = !q ? logoListAll : logoListAll.filter(u => (u||'').toLowerCase().includes(q));
      renderLogoGrid(filtered);
    }

    function selectLogo(url){
      document.getElementById('thLogoUrl').value = url;
      document.getElementById('thLogoPreview').src = url;
      logoLibraryModal.hide();
    }

    // Direct upload (single logo)
    function openUploadDirect(){
      const inp = document.getElementById('hiddenUploadInput');
      inp.value = ''; inp.click();
    }
    document.getElementById('hiddenUploadInput').addEventListener('change', async (e)=>{
      const file = e.target.files?.[0];
      if(!file) return;
      try{
        const url = await uploadLogoFile(file);
        document.getElementById('thLogoUrl').value = url;
        document.getElementById('thLogoPreview').src = url;
      }catch(err){
        Swal.fire('Upload failed', err.message || 'Could not upload file', 'error');
      }
    });

    // Upload from inside Logo Library
    document.getElementById('logoUploadBtn').addEventListener('click', ()=>{
      document.getElementById('logoUploadInput').value = '';
      document.getElementById('logoUploadInput').click();
    });
    document.getElementById('logoUploadInput').addEventListener('change', async (e)=>{
      const file = e.target.files?.[0];
      if(!file) return;
      const btn = document.getElementById('logoUploadBtn');
      setBtnLoading(btn,true);
      try{
        const url = await uploadLogoFile(file);
        await loadLogos();
        selectLogo(url); // auto-select upload
      }catch(err){
        Swal.fire('Upload failed', err.message || 'Could not upload file', 'error');
      }finally{
        setBtnLoading(btn,false);
      }
    });

    async function uploadLogoFile(file){
      const fd = new FormData();
      fd.append('file', file);
      fd.append('kind', 'logo');
      const res = await api('/api/themes/upload', { method:'POST', body: fd });
      return res.url;
    }

    document.getElementById('logoReloadBtn').addEventListener('click', loadLogos);
    document.getElementById('logoSearch').addEventListener('input', filterLogos);

    /* =========================
       WIRING
       ========================= */
    [
      ['thPrimary','thPrimaryText'],['thSecondary','thSecondaryText'],
      ['thAccent','thAccentText'],['thBgBody','thBgBodyText'],
      ['thText','thTextText'],['thBorder','thBorderText'],
      ['thInfo','thInfoText'],['thSuccess','thSuccessText'],
      ['thWarning','thWarningText'],['thDanger','thDangerText']
    ].forEach(([c,t])=>hexSync(c,t));

    document.getElementById('userSearch').addEventListener('input', ()=>renderUsers());
    document.getElementById('themeSearch').addEventListener('input', ()=>renderThemes());
    document.getElementById('reloadUsersBtn').addEventListener('click', ()=>loadUsers());
    document.getElementById('reloadThemesBtn').addEventListener('click', ()=>loadThemes());
    document.getElementById('newThemeBtn').addEventListener('click', ()=>openThemeModal('add'));
    document.getElementById('refreshAllBtn').addEventListener('click', async (e)=>{
      setBtnLoading(e.currentTarget,true);
      try {
        await Promise.all([loadUsers(), loadThemes()]);
      } finally {
        setBtnLoading(e.currentTarget,false);
      }
    });
    // document.getElementById('btnOpenLogoLib').addEventListener('click', openLogoLibrary);

    // initial load
    (async function init(){
      try {
        setBtnLoading(document.getElementById('refreshAllBtn'),true);
        await Promise.all([loadUsers(), loadThemes()]);
      } catch (e) {
        Swal.fire('Error', e.message || 'Failed to load', 'error');
      } finally {
        setBtnLoading(document.getElementById('refreshAllBtn'),false);
      }
    })();
  </script>
</body>
</html>
