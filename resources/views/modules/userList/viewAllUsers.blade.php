<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Manage Users</title>

  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <!-- Font Awesome -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet"/>

  <!-- CSRF & Auth Token -->
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <link rel="stylesheet" href="{{ asset('assets/css/common/main.css') }}">
  <style>
    /* Custom styling */
    .summary-card {
      background: white;
      border-radius: 8px;
      box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }
    .summary-card .icon {
      font-size: 1.5rem;
      color: var(--secondary-color);
    }
    .badge-active { background-color: #198754; }
    .badge-inactive { background-color: #dc3545; }
    .badge-expired { background-color: #6c757d; }
    .rotating { animation: rotate 1s linear infinite; }
    @keyframes rotate { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
    .table-actions button { margin-right: .25rem; }
    .user-avatar {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      background-color: #6366f1;
      color: white;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: bold;
    }
    .pagination { justify-content: center; }
    .pager-btn { margin: 0 2px; }
    .preview-container {
      max-width: 100%;
      border: 1px solid #dee2e6;
      border-radius: 5px;
      overflow: hidden;
    }
    #previewFrame {
      width: 100%;
      height: 500px;
      border: none;
    }
    .plan-status-badge {
      font-size: 0.7em;
      margin-left: 5px;
    }
    .renew-btn {
      font-size: 0.75em;
      padding: 2px 8px;
      margin-left: 5px;
    }
    .upgrade-btn {
      font-size: 0.75em;
      padding: 2px 8px;
      margin-left: 5px;
    }
    .remaining-days {
      font-size: 0.7em;
      margin-left: 5px;
      color: #6c757d;
    }
    .plan-actions {
      display: flex;
      align-items: center;
      flex-wrap: wrap;
      gap: 5px;
    }
  </style>
</head>
<body class="bg-light">
  <div class="container py-5">
    <!-- Summary Cards -->
    <div class="row row-cols-1 row-cols-md-4 g-4 mb-4 text-center">
      <div class="col">
        <div class="summary-card p-3 h-100">
          <div class="icon mb-2"><i class="fa-solid fa-users"></i></div>
          <h5 id="totalUsers">0</h5>
          <small>Total Users</small>
        </div>
      </div>
      <div class="col">
        <div class="summary-card p-3 h-100">
          <div class="icon mb-2"><i class="fa-solid fa-toggle-on"></i></div>
          <h5 id="activeUsers">0</h5>
          <small>Active</small>
        </div>
      </div>
      <div class="col">
        <div class="summary-card p-3 h-100">
          <div class="icon mb-2"><i class="fa-solid fa-toggle-off"></i></div>
          <h5 id="inactiveUsers">0</h5>
          <small>Inactive</small>
        </div>
      </div>
      <div class="col">
        <div class="summary-card p-3 h-100">
          <div class="icon mb-2"><i class="fa-solid fa-envelope"></i></div>
          <h5 id="totalCampaigns">0</h5>
          <small>Total Campaigns</small>
        </div>
      </div>
    </div>

    <!-- Toolbar -->
    <div class="d-flex justify-content-between align-items-center mb-3">
      <div class="search-export d-flex">
        <input type="text" id="globalSearch" class="form-control form-control-sm me-2" placeholder="Search users...">
        <button class="btn btn-outline-success btn-sm me-3" id="exportCsvBtn">
          <i class="fa-solid fa-file-csv me-1"></i> Export CSV
        </button>
      </div>
      <div>
        <button class="btn btn-outline-secondary btn-sm me-2" id="refreshBtn" title="Refresh">
          <i class="fa-solid fa-arrows-rotate" id="refreshBtnIcon"></i>
        </button>
        <button class="btn btn-primary btn-sm" onclick="openUserModal('add')">
          <i class="fa-solid fa-plus me-1"></i> New User
        </button>
      </div>
    </div>

    <!-- Main Table -->
    <div class="p-4 rounded shadow-sm bg-white">
      <div class="table-responsive">
        <table class="table table-hover" id="usersTable">
          <thead class="table-light">
            <tr>
              <th>User</th>
              <th>Email</th>
              <th>Phone</th>
              <th>Status</th>
              <th>Created</th>
              <th>Plan</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td colspan="10" class="py-5 text-center">
                <div class="spinner-border" role="status">
                  <span class="visually-hidden">Loading...</span>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div id="pagerUsers" class="mt-3"></div>
    </div>
  </div>

  <!-- Add/Edit User Modal -->
  <div class="modal fade" id="userModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <form id="userForm">
          <div class="modal-header">
            <h5 class="modal-title" id="userModalLabel">Add New User</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <input type="hidden" id="modalUserId">
            <div class="row mb-3">
              <div class="col-md-6">
                <label for="modalName" class="form-label">Name</label>
                <input type="text" class="form-control" id="modalName" required>
              </div>
              <div class="col-md-6">
                <label for="modalEmail" class="form-label">Email</label>
                <input type="email" class="form-control" id="modalEmail" required>
              </div>
            </div>
            <div class="row mb-3">
              <div class="col-md-6">
                <label for="modalPhone" class="form-label">Phone</label>
                <input type="text" class="form-control" id="modalPhone">
              </div>
              <div class="col-md-6">
                <label for="modalStatus" class="form-label">Status</label>
                <select class="form-select" id="modalStatus">
                  <option value="active">Active</option>
                  <option value="inactive">Inactive</option>
                </select>
              </div>
            </div>
            <div class="row mb-3">
              <div class="col-md-6">
                <label for="modalPassword" class="form-label">Password</label>
                <input type="password" class="form-control" id="modalPassword" autocomplete="new-password">
                <div class="form-text">Leave blank to keep current password</div>
              </div>
              <div class="col-md-6">
                <label for="modalPasswordConfirm" class="form-label">Confirm Password</label>
                <input type="password" class="form-control" id="modalPasswordConfirm" autocomplete="new-password">
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary">Save User</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- User Detail Modal -->
  <div class="modal fade" id="userDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">User Details</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="row">
            <div class="col-md-4">
              <div class="card mb-4 h-100">
                <div class="card-body text-center">
                  <div class="user-avatar mx-auto mb-3" id="detailAvatar">JD</div>
                  <h5 id="detailName">John Doe</h5>
                  <p class="text-muted mb-1" id="detailEmail">john@example.com</p>
                  <p class="text-muted" id="detailPhone">+1 234 567 890</p>
                  <span class="badge rounded-pill" id="detailStatus">Active</span>
                </div>
              </div>
            </div>
            <div class="col-md-8">
              <ul class="nav nav-tabs" id="userDetailTabs" role="tablist">
                <li class="nav-item" role="presentation">
                  <button class="nav-link active" id="stats-tab" data-bs-toggle="tab" data-bs-target="#stats" type="button" role="tab">
                    Statistics
                  </button>
                </li>
                <li class="nav-item" role="presentation">
                  <button class="nav-link" id="templates-tab" data-bs-toggle="tab" data-bs-target="#templates" type="button" role="tab">
                    Recent Templates
                  </button>
                </li>
                <li class="nav-item" role="presentation">
                  <button class="nav-link" id="lists-tab" data-bs-toggle="tab" data-bs-target="#lists" type="button" role="tab">
                    Recent Lists
                  </button>
                </li>
                <li class="nav-item" role="presentation">
                  <button class="nav-link" id="campaigns-tab" data-bs-toggle="tab" data-bs-target="#campaigns" type="button" role="tab">
                    Recent Campaigns
                  </button>
                </li>
              </ul>
              <div class="tab-content p-3 border border-top-0 rounded-bottom">
                <div class="tab-pane fade show active" id="stats" role="tabpanel">
                  <div class="row">
                    <div class="col-md-6 mb-3">
                      <div class="card h-100">
                        <div class="card-body">
                          <h6 class="card-title">Account Information</h6>
                          <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                              <span>Status</span>
                              <span class="badge" id="detailStatusBadge">Active</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                              <span>Member Since</span>
                              <span id="detailCreatedAt">2023-01-01</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                              <span>Last Updated</span>
                              <span id="detailUpdatedAt">2023-01-01</span>
                            </li>
                          </ul>
                        </div>
                      </div>
                    </div>
                    <div class="col-md-6 mb-3">
                      <div class="card h-100">
                        <div class="card-body">
                          <h6 class="card-title">Activity Summary</h6>
                          <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                              <span>Templates</span>
                              <span id="detailTemplatesCount">0</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                              <span>Lists</span>
                              <span id="detailListsCount">0</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                              <span>Campaigns</span>
                              <span id="detailCampaignsCount">0</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                              <span>Subscribers</span>
                              <span id="detailSubscribersCount">0</span>
                            </li>
                          </ul>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="tab-pane fade" id="templates" role="tabpanel">
                  <div class="table-responsive">
                    <table class="table table-sm">
                      <thead>
                        <tr>
                          <th>Name</th>
                          <th>Subject</th>
                          <th>Status</th>
                          <th>Created</th>
                        </tr>
                      </thead>
                      <tbody id="detailTemplatesTable">
                        <tr>
                          <td colspan="4" class="text-center py-3">
                            <div class="spinner-border spinner-border-sm" role="status">
                              <span class="visually-hidden">Loading...</span>
                            </div>
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>
                <div class="tab-pane fade" id="lists" role="tabpanel">
                  <div class="table-responsive">
                    <table class="table table-sm">
                      <thead>
                        <tr>
                          <th>Name</th>
                          <th>Subscribers</th>
                          <th>Created</th>
                        </tr>
                      </thead>
                      <tbody id="detailListsTable">
                        <tr>
                          <td colspan="3" class="text-center py-3">
                            <div class="spinner-border spinner-border-sm" role="status">
                              <span class="visually-hidden">Loading...</span>
                            </div>
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>
                <div class="tab-pane fade" id="campaigns" role="tabpanel">
                  <div class="table-responsive">
                    <table class="table table-sm">
                      <thead>
                        <tr>
                          <th>Name</th>
                          <th>Status</th>
                          <th>Sent</th>
                          <th>Created</th>
                        </tr>
                      </thead>
                      <tbody id="detailCampaignsTable">
                        <tr>
                          <td colspan="4" class="text-center py-3">
                            <div class="spinner-border spinner-border-sm" role="status">
                              <span class="visually-hidden">Loading...</span>
                            </div>
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Dependencies -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <!-- Application Script -->
  <script>
  /* ================== TOKEN ================== */
  function getToken(){ return sessionStorage.getItem('token') || localStorage.getItem('token'); }

  /* ================== GLOBAL STATE ================== */
  const userModal = new bootstrap.Modal('#userModal');
  const userDetailModal = new bootstrap.Modal('#userDetailModal');
  let usersData = [];
  let filteredData = [];
  let currentPage = 1;
  const rowsPerPage = 10;
  let currentDetailUser = null;
  let subscriptionPlans = [];

  /* ================== API CORE ================== */
  async function apiRequest(url,{method='GET',body=null,headers={}}={}){
    const token=getToken();
    if(!token) throw new Error('NO_TOKEN');

    const base={
      Accept:'application/json',
      Authorization:`Bearer ${token}`,
      'X-Requested-With':'XMLHttpRequest'
    };
    if(body && typeof body==='object' && !(body instanceof FormData)){
      base['Content-Type']='application/json';
      body=JSON.stringify(body);
    }
    if(['PUT','PATCH','DELETE'].includes(method)){
      base['X-HTTP-Method-Override']=method;
    }

    const res=await fetch(url,{method,headers:{...base,...headers},body});
    const ct=res.headers.get('content-type')||'';
    let data;
    if(ct.includes('application/json')){
      try{ data=await res.json(); }catch{ data={};}
    }else{
      const text=await res.text();
      console.warn('[apiRequest] NON-JSON BODY',text.slice(0,200));
      throw new Error('NON_JSON_RESPONSE_POSSIBLE_AUTH ('+res.status+')');
    }
    if(!res.ok){
      const err=new Error(data.message||data.error||('HTTP '+res.status));
      err.status=res.status; err.payload=data; throw err;
    }
    return data;
  }

  /* ================== FETCH & RENDER ================== */
  async function fetchUsers(){
    $('#refreshBtnIcon').addClass('rotating');
    const $tbody=$('#usersTable tbody');
    $tbody.html(`
      <tr><td colspan="10" class="py-5 text-center">
        <div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div>
      </td></tr>
    `);
    try{
      const json=await apiRequest('/api/admin/users');
      usersData=json.data.users||[];
      
      updateSummary();
      applyFilterAndRender();
    }catch(err){
      console.error(err);
      $tbody.html(`<tr><td colspan="10" class="py-5 text-center text-danger">Error loading users</td></tr>`);
      handleApiError('Failed to load users',err);
    }finally{
      $('#refreshBtnIcon').removeClass('rotating');
    }
  }

  async function fetchUserDetail(userId, buttonElement) {
    const $btn = $(buttonElement);
    const $icon = $btn.find('i');
    
    // Show loading state on button
    $btn.prop('disabled', true);
    $icon.removeClass('fa-eye').addClass('fa-spinner fa-spin');
    
    // Show SweetAlert loading modal
    const swalInstance = Swal.fire({
        title: 'Loading user details',
        html: 'Please wait while we fetch the user information...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    try {
        const json = await apiRequest(`/api/admin/users/${userId}`);
        currentDetailUser = json.data.user;
        
        // Update basic info
        $('#detailName').text(currentDetailUser.name);
        $('#detailEmail').text(currentDetailUser.email);
        $('#detailPhone').text(currentDetailUser.phone || 'N/A');
        $('#detailStatus').text(currentDetailUser.status);
        $('#detailStatusBadge').text(currentDetailUser.status);
        $('#detailCreatedAt').text(formatDate(currentDetailUser.created_at));
        $('#detailUpdatedAt').text(formatDate(currentDetailUser.updated_at));
        
        // Update avatar
        const initials = currentDetailUser.name.split(' ').map(n => n[0]).join('').toUpperCase();
        $('#detailAvatar').text(initials.substring(0, 2));
        
        // Update status badge color
        const statusBadge = $('#detailStatusBadge');
        statusBadge.removeClass('bg-success bg-danger');
        statusBadge.addClass(currentDetailUser.status === 'active' ? 'bg-success' : 'bg-danger');
        
        // Show loading in detail sections
        $('#detailTemplatesTable, #detailListsTable, #detailCampaignsTable').html(`
          <tr><td colspan="10" class="text-center py-3">
            <div class="spinner-border spinner-border-sm" role="status">
              <span class="visually-hidden">Loading...</span>
            </div>
          </td></tr>
        `);
        
        // Update statistics
        $('#detailTemplatesCount').text(json.data.statistics.templates || 0);
        $('#detailListsCount').text(json.data.statistics.lists || 0);
        $('#detailCampaignsCount').text(json.data.statistics.campaigns.total || 0);
        $('#detailSubscribersCount').text(json.data.statistics.total_subscribers || 0);
        
        // Close the SweetAlert loading modal
        await swalInstance.close();
        
        // Show the user detail modal
        userDetailModal.show();
        
        // Update recent items tables
        renderDetailTable('#detailTemplatesTable', json.data.recent_templates || [], (item) => [
          escapeHtml(item.name || 'Untitled'),
          escapeHtml(item.subject || 'No subject'),
          `<span class="badge ${item.is_active ? 'bg-success' : 'bg-danger'}">${item.is_active ? 'Active' : 'Inactive'}</span>`,
          formatDate(item.created_at)
        ]);
        
        renderDetailTable('#detailListsTable', json.data.recent_lists || [], (item) => [
          escapeHtml(item.title || 'Untitled'),
          item.subscribers_count || 0,
          formatDate(item.created_at)
        ]);
        
        renderDetailTable('#detailCampaignsTable', json.data.recent_campaigns || [], (item) => [
          escapeHtml(item.title || 'Untitled'),
          `<span class="badge bg-${getCampaignStatusClass(item.status)}">${item.status}</span>`,
          item.scheduled_at ? formatDate(item.scheduled_at) : 'Not sent',
          formatDate(item.created_at)
        ]);
        
    } catch(err) {
        console.error(err);
        // Close the loading modal if it's still open
        if (swalInstance.isActive()) {
            await swalInstance.close();
        }
        Swal.fire('Error','Failed to load user details','error');
    } finally {
        // Restore button state
        if ($btn.length) {
            $btn.prop('disabled', false);
            $icon.removeClass('fa-spinner fa-spin').addClass('fa-eye');
        }
    }
}

  function renderDetailTable(selector, data, rowRenderer){
    const $tbody = $(selector).empty();
    
    if(!data.length){
      $tbody.html(`<tr><td colspan="10" class="text-center py-3 text-muted">No data available</td></tr>`);
      return;
    }
    
    data.forEach(item => {
      $tbody.append(`<tr>${rowRenderer(item).map(cell => `<td>${cell}</td>`).join('')}</tr>`);
    });
  }

  function getCampaignStatusClass(status){
    switch(status){
      case 'completed': return 'success';
      case 'scheduled': return 'info';
      case 'draft': return 'secondary';
      case 'sending': return 'warning';
      default: return 'primary';
    }
  }

  function updateSummary(){
    const total=usersData.length;
    const active=usersData.filter(u=>u.status==='active').length;
    const totalCampaigns=usersData.reduce((sum,u)=>sum+(u.campaign_count||0),0);
    
    $('#totalUsers').text(total);
    $('#activeUsers').text(active);
    $('#inactiveUsers').text(total-active);
    $('#totalCampaigns').text(totalCampaigns);
  }

  $('#globalSearch').on('input',()=>{ currentPage=1; applyFilterAndRender(); });

  $('#exportCsvBtn').click(()=>{
    if(!filteredData.length){ Swal.fire('Info','No rows to export','info'); return; }
    let csv='Name,Email,Phone,Status,Created,Templates,Lists,Campaigns,Subscribers,Plan,Plan Status\n';
    filteredData.forEach(u=>{
      const planStatus = u.subscription_info?.status_text || 'No Plan';
      
      csv+=`"${(u.name||'').replace(/"/g,'""')}",`+
           `"${(u.email||'').replace(/"/g,'""')}",`+
           `"${(u.phone||'').replace(/"/g,'""')}",`+
           `${u.status},`+
           `"${u.created_at?new Date(u.created_at).toLocaleString():''}",`+
           `${u.template_count||0},`+
           `${u.list_count||0},`+
           `${u.campaign_count||0},`+
           `${u.total_subscribers||0},`+
           `"${(u.subscription_plan_title||'').replace(/"/g,'""')}",`+
           `${planStatus}\n`;
    });
    const blob=new Blob([csv],{type:'text/csv'});
    const url=URL.createObjectURL(blob);
    const a=document.createElement('a');
    a.href=url; a.download='users.csv'; a.click();
    URL.revokeObjectURL(url);
    Swal.fire('Done','CSV exported','success');
  });

  function applyFilterAndRender(){
    const term=$('#globalSearch').val().toLowerCase();
    filteredData=usersData.filter(u=>
      (u.name||'').toLowerCase().includes(term) ||
      (u.email||'').toLowerCase().includes(term) ||
      (u.phone||'').toLowerCase().includes(term)
    );
    renderPage();
    renderPager();
  }

  function renderPage(){
    const start=(currentPage-1)*rowsPerPage;
    const pageData=filteredData.slice(start,start+rowsPerPage);
    const $tbody=$('#usersTable tbody').empty();
    const pager=document.getElementById('pagerUsers');

    if(!pageData.length){
      $tbody.html(`<tr><td colspan="10" class="text-center text-muted py-4">
        <i class="fa-solid fa-users-slash fa-2x mb-2"></i><br>No users found.
      </td></tr>`);
      pager.style.display='none';
      return;
    }

    pager.style.display='';
    pageData.forEach(u=>{
      const initials = u.name.split(' ').map(n => n[0]).join('').toUpperCase().substring(0, 2);
      const subscriptionInfo = u.subscription_info || {};
      const hasPlan = subscriptionInfo.has_plan;
      const isExpired = subscriptionInfo.is_expired;
      const isActive = subscriptionInfo.is_active;
      const remainingDays = Math.floor(subscriptionInfo.remaining_days || 0); // Convert to integer
      
      $tbody.append(`
        <tr>
          <td>
            <div class="d-flex align-items-center">
              <div class="user-avatar me-2">${initials}</div>
              <div>
                <div class="fw-bold">${escapeHtml(u.name)}</div>
              </div>
            </div>
          </td>
          <td>${escapeHtml(u.email)}</td>
          <td>${escapeHtml(u.phone || 'N/A')}</td>
          <td><span class="badge ${u.status==='active'?'bg-success':'bg-danger'}">${u.status}</span></td>
          <td>${formatDate(u.created_at)}</td>
          <td>
            ${hasPlan 
              ? `<div class="plan-actions">
                   <span class="text-success fw-semibold text-uppercase me-2">${escapeHtml(u.subscription_plan_title || 'Unknown Plan')}</span>
                   <span class="badge bg-${subscriptionInfo.status_badge} plan-status-badge">${subscriptionInfo.status_text}</span>
                   ${isActive && remainingDays > 0 ? `<span class="remaining-days">(${remainingDays} days left)</span>` : ''}
                   <div class="d-flex gap-1 mt-1">
                     ${isExpired 
                       ? `<button class="btn btn-warning btn-sm renew-btn" onclick="renewUserPlan('${u.id}')" title="Renew Plan">
                            <i class="fa-solid fa-rotate"></i> Renew
                          </button>`
                       : ''}
                     <button class="btn btn-info btn-sm upgrade-btn" onclick="openUpgradeModal('${u.id}')" title="Upgrade Plan">
                       <i class="fa-solid fa-arrow-up"></i> Upgrade
                     </button>
                   </div>
                 </div>`
              : `<div class="d-flex align-items-center">
                   <span class="text-danger fw-semibold text-uppercase">NOT ASSIGNED</span>
                   <button class="btn btn-outline-primary btn-sm ms-2" onclick="openAssignModal('${u.id}')" title="Assign Plan">
                     <i class="fa-solid fa-boxes-packing"></i>
                   </button>
                 </div>`}
          </td>
          <td class="table-actions">
            <button class="btn btn-sm btn-info me-1" onclick="fetchUserDetail('${u.id}', this)">
              <i class="fa-solid fa-eye"></i>
            </button>
            <button class="btn btn-sm btn-outline-secondary me-1" onclick="openUserModal('edit','${u.id}')">
              <i class="fa-regular fa-pen-to-square"></i>
            </button>
            <button class="btn btn-sm btn-outline-${u.status==='active'?'danger':'success'}" onclick="toggleUserStatus('${u.id}')">
              <i class="fa-solid ${u.status==='active'?'fa-ban':'fa-power-off'}"></i>
            </button>
          </td>
        </tr>
      `);
    });
  }

  function renderPager(){
    const total=filteredData.length;
    const pages=Math.ceil(total/rowsPerPage)||1;
    const ctr=document.getElementById('pagerUsers');
    ctr.innerHTML='';
    ctr.className='pagination';

    function btn(html,p,dis){
      const b=document.createElement('button');
      b.type='button'; b.innerHTML=html; b.disabled=dis;
      b.className='btn btn-outline-primary pager-btn';
      if(!dis) b.addEventListener('click',()=>{ currentPage=p; renderPage(); renderPager(); });
      return b;
    }

    ctr.appendChild(btn('<i class="fa-solid fa-angles-left"></i>',1,currentPage===1));
    ctr.appendChild(btn('<i class="fa-solid fa-angle-left"></i>',currentPage-1,currentPage===1));
    ctr.appendChild(btn(`${currentPage} / ${pages}`,currentPage,true));
    ctr.appendChild(btn('<i class="fa-solid fa-angle-right"></i>',currentPage+1,currentPage===pages));
    ctr.appendChild(btn('<i class="fa-solid fa-angles-right"></i>',pages,currentPage===pages));
  }

  /* ================== PLAN MANAGEMENT ================== */
  async function fetchPlans() {
    try {
      const json = await apiRequest('/api/plans');
      subscriptionPlans = json.data || [];
    } catch (e) {
      console.error('Could not load plans', e);
    }
  }

  let assignModalInst;
  async function openAssignModal(userId) {
    if (!document.getElementById('assignPlanModal')) {
      const modalContainer = document.createElement('div');
      modalContainer.innerHTML = `
        <div class="modal fade" id="assignPlanModal" tabindex="-1">
          <div class="modal-dialog">
            <form class="modal-content" id="assignPlanForm">
              <div class="modal-header">
                <h5 class="modal-title">Assign Subscription Plan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
              </div>
              <div class="modal-body">
                <input type="hidden" id="assignUserId">
                <div class="mb-3">
                  <label class="form-label">Select Plan</label>
                  <select id="assignPlanSelect" class="form-select" required>
                    <option value="">-- choose one --</option>
                  </select>
                </div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Assign</button>
              </div>
            </form>
          </div>
        </div>`;
      document.body.appendChild(modalContainer.firstElementChild);
      document.getElementById('assignPlanForm')
              .addEventListener('submit', assignPlanHandler);
      assignModalInst = new bootstrap.Modal(document.getElementById('assignPlanModal'));
    }

    document.getElementById('assignUserId').value = userId;
    const sel = document.getElementById('assignPlanSelect');

    const fmt = new Intl.NumberFormat('en-IN', {
      style: 'currency',
      currency: 'INR',
      minimumFractionDigits: 2,
    });

    const sortedPlans = [...subscriptionPlans].sort((a, b) => {
      return (parseFloat(a.price) || 0) - (parseFloat(b.price) || 0);
    });

    sel.innerHTML = `<option value="">-- choose one --</option>` +
      sortedPlans
        .map(p => {
          const price = fmt.format(parseFloat(p.price) || 0);
          return `<option value="${p.id}">${p.title} — ${price}/${p.billing_cycle}</option>`;
        })
        .join('');

    assignModalInst.show();
  }

  let upgradeModalInst;
  async function openUpgradeModal(userId) {
    if (!document.getElementById('upgradePlanModal')) {
      const modalContainer = document.createElement('div');
      modalContainer.innerHTML = `
        <div class="modal fade" id="upgradePlanModal" tabindex="-1">
          <div class="modal-dialog">
            <form class="modal-content" id="upgradePlanForm">
              <div class="modal-header">
                <h5 class="modal-title">Upgrade Subscription Plan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
              </div>
              <div class="modal-body">
                <input type="hidden" id="upgradeUserId">
                <div class="mb-3">
                  <label class="form-label">Select New Plan</label>
                  <select id="upgradePlanSelect" class="form-select" required>
                    <option value="">-- choose one --</option>
                  </select>
                </div>
                <div class="alert alert-info">
                  <i class="fa-solid fa-info-circle me-2"></i>
                  Upgrading will cancel the current subscription and assign the new plan immediately.
                </div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Upgrade Plan</button>
              </div>
            </form>
          </div>
        </div>`;
      document.body.appendChild(modalContainer.firstElementChild);
      document.getElementById('upgradePlanForm')
              .addEventListener('submit', upgradePlanHandler);
      upgradeModalInst = new bootstrap.Modal(document.getElementById('upgradePlanModal'));
    }

    const user = usersData.find(u => u.id == userId);
    if (!user) {
      Swal.fire('Error', 'User not found', 'error');
      return;
    }

    document.getElementById('upgradeUserId').value = userId;
    const sel = document.getElementById('upgradePlanSelect');

    const fmt = new Intl.NumberFormat('en-IN', {
      style: 'currency',
      currency: 'INR',
      minimumFractionDigits: 2,
    });

    // Filter out current plan and show only higher-priced plans for upgrade
    const currentPlanPrice = parseFloat(user.subscription_plan_id ? 
      subscriptionPlans.find(p => p.id == user.subscription_plan_id)?.price || 0 : 0);
    
    const upgradePlans = subscriptionPlans
      .filter(p => parseFloat(p.price || 0) > currentPlanPrice)
      .sort((a, b) => (parseFloat(a.price) || 0) - (parseFloat(b.price) || 0));

    if (upgradePlans.length === 0) {
      Swal.fire('Info', 'No higher-tier plans available for upgrade.', 'info');
      return;
    }

    sel.innerHTML = `<option value="">-- choose one --</option>` +
      upgradePlans
        .map(p => {
          const price = fmt.format(parseFloat(p.price) || 0);
          return `<option value="${p.id}">${p.title} — ${price}/${p.billing_cycle}</option>`;
        })
        .join('');

    upgradeModalInst.show();
  }

  async function assignPlanHandler(e) {
    e.preventDefault();
    const userId = document.getElementById('assignUserId').value;
    const planId = document.getElementById('assignPlanSelect').value;
    if (!planId) {
      Swal.fire('Error', 'Please select a plan to assign.', 'warning');
      return;
    }

    const submitBtn = document.querySelector('#assignPlanForm button[type=submit]');
    submitBtn.disabled = true;
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = `<i class="fa-solid fa-spinner fa-spin me-1"></i> Assigning…`;

    try {
      const res = await apiRequest(`/api/plans/${planId}/assign`, {
        method: 'POST',
        body: { user_id: userId }
      });

      assignModalInst.hide();
      await fetchUsers();
      Swal.fire({
        icon: 'success',
        title: 'Plan Assigned',
        text: res.message || 'Subscription plan assigned successfully.',
        timer: 2500,
        showConfirmButton: false
      });
    } catch (err) {
      console.error('Assign plan error', err);
      let title = 'Failed to assign plan';
      let text = err.payload?.message || err.message || 'Unknown error';
      if (err.payload?.data) {
        if (err.payload.data.current_plan_id) {
          text += `\nCurrent plan ID: ${err.payload.data.current_plan_id}`;
          if (err.payload.data.expires_at) {
            text += ` (expires at ${new Date(err.payload.data.expires_at).toLocaleString()})`;
          }
        }
      }
      Swal.fire({
        icon: 'error',
        title,
        html: text.replace(/\n/g, '<br>'),
      });
    } finally {
      submitBtn.disabled = false;
      submitBtn.innerHTML = originalText;
    }
  }

  async function upgradePlanHandler(e) {
    e.preventDefault();
    const userId = document.getElementById('upgradeUserId').value;
    const newPlanId = document.getElementById('upgradePlanSelect').value;
    if (!newPlanId) {
      Swal.fire('Error', 'Please select a plan to upgrade to.', 'warning');
      return;
    }

    const submitBtn = document.querySelector('#upgradePlanForm button[type=submit]');
    submitBtn.disabled = true;
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = `<i class="fa-solid fa-spinner fa-spin me-1"></i> Upgrading…`;

    try {
      const res = await apiRequest(`/api/plans/${newPlanId}/upgrade`, {
        method: 'POST',
        body: { user_id: userId }
      });

      upgradeModalInst.hide();
      await fetchUsers();
      Swal.fire({
        icon: 'success',
        title: 'Plan Upgraded',
        text: res.message || 'Subscription plan upgraded successfully.',
        timer: 2500,
        showConfirmButton: false
      });
    } catch (err) {
      console.error('Upgrade plan error', err);
      let title = 'Failed to upgrade plan';
      let text = err.payload?.message || err.message || 'Unknown error';
      Swal.fire({
        icon: 'error',
        title,
        text,
      });
    } finally {
      submitBtn.disabled = false;
      submitBtn.innerHTML = originalText;
    }
  }

  async function renewUserPlan(userId) {
    const user = usersData.find(u => u.id == userId);
    if (!user || !user.subscription_plan_id) {
      Swal.fire('Error', 'User does not have a plan to renew.', 'error');
      return;
    }

    const planId = user.subscription_plan_id;
    
    Swal.fire({
      title: 'Renew Subscription Plan?',
      text: `Renew ${user.name}'s ${user.subscription_plan_title} plan?`,
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Yes, Renew',
      cancelButtonText: 'Cancel'
    }).then(async (result) => {
      if (result.isConfirmed) {
        try {
          const res = await apiRequest(`/api/plans/${planId}/renew`, {
            method: 'POST',
            body: { user_id: userId }
          });

          await fetchUsers();
          Swal.fire({
            icon: 'success',
            title: 'Plan Renewed',
            text: res.message || 'Subscription plan renewed successfully.',
            timer: 2500,
            showConfirmButton: false
          });
        } catch (err) {
          console.error('Renew plan error', err);
          let title = 'Failed to renew plan';
          let text = err.payload?.message || err.message || 'Unknown error';
          Swal.fire({
            icon: 'error',
            title,
            text,
          });
        }
      }
    });
  }

  /* ================== USER CRUD OPERATIONS ================== */
  function openUserModal(mode, userId=''){
    $('#userModalLabel').text(mode==='add'?'Add New User':'Edit User');
    $('#userForm').trigger('reset');
    $('#modalUserId').val('');
    
    if(mode==='edit'){
      const user=usersData.find(u=>u.id==userId);
      if(!user){ Swal.fire('Error','User not found','error'); return; }
      
      $('#modalUserId').val(user.id);
      $('#modalName').val(user.name);
      $('#modalEmail').val(user.email);
      $('#modalPhone').val(user.phone||'');
      $('#modalStatus').val(user.status);
    }
    
    userModal.show();
  }

  $('#userForm').on('submit', async function(e){
    e.preventDefault();
    
    const userId = $('#modalUserId').val();
    const name = $('#modalName').val().trim();
    const email = $('#modalEmail').val().trim();
    const phone = $('#modalPhone').val().trim();
    const status = $('#modalStatus').val();
    const password = $('#modalPassword').val();
    const passwordConfirm = $('#modalPasswordConfirm').val();
    
    if(!name || !email){
      Swal.fire('Error','Name and email are required','error');
      return;
    }
    
    if(password && password !== passwordConfirm){
      Swal.fire('Error','Passwords do not match','error');
      return;
    }
    
    const payload = {
      name,
      email,
      phone: phone || null,
      status,
      password: password || undefined,
      password_confirmation: password ? passwordConfirm : undefined
    };
    
    Swal.fire({ title:'Saving...', allowOutsideClick:false, didOpen:()=>Swal.showLoading() });
    
    try {
      const url = userId ? `/api/admin/users/${userId}` : '/api/admin/users/register';
      const method = userId ? 'PUT' : 'POST';
      
      const json = await apiRequest(url, { method, body: payload });
      
      userModal.hide();
      await fetchUsers();
      Swal.fire('Success',json.message||'User saved','success');
    } catch(err) {
      handleApiError('Failed to save user',err);
    }
  });

  function toggleUserStatus(userId){
    Swal.fire({ 
      title:'Toggle user status?', 
      text:'This will activate or deactivate the user account.', 
      icon:'warning', 
      showCancelButton:true 
    }).then(async r=>{
      if(!r.isConfirmed) return;
      try{
        await apiRequest(`/api/admin/users/${userId}/toggle-status`,{method:'PATCH'});
        await fetchUsers();
        Swal.fire('Success','User status updated','success');
      }catch(err){
        handleApiError('Failed to toggle status',err);
      }
    });
  }

  function toggleUserStatusFromDetail(){
    if(!currentDetailUser) return;
    toggleUserStatus(currentDetailUser.id);
    userDetailModal.hide();
  }

  function deleteUserFromDetail(){
    if(!currentDetailUser) return;
    deleteUser(currentDetailUser.id);
    userDetailModal.hide();
  }

  function deleteUser(userId){
    Swal.fire({ 
      title:'Delete this user?', 
      text:'This cannot be undone. The user will lose access immediately.', 
      icon:'warning', 
      showCancelButton:true,
      confirmButtonText:'Delete',
      confirmButtonColor:'#dc3545'
    }).then(async r=>{
      if(!r.isConfirmed) return;
      try{
        await apiRequest(`/api/admin/users/${userId}`,{method:'DELETE'});
        await fetchUsers();
        Swal.fire('Deleted','User removed','success');
      }catch(err){
        handleApiError('Failed to delete user',err);
      }
    });
  }

  /* ================== UTILITIES ================== */
  function formatDate(dt){
    if(!dt) return '';
    try{ return new Date(dt).toLocaleString(); }catch{ return dt; }
  }
  
  function escapeHtml(str=''){
    return str.replace(/[&<>"']/g,s=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[s]));
  }

  function handleApiError(context,err){
    console.error(context,err);
    let msg=err.message||context;
    if(err.status===401) msg='Unauthorized – please login again.';
    else if(err.status===403) msg='Forbidden – check token scope / mismatch.';
    else if(msg.startsWith('NON_JSON_RESPONSE_POSSIBLE_AUTH')) msg='Server returned non‑JSON (likely login HTML). Auth failed.';
    else if(msg==='NO_TOKEN') msg='No auth token found. Please login again.';

    Swal.fire('Error',msg,'error').then(()=>{
      if(msg.includes('login again')||msg.includes('No auth token')) location.href='/';
    });
  }

  /* ================== INIT ================== */
  $(function(){
    const t=getToken();
    if(!t){
      Swal.fire('Auth Required','Session expired. Please login again.','warning')
        .then(()=>location.href='/');
      return;
    }
    
    $('#refreshBtn').on('click', fetchUsers);
    fetchPlans().then(fetchUsers);
  });
  </script>
</body>
</html>