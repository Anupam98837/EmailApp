<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Manage Email Templates</title>

  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <!-- Font Awesome -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet"/>

  <!-- CSRF & Auth Token -->
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <link rel="stylesheet" href="{{ asset('assets/css/common/main.css') }}">
  <link rel="stylesheet" href="{{ asset('assets/css/pages/manageTemplate/manageTemplate.css') }}">
  <style>
    /* SweetAlert2 Popup Customization */
    .swal2-popup {
      background: #f5f7fa !important;
      padding: 1.25rem !important;
      border-radius: 8px !important;
      font-family: Inter, sans-serif;
    }
    .swal2-title {
      font-size: 1.25rem !important;
      margin-bottom: 0.75rem !important;
      color: #111827;
    }

    /* Device Selector Toolbar */
    .preview-tools {
      display: flex;
      justify-content: center;
      gap: 8px;
      margin-bottom: 12px;
    }
    .preview-tools .device-btn {
      padding: 6px 14px;
      border: 1px solid #d1d5db;
      background: #fff;
      color: #374151;
      border-radius: 6px;
      cursor: pointer;
      font-size: 0.9rem;
      transition: background 0.2s, color 0.2s;
    }
    .preview-tools .device-btn:hover {
      background: #e5e7eb;
    }
    .preview-tools .device-btn.active {
      background: #6366f1;
      border-color: #4f46e5;
      color: #fff;
    }

    /* Preview Container */
    .preview-container {
      background: #fff;
      border: 1px solid #d1d5db;
      border-radius: 6px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.06);
      margin: 0 auto;
      padding: 8px;
    }
    .preview-container.desktop { max-width: 768px; }
    .preview-container.tablet  { max-width: 768px; }
    .preview-container.mobile  { max-width: 475px; }

    /* Iframe Styling */
    #previewFrame {
      width: 100%;
      height: 500px;
      border: none;
      border-radius: 4px;
      background: #fff;
    }
    
    /* Tab styling */
    .nav-tabs .nav-link.active { 
      background: #6366f1; 
      color: #fff; 
    }
    .table-actions button { 
      margin-right: .25rem; 
    }
    
    /* Saving indicator */
    .saving-indicator {
      display: inline-flex;
      align-items: center;
      margin-left: 10px;
      font-size: 0.9rem;
      color: #6c757d;
    }
    .saving-indicator .icon {
      margin-right: 5px;
      animation: pulse 1.5s infinite;
    }
    @keyframes pulse {
      0% { opacity: 0.5; }
      50% { opacity: 1; }
      100% { opacity: 0.5; }
    }
    .saving-indicator.saving .icon {
      color: #0d6efd;
    }
    .saving-indicator.saved .icon {
      color: #198754;
    }

    /* Modal backdrop fix */
    .modal-backdrop {
      z-index: 1040 !important;
    }
    .modal {
      z-index: 1050 !important;
    }
  </style>
</head>
<body class="bg-light">

  <div class="container py-5">

    <!-- Summary Cards -->
    <div class="row row-cols-1 row-cols-md-4 g-4 mb-4 text-center">
      <div class="col">
        <div class="summary-card p-3 h-100">
          <div class="icon mb-2"><i class="fa-solid fa-envelope"></i></div>
          <h5 id="totalTemplates">0</h5>
          <small>Total Templates</small>
        </div>
      </div>
      <div class="col">
        <div class="summary-card p-3 h-100">
          <div class="icon mb-2"><i class="fa-solid fa-toggle-on"></i></div>
          <h5 id="activeTemplates">0</h5>
          <small>Active</small>
        </div>
      </div>
      <div class="col">
        <div class="summary-card p-3 h-100">
          <div class="icon mb-2"><i class="fa-solid fa-toggle-off"></i></div>
          <h5 id="inactiveTemplates">0</h5>
          <small>Inactive</small>
        </div>
      </div>
      <div class="col">
        <div class="summary-card p-3 h-100">
          <div class="icon mb-2"><i class="fa-solid fa-file-edit"></i></div>
          <h5 id="totalDrafts">0</h5>
          <small>Total Drafts</small>
        </div>
      </div>
    </div>

    <!-- Toolbar -->
    <div class="d-flex justify-content-between align-items-center mb-3">
      <div class="search-export d-flex">
        <input type="text" id="globalSearch" class="form-control form-control-sm me-2" placeholder="Search templates…">
        <button class="btn btn-outline-success btn-sm me-3" id="exportCsvBtn">
          <i class="fa-solid fa-file-csv me-1"></i> Export CSV
        </button>
      </div>
      <div>
        <button class="btn btn-outline-secondary btn-sm me-2" id="refreshBtn" title="Refresh">
          <i class="fa-solid fa-arrows-rotate"></i>
        </button>
        <button class="btn btn-primary btn-sm" onclick="openTemplateModal('add')">
          <i class="fa-solid fa-plus me-1"></i> New Template
        </button>
      </div>
    </div>

    <!-- Tabs Navigation -->
    <ul class="nav nav-tabs mb-4" id="templateTabs" role="tablist">
      <li class="nav-item" role="presentation">
        <button class="nav-link active" id="templates-tab" data-bs-toggle="tab" data-bs-target="#templates" type="button" role="tab">
          Templates
        </button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="drafts-tab" data-bs-toggle="tab" data-bs-target="#drafts" type="button" role="tab">
          Drafts
        </button>
      </li>
    </ul>

    <!-- Tab Content -->
    <div class="tab-content">
      <!-- Templates Tab -->
      <div class="tab-pane fade show active" id="templates" role="tabpanel">
        <div class="p-4 rounded shadow-sm template-table-container">
          <div class="table-responsive">
            <table class="table table-hover text-center" id="templatesTable">
              <thead class="table-light">
                <tr>
                  <th>Name</th>
                  <th>Subject</th>
                  <th>Status</th>
                  <th>Created</th>
                  <th>Updated</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td colspan="6" class="py-5">
                    <div class="spinner-border" role="status">
                      <span class="visually-hidden">Loading...</span>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <div id="pagerTemplates" class="mt-3"></div>
        </div>
      </div>

      <!-- Drafts Tab -->
      <div class="tab-pane fade" id="drafts" role="tabpanel">
        <div class="p-4 rounded shadow-sm draft-table-container">
          <div class="table-responsive">
            <table class="table table-hover text-center" id="draftsTable">
              <thead class="table-light">
                <tr>
                  <th>Name</th>
                  <th>Subject</th>
                  <th>Version</th>
                  <th>Status</th>
                  <th>Updated</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td colspan="6" class="py-5">
                    <div class="spinner-border" role="status">
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

  <!-- Add/Edit Modal -->
  <div class="modal fade" id="templateModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-fullscreen">
      <form class="modal-content" id="templateForm">
        <div class="modal-header">
          <h5 class="modal-title" id="templateModalLabel"></h5>
          <span id="savingIndicator" class="saving-indicator">
            <i class="fas fa-cloud icon"></i>
            <span class="text">Saved</span>
          </span>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          @csrf
          <input type="hidden" id="modalTemplateUuid">
          <input type="hidden" id="modalDraftUuid">

          <div class="row mb-3">
            <div class="col-md-6">
              <label class="form-label">Template Name</label>
              <input type="text" id="modalTemplateName" class="form-control" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Email Subject</label>
              <input type="text" id="modalEmailSubject" class="form-control" required>
            </div>
          </div>

          <div class="mb-3">
            <div class="d-flex gap-2 align-items-center mb-2">
              <label class="form-label mb-0">Email Body</label>
              <button type="button" class="btn btn-sm btn-outline-info" id="bodyInstructionsBtn">
                <i class="fa-solid fa-circle-info"></i>
              </button>
            </div>

            <!-- CUSTOM EDITOR -->
            <div id="template-editor-wrap">
              @include('modules.template.editor')
            </div>
            <!-- Hidden textarea as fallback / storage -->
            <textarea id="templateBodyHtml" class="d-none"></textarea>
            <textarea id="ceDraftExport" class="d-none"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
            <i class="fa-solid fa-xmark me-1"></i> Cancel
          </button>
          <button type="button" id="saveDraftBtn" class="btn btn-outline-primary me-2">
            <i class="fa-solid fa-file-edit me-1"></i> Save Draft
          </button>
          <button type="button" id="modalTemplateSubmit" class="btn btn-primary">
            <i class="fa-solid fa-save me-1"></i> Save
          </button>
        </div>
      </form>
    </div>
  </div>

  <input type="file" id="imageUploader" accept="image/*" class="d-none">

  <!-- Dependencies -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <!-- Application Script -->
  <script>
  /* ================== TOKEN ================== */
  function getToken(){ return sessionStorage.getItem('token') || localStorage.getItem('token'); }

  /* ================== GLOBAL STATE ================== */
  const templateModal = new bootstrap.Modal('#templateModal');
  let templateAction = '';
  let templatesData = [];
  let draftsData = [];
  let filteredData = [];
  let currentPage = 1;
  const rowsPerPage = 10;
  let activeLoaders = 0;
  let autoSaveTimeout;
  let isAutoSaving = false;
  let currentDraftUuid = '';
  let lastSaveTime = 0;
  const DEBOUNCE_TIME = 2000; // 2 seconds after last change
  let editorInitialized = false;

  /* ================== LOADERS ================== */
  function showLoader(){ activeLoaders++; }
  function hideLoader(){ activeLoaders = Math.max(0, activeLoaders - 1); }

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

  /* ================== EDITOR HELPERS ================== */
  function setBodyHTML(html){
    if(window.CEBuilder && window.CEBuilder.setHTML) {
      window.CEBuilder.setHTML(html || '');
    }
    $('#templateBodyHtml').val(html || '');
    $('#ceDraftExport').val(html || '');
  }

  function getBodyHTML(){
    if(window.CEBuilder && window.CEBuilder.getHTML) {
      return window.CEBuilder.getHTML().trim();
    }
    return $('#templateBodyHtml').val().trim();
  }

  /* ================== SAVING INDICATOR ================== */
  function setSavingState(state) {
    const indicator = $('#savingIndicator');
    indicator.removeClass('saving saved error');
    
    if (state === 'saving') {
      indicator.addClass('saving');
      indicator.find('.icon').attr('class', 'fas fa-cloud-upload-alt icon');
      indicator.find('.text').text('Saving...');
    } 
    else if (state === 'saved') {
      indicator.addClass('saved');
      indicator.find('.icon').attr('class', 'fas fa-cloud icon');
      indicator.find('.text').text('Saved');
      
      // Show timestamp if more than 5 seconds ago
      const now = Date.now();
      if (now - lastSaveTime > 5000) {
        const timeStr = new Date(lastSaveTime).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
        indicator.find('.text').text(`Saved at ${timeStr}`);
      }
    }
    else if (state === 'error') {
      indicator.addClass('error');
      indicator.find('.icon').attr('class', 'fas fa-cloud-rain icon');
      indicator.find('.text').text('Error saving');
    }
  }

  /* ================== AUTO-SAVE DRAFT ================== */
  function setupAutoSave() {
    // Clear any existing timeout
    if (autoSaveTimeout) clearTimeout(autoSaveTimeout);
    
    // Setup event listeners for changes
    $('#modalTemplateName, #modalEmailSubject').on('input', triggerAutoSave);
    
    if (window.CEBuilder && window.CEBuilder.editor) {
      // Check if editor is properly initialized
      const editor = window.CEBuilder.editor;
      if (editor && editor.domEvents && editor.ui && editor.ui.nodes) {
        editor.domEvents.on(editor.ui.nodes.holder, 'input', triggerAutoSave);
      } else {
        console.warn('Editor not fully initialized, using fallback');
        $('#templateBodyHtml').on('input', triggerAutoSave);
      }
    } else {
      console.warn('CEBuilder not available, using fallback');
      $('#templateBodyHtml').on('input', triggerAutoSave);
    }
  }

  function triggerAutoSave() {
  // 1) grab a “clean” copy of the editor DOM
    const cloneHtml = cleanCloneFromEdit();
    // 2) push it into your hidden textarea
    $('#ceDraftExport').val(cloneHtml);

    // 3) then debounce your draft‑save like before
    if (autoSaveTimeout) clearTimeout(autoSaveTimeout);
    autoSaveTimeout = setTimeout(autoSaveDraft, DEBOUNCE_TIME);
  }


  async function autoSaveDraft() {
    // Don't save if we're already saving
    if (isAutoSaving) return;
    
    const name = $('#modalTemplateName').val().trim() || 'Untitled Draft';
    const subject = $('#modalEmailSubject').val().trim() || 'No Subject';
    const body_html = getBodyHTML();
    const editable = $('#ceDraftExport').val();
    
    // Don't save empty content
    if (!body_html && !editable) return;
    
    isAutoSaving = true;
    setSavingState('saving');
    
    try {
      const payload = {
        body_html,
        editable_html: editable,
        name: name,
        subject: subject
      };
      
      const url = currentDraftUuid 
        ? `/api/template-drafts/${currentDraftUuid}`
        : '/api/template-drafts';
      const method = currentDraftUuid ? 'PATCH' : 'POST';
      
      const json = await apiRequest(url, { method, body: payload });
      
      // Store returned draft UUID for future updates
      if (json.draft && json.draft.draft_uuid) {
        currentDraftUuid = json.draft.draft_uuid;
        $('#modalDraftUuid').val(currentDraftUuid);
      }
      
      lastSaveTime = Date.now();
      setSavingState('saved');
    } catch (err) {
      console.error('Auto-save failed', err);
      setSavingState('error');
    } finally {
      isAutoSaving = false;
    }
  }

  /* ================== FETCH & RENDER ================== */
  async function fetchTemplates(){
    $('#refreshBtn').addClass('rotating');
    const $tbody=$('#templatesTable tbody');
    $tbody.html(`
      <tr><td colspan="6" class="py-5 text-center">
        <div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div>
      </td></tr>
    `);
    showLoader();
    try{
      const json=await apiRequest('/api/templates');
      templatesData=json.data||[];
      updateSummary();
      applyFilterAndRender();
    }catch(err){
      console.error(err);
      $tbody.html(`<tr><td colspan="6" class="py-5 text-center text-danger">Error loading templates</td></tr>`);
      handleApiError('Failed to load templates',err);
    }finally{
      $('#refreshBtn').removeClass('rotating');
      hideLoader();
    }
  }

  async function fetchDrafts(){
    const $tbody=$('#draftsTable tbody');
    $tbody.html(`
      <tr><td colspan="6" class="py-5 text-center">
        <div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div>
      </td></tr>
    `);
    showLoader();
    try{
      const json=await apiRequest('/api/template-drafts');
      draftsData=json.drafts||[];
      $('#totalDrafts').text(draftsData.length);
      renderDrafts();
    }catch(err){
      console.error(err);
      $tbody.html(`<tr><td colspan="6" class="py-5 text-center text-danger">Error loading drafts</td></tr>`);
      handleApiError('Failed to load drafts',err);
    }finally{
      hideLoader();
    }
  }

  function renderDrafts(){
    const $tbody=$('#draftsTable tbody').empty();
    
    if(!draftsData.length){
      return $tbody.html(`
        <tr><td colspan="6" class="py-5 text-center text-muted">
          <i class="fa-solid fa-inbox fa-2x mb-2"></i><br>No drafts found.
        </td></tr>
      `);
    }
    
    draftsData.forEach(d=>{
      $tbody.append(`
        <tr>
          <td>${escapeHtml(d.name)}</td>
          <td>${escapeHtml(d.subject)}</td>
          <td>${d.version || ''}</td>
          <td>${escapeHtml(d.status)}</td>
          <td>${formatDate(d.updated_at)}</td>
          <td class="table-actions">
            <button class="btn btn-sm btn-outline-secondary me-1" onclick="openDraftModal('${d.draft_uuid}')">
              <i class="fa-regular fa-pen-to-square"></i>
            </button>
            <button class="btn btn-sm btn-outline-primary me-1" onclick="copyDraft('${d.draft_uuid}')">
              <i class="fa-solid fa-copy"></i>
            </button>
            <button class="btn btn-sm btn-success me-1" onclick="approveDraft('${d.draft_uuid}')">
              <i class="fa-solid fa-check"></i>
            </button>
            <button class="btn btn-sm btn-outline-danger" onclick="deleteDraft('${d.draft_uuid}')">
              <i class="fa-solid fa-trash"></i>
            </button>
          </td>
        </tr>
      `);
    });
  }

  function updateSummary(){
    const total=templatesData.length;
    const active=templatesData.filter(t=>t.is_active).length;
    $('#totalTemplates').text(total);
    $('#activeTemplates').text(active);
    $('#inactiveTemplates').text(total-active);
  }

  $('#globalSearch').on('input',()=>{ currentPage=1; applyFilterAndRender(); });

  $('#exportCsvBtn').click(()=>{
    if(!filteredData.length){ Swal.fire('Info','No rows to export','info'); return; }
    let csv='Name,Subject,Status,Created,Updated\n';
    filteredData.forEach(t=>{
      csv+=`"${(t.name||'').replace(/"/g,'""')}",`+
           `"${(t.subject||'').replace(/"/g,'""')}",`+
           `${t.is_active?'Active':'Inactive'},`+
           `"${t.created_at?new Date(t.created_at).toLocaleString():''}",`+
           `"${t.updated_at?new Date(t.updated_at).toLocaleString():''}"\n`;
    });
    const blob=new Blob([csv],{type:'text/csv'});
    const url=URL.createObjectURL(blob);
    const a=document.createElement('a');
    a.href=url; a.download='templates.csv'; a.click();
    URL.revokeObjectURL(url);
    Swal.fire('Done','CSV exported','success');
  });

  function applyFilterAndRender(){
    const term=$('#globalSearch').val().toLowerCase();
    filteredData=templatesData.filter(t=>
      (t.name||'').toLowerCase().includes(term) ||
      (t.subject||'').toLowerCase().includes(term)
    );
    renderPage();
    renderPager();
  }

  function renderPage(){
    const start=(currentPage-1)*rowsPerPage;
    const pageData=filteredData.slice(start,start+rowsPerPage);
    const $tbody=$('#templatesTable tbody').empty();
    const pager=document.getElementById('pagerTemplates');

    if(!pageData.length){
      $tbody.html(`<tr><td colspan="6" class="text-center text-muted py-4">
        <i class="fa-solid fa-inbox fa-2x mb-2"></i><br>No templates found.
      </td></tr>`);
      pager.style.display='none';
      return;
    }

    pager.style.display='';
    pageData.forEach(t=>{
      $tbody.append(`
        <tr>
          <td>${escapeHtml(t.name)}</td>
          <td>${escapeHtml(t.subject)}</td>
          <td><span class="badge bg-${t.is_active?'success':'danger'}">${t.is_active?'Active':'Inactive'}</span></td>
          <td>${formatDate(t.created_at)}</td>
          <td>${formatDate(t.updated_at)}</td>
          <td class="table-actions">
            <button class="btn btn-sm btn-info me-1" onclick="previewTemplateApi('${t.template_uuid}')">
              <i class="fa-solid fa-eye"></i>
            </button>
            <button class="btn btn-sm btn-outline-secondary me-1" onclick="openTemplateModal('edit','${t.template_uuid}')">
              <i class="fa-regular fa-pen-to-square"></i>
            </button>
            <button class="btn btn-sm btn-outline-${t.is_active?'danger':'success'} me-1" onclick="toggleTemplate('${t.template_uuid}')">
              <i class="fa-solid ${t.is_active?'fa-ban':'fa-power-off'}"></i>
            </button>
            <button class="btn btn-sm btn-outline-danger" onclick="deleteTemplate('${t.template_uuid}')">
              <i class="fa-solid fa-trash"></i>
            </button>
          </td>
        </tr>
      `);
    });
  }

  function renderPager(){
    const total=filteredData.length;
    const pages=Math.ceil(total/rowsPerPage)||1;
    const ctr=document.getElementById('pagerTemplates');
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
  

  /* ================== MODAL & CRUD ================== */
  async function openTemplateModal(mode, uuid=''){
    templateAction=mode;
    currentDraftUuid = '';
    setSavingState('saved');

    if(mode==='edit'){
      const tpl=templatesData.find(t=>t.template_uuid===uuid);
      if(!tpl){ Swal.fire('Error','Template not found','error'); return; }
      $('#modalTemplateUuid').val(uuid);
      $('#modalTemplateName').val(tpl.name);
      $('#modalEmailSubject').val(tpl.subject);
      setBodyHTML(tpl.editable_html||'');
    }else{
      $('#modalTemplateUuid').val('');
      $('#modalTemplateName').val('');
      $('#modalEmailSubject').val('');
      setBodyHTML('');
    }

    $('#templateModalLabel').text(mode==='add'?'Add Template':'Edit Template');
    templateModal.show();
    
    // Setup auto-save when modal is shown
    setupAutoSave();
    
    // Immediately save a draft if there's content
    // if (getBodyHTML() || $('#modalTemplateName').val() || $('#modalEmailSubject').val()) {
    //   triggerAutoSave();
    // }
  }

  async function openDraftModal(uuid) {
    try {
      const draft = draftsData.find(d => d.draft_uuid === uuid);
      if (!draft) {
        Swal.fire('Error', 'Draft not found', 'error');
        return;
      }

      // clear any template UUID
      $('#modalTemplateUuid').val('');
      // set current draft UUID
      currentDraftUuid = draft.draft_uuid;
      $('#modalDraftUuid').val(currentDraftUuid);
      // populate name & subject
      $('#modalTemplateName').val(draft.name);
      $('#modalEmailSubject').val(draft.subject);
      // load the raw CE‑Builder HTML (editable source) into the editor
      setBodyHTML(draft.editable_html || '');
      
      setSavingState('saved');

      $('#templateModalLabel').text('Edit Draft');
      templateModal.show();
      
      // Setup auto-save when modal is shown
      setupAutoSave();
    } catch (err) {
      console.error('Error opening draft:', err);
      Swal.fire('Error', 'Failed to load draft: ' + err.message, 'error');
    }
  }

  // prevent default submit (Enter key etc.)
  $('#templateForm').on('submit', e => e.preventDefault());

  // SAVE BUTTON CLICK
  $('#modalTemplateSubmit').on('click', async function(){
    const uuid        = $('#modalTemplateUuid').val();
    const name        = $('#modalTemplateName').val().trim();
    const subject     = $('#modalEmailSubject').val().trim();
    const body_html   = getBodyHTML();               // rendered HTML
    const editable    = $('#ceDraftExport').val();    // raw CE‑Builder HTML

    if (!name || !subject) {
      Swal.fire('Error','Name & subject required','error');
      return;
    }

    Swal.fire({ title:'Saving…', allowOutsideClick:false, didOpen:()=>Swal.showLoading() });
    showLoader();

    try {
      // build payload
      const payload = {
        name,
        subject,
        body_html,
        editable_html: editable
      };

      const url    = templateAction === 'add'
        ? '/api/templates'
        : `/api/templates/${uuid}`;
      const method = templateAction === 'add' ? 'POST' : 'PUT';

      const json = await apiRequest(url, { method, body: payload });

      if (json.status === 'success') {
        templateModal.hide();
        await fetchTemplates();
        Swal.fire('Success', json.message || 'Saved','success');
      } else {
        Swal.fire('Error', json.message || 'Failed to save','error');
      }

    } catch (err) {
      handleApiError('Failed to save template', err);
    } finally {
      hideLoader();
    }
  });

  $('#saveDraftBtn').on('click', async function(){
    const name      = $('#modalTemplateName').val().trim() || 'Untitled Draft';
    const subject   = $('#modalEmailSubject').val().trim() || 'No Subject';
    const body_html = getBodyHTML();                    // rendered HTML snapshot
    const editable  = $('#ceDraftExport').val();       // raw CE‑Builder HTML

    Swal.fire({
      title: currentDraftUuid ? 'Updating draft…' : 'Saving draft…',
      allowOutsideClick: false,
      didOpen: () => Swal.showLoading()
    });
    showLoader();

    try {
      // build payload
      const payload = {
        body_html,
        editable_html: editable,
        name: name,
        subject: subject
      };

      const url    = currentDraftUuid
        ? `/api/template-drafts/${currentDraftUuid}`
        : '/api/template-drafts';
      const method = currentDraftUuid ? 'PATCH' : 'POST';

      const json = await apiRequest(url, { method, body: payload });

      // store returned draft UUID for future updates
      if (json.draft && json.draft.draft_uuid) {
        currentDraftUuid = json.draft.draft_uuid;
        $('#modalDraftUuid').val(currentDraftUuid);
      }

      lastSaveTime = Date.now();
      setSavingState('saved');
      
      Swal.fire('Success', currentDraftUuid ? 'Draft updated.' : 'Draft saved.', 'success');
    } catch (err) {
      console.error('Draft save failed', err);
      setSavingState('error');
      Swal.fire('Error', 'Failed to save draft', 'error');
    } finally {
      hideLoader();
    }
  });

  function copyDraft(uuid){
    Swal.fire({ 
      title: 'Copy this draft?', 
      icon: 'question', 
      showCancelButton: true 
    }).then(r => {
      if(r.isConfirmed) {
        apiRequest(`/api/template-drafts/${uuid}/copy`, {method: 'POST'})
          .then(fetchDrafts)
          .catch(() => Swal.fire('Error','Copy failed','error'));
      }
    });
  }

  function cleanCloneFromEdit() {
    if (!window.CEBuilder || !window.CEBuilder.editor) {
      console.warn('CEBuilder not available, using fallback');
      return $('#templateBodyHtml').val();
    }
    
    const clone = window.CEBuilder.editor.ui.nodes.holder.cloneNode(true);
    clone.querySelectorAll('.ce-block-handle, .ce-add-inside, .ce-drop-marker')
         .forEach(el => el.remove());
    clone.querySelectorAll('.ce-block').forEach(block => {
      block.classList.remove('ce-block','ce-selected');
      block.removeAttribute('data-block-id');
      block.removeAttribute('data-key');
    });
    clone.querySelectorAll('.ce-slot').forEach(slot => {
      slot.replaceWith(...slot.childNodes);
    });
    return clone.innerHTML;
  }

  async function approveDraft(uuid) {
  const result = await Swal.fire({
    title: 'Publish this draft?',
    text: 'This will update the template HTML and remove the draft.',
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: 'Yes, publish'
  });

  if (!result.isConfirmed) return;

  Swal.fire({
    title: 'Publishing…',
    allowOutsideClick: false,
    didOpen: () => Swal.showLoading()
  });

  try {
    await apiRequest(`/api/template-drafts/${uuid}/approve`, { method: 'POST' });

    // Refresh lists
    await fetchDrafts();
    await fetchTemplates();

    Swal.fire('Success', 'Draft published successfully.', 'success');
  } catch (err) {
    console.error('Approve failed', err);
    // Prefer detailed backend message if present
    let message = 'Failed to publish draft.';
    if (err.payload) {
      if (err.payload.message) message = err.payload.message;
      else if (err.payload.error) message = err.payload.error;
      else if (typeof err.payload === 'string') message = err.payload;
    } else if (err.message) {
      message = err.message;
    }

    Swal.fire('Error', message, 'error');
  }
}


  function deleteDraft(uuid){
    Swal.fire({ 
      title: 'Delete this draft?', 
      icon: 'warning', 
      showCancelButton: true 
    }).then(r => {
      if(r.isConfirmed) {
        apiRequest(`/api/template-drafts/${uuid}`, {method: 'DELETE'})
          .then(fetchDrafts)
          .catch(() => Swal.fire('Error','Delete failed','error'));
      }
    });
  }

  async function previewTemplateApi(uuid) {
    const json = await apiRequest(`/api/templates/${uuid}/preview`);
    Swal.fire({
      title: escapeHtml(json.data.subject),
      html: `
        <div class="preview-tools">
          <button class="device-btn active" data-device="desktop"><i class="fa-solid fa-desktop"></i></button>
          <button class="device-btn" data-device="tablet"><i class="fa-solid fa-tablet-screen-button"></i></button>
          <button class="device-btn" data-device="mobile"><i class="fa-solid fa-mobile-screen-button"></i></button>
        </div>
        <div class="preview-container desktop">
          <iframe id="previewFrame" sandbox srcdoc="${sanitizeHtml(json.data.body_html)}"></iframe>
        </div>
      `,
      width: '95%',
      showCloseButton: true,
      showConfirmButton: false,
      didOpen: () => {
        const container = Swal.getHtmlContainer();
        container.querySelectorAll('.device-btn').forEach(btn => {
          btn.addEventListener('click', e => {
            container.querySelectorAll('.device-btn').forEach(b => b.classList.remove('active'));
            e.currentTarget.classList.add('active');
            const device = e.currentTarget.dataset.device;
            const wrap = container.querySelector('.preview-container');
            wrap.className = 'preview-container ' + device;
          });
        });
      }
    });
  }

  function toggleTemplate(uuid){
    Swal.fire({ title:'Toggle status?', icon:'warning', showCancelButton:true })
      .then(async r=>{
        if(!r.isConfirmed) return;
        showLoader();
        Swal.fire({ title:'Updating…', allowOutsideClick:false, didOpen:()=>Swal.showLoading() });
        try{
          await apiRequest(`/api/templates/${uuid}/toggle`,{method:'PATCH'});
          await fetchTemplates();
          Swal.fire('Done','Updated','success');
        }catch(err){
          handleApiError('Toggle failed',err);
        }finally{
          hideLoader();
        }
      });
  }

  function deleteTemplate(uuid){
    Swal.fire({ title:'Delete this template?', icon:'warning', showCancelButton:true, confirmButtonText:'Delete' })
      .then(async r=>{
        if(!r.isConfirmed) return;
        showLoader();
        Swal.fire({ title:'Deleting…', allowOutsideClick:false, didOpen:()=>Swal.showLoading() });
        try{
          await apiRequest(`/api/templates/${uuid}`,{method:'DELETE'});
          await fetchTemplates();
          Swal.fire('Deleted','Template removed','success');
        }catch(err){
          handleApiError('Delete failed',err);
        }finally{
          hideLoader();
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
  function sanitizeHtml(html=''){
    return html.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
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
    showLoader();
    $('#refreshBtn').on('click', function() {
      fetchTemplates();
      fetchDrafts();
    });
    
    // Load initial data
    fetchTemplates();
    
    // Load drafts when tab is clicked
    $('#drafts-tab').on('shown.bs.tab', function() {
      fetchDrafts();
    });

    $('#bodyInstructionsBtn').on('click',()=>{
      Swal.fire({
        icon:'info',
        title:'Email Body Instructions',
        html:`<ul style="text-align:left">
                <li>Use the editor to create your HTML.</li>
                <li>Inline CSS if needed for email clients.</li>
              </ul>`,
        width:600, showCloseButton:true, showConfirmButton:false
      });
    });
    
    // Clear auto-save timeout when modal closes
    $('#templateModal').on('hidden.bs.modal', function() {
      if (autoSaveTimeout) {
        clearTimeout(autoSaveTimeout);
        autoSaveTimeout = null;
      }
      currentDraftUuid = '';
    });

    // Initialize editor when modal is shown
    $('#templateModal').on('shown.bs.modal', function() {
      window.CEBuilder.init().then(() => {
        // Now the editor is definitely in place
        editor.domEvents.on(editor.ui.nodes.holder, 'input', triggerAutoSave);
        // Also watch the hidden export field in case you ever change that:
        $('#ceDraftExport').on('input', triggerAutoSave);
      });
    });

  });
  </script>
</body>
</html>