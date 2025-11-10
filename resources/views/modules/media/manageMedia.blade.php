<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Media Manager</title>

  <!-- Bootstrap CSS -->
  <link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
    rel="stylesheet"
  />
  <!-- Font Awesome -->
  <link
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    rel="stylesheet"
  />

  <meta name="csrf-token" content="{{ csrf_token() }}">

  <link rel="stylesheet" href="{{ asset('assets/css/common/main.css') }}">
  <link rel="stylesheet" href="{{ asset('assets/css/pages/manageMedia/manageMedia.css') }}">

  <style>
    .media-topbar {
      background:#ffffff;
      border:1px solid #e2e8f0;
      border-radius: .75rem;
      padding:.85rem 1.25rem;
      display:flex;
      align-items:center;
      gap:1rem;
      box-shadow:0 2px 4px -2px rgba(0,0,0,.06),0 4px 12px -4px rgba(0,0,0,.05);
      margin-bottom:1.25rem;
    }
    .media-topbar h1 {
      font-size:1.25rem;
      font-weight:600;
      margin:0;
      display:flex;
      align-items:center;
      gap:.55rem;
      color:#1e293b;
    }
    .media-topbar .back-btn {
      display:inline-flex;
      align-items:center;
      gap:.4rem;
    }
    .library-loading {
      position: absolute;
      inset: 0;
      background: rgba(255,255,255,0.8);
      display: flex;
      align-items: center;
      justify-content: center;
      z-index: 5;
      border-radius: .5rem;
    }
    .media-card {
      position: relative;
      background: #fff;
      border: 1px solid #e2e8f0;
      border-radius: .5rem;
      overflow: hidden;
      width: 220px;
      margin: 0.5rem;
      cursor: pointer;
      display: inline-block;
      vertical-align: top;
    }
    .thumb-container {
      position: relative;
      height: 120px;
      background: #f9f9fb;
      display: flex;
      align-items: center;
      justify-content: center;
      overflow: hidden;
    }
    .thumb-container img {
      max-width: 100%;
      max-height: 100%;
      object-fit: contain;
      display: block;
    }
    .fallback-icon {
      position: absolute;
      display: none;
      font-size: 2rem;
      color: #6c757d;
      align-items: center;
      justify-content: center;
      height: 100%;
      width: 100%;
      background: #f0f2f7;
      display: flex;
    }
    .card-body {
      padding: .5rem .75rem;
    }
    .overlay {
      position: absolute;
      bottom: 0;
      left: 0;
      right: 0;
      background: rgba(255,255,255,0.95);
      padding: .5rem;
      display: flex;
      flex-direction: column;
      gap: .25rem;
      font-size: .75rem;
    }
    .btn-copy {
      align-self: flex-start;
    }
    .no-media {
      text-align: center;
      padding: 4rem 1rem;
      color: #6c757d;
      font-size: 1rem;
    }
    .upload-item {
      position: relative;
      width: 100%;
    }
    .progress-wrapper {
      margin-top: .5rem;
    }
  </style>
</head>
<body class="bg-light">
  <div class="container py-5">

    <!-- Top message & back -->
    <div class="media-topbar">
      {{-- <button type="button" class="btn btn-outline-secondary back-btn" onclick="window.history.back()">
        <i class="fa-solid fa-arrow-left"></i> Back
      </button> --}}
      <h1>
        <i class="fa-solid fa-photo-film text-primary"></i>
        Your Media
      </h1>
      <div class="ms-auto small text-muted">
        Manage, upload and copy URLs for use in templates.
      </div>
    </div>

    <!-- Nav Tabs -->
    <ul class="nav nav-tabs" id="mediaTab">
      <li class="nav-item">
        <button class="nav-link active" type="button" id="library-tab" data-bs-toggle="tab" data-bs-target="#libraryPane">
          <i class="fa-solid fa-list"></i> Library
        </button>
      </li>
      <li class="nav-item">
        <button class="nav-link" type="button" id="upload-tab" data-bs-toggle="tab" data-bs-target="#uploadPane">
          <i class="fa-solid fa-upload"></i> Upload
        </button>
      </li>
    </ul>

    <div class="tab-content position-relative">

      <!-- Library Pane -->
      <div class="tab-pane fade show active" id="libraryPane">
        <div class="card-container mt-3 d-flex flex-wrap" id="mediaCards" style="min-height:200px; position: relative;">
          <div id="libraryLoadingOverlay" class="library-loading" style="display:none;">
            <div class="text-center">
              <div class="spinner-border" role="status" aria-hidden="true"></div>
              <div class="mt-2 small">Loading media...</div>
            </div>
          </div>
        </div>
        <div id="noMediaMsg" class="no-media" style="display:none;">
          <i class="fa-regular fa-folder-open fa-2x mb-2"></i>
          <div>No media found. Upload to get started.</div>
        </div>
      </div>

      <!-- Upload Pane -->
      <div class="tab-pane fade" id="uploadPane">
        <div class="mt-3">
          <div id="dropZone" class="drop-zone mb-3">
            <p>Drag &amp; drop files here, or</p>
            <button class="btn btn-outline-primary" type="button" id="pickFileBtn">
              <i class="fa-solid fa-folder-open"></i> Choose File
            </button>
            <input type="file" id="fileInput" multiple class="d-none" />
          </div>
          <ul class="list-group" id="uploadList"></ul>
        </div>
      </div>

    </div>
  </div>

  <!-- Dependencies -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <script>
    const token = sessionStorage.getItem('token');
    if (!token) location.href = '/';

    const API = {
      list:    () => fetch('/api/media', { headers: { 'Authorization': `Bearer ${token}` } }),
      uploadRaw:  (form, onProgress) => {
        return new Promise((resolve, reject) => {
          const xhr = new XMLHttpRequest();
          xhr.open('POST', '/api/media', true);
          xhr.setRequestHeader('Authorization', `Bearer ${token}`);
          xhr.upload.onprogress = function(e){
            if (e.lengthComputable && onProgress) {
              onProgress({ loaded: e.loaded, total: e.total });
            }
          };
          xhr.onreadystatechange = function(){
            if (xhr.readyState === 4) {
              try {
                const json = JSON.parse(xhr.responseText);
                if (xhr.status >=200 && xhr.status <300) resolve(json);
                else reject(json);
              } catch (err) {
                reject({ status:'error', message:'Invalid response' });
              }
            }
          };
          xhr.onerror = () => reject({ status:'error', message:'Network error' });
          xhr.send(form);
        });
      },
      remove:  id   => fetch(`/api/media/${id}`, { method:'DELETE', headers:{ 'Authorization': `Bearer ${token}` } }),
    };

    function fmtSize(b){
      if(b < 1024) return b + ' B';
      if(b < 1024*1024) return (b/1024).toFixed(1) + ' KB';
      return (b/(1024*1024)).toFixed(1) + ' MB';
    }

    async function loadLibrary(){
      const container = document.getElementById('mediaCards');
      const noMedia = document.getElementById('noMediaMsg');
      const overlay = document.getElementById('libraryLoadingOverlay');
      container.querySelectorAll('.media-card').forEach(n => n.remove());
      noMedia.style.display = 'none';
      overlay.style.display = 'flex';
      try {
        const res = await API.list().then(r=>r.json());
        overlay.style.display = 'none';
        if(res.status !== 'success') throw '';
        if (!Array.isArray(res.data) || res.data.length === 0) {
          noMedia.style.display = 'block';
          return;
        }
        res.data.forEach(item => {
          const card = document.createElement('div');
          card.className = 'media-card';

          card.innerHTML = `
            <div class="thumb-container">
              <div class="fallback-icon">
                <i class="fa-solid fa-file-image"></i>
              </div>
              <img loading="lazy" />
            </div>
            <div class="card-body">
              <div class="title text-truncate">${item.url.split('/').pop()}</div>
            </div>
            <div class="overlay">
              <div class="url text-break" style="word-break: break-all;">${item.url}</div>
              <div class="d-flex justify-content-between align-items-center">
                <div class="size">${fmtSize(item.size)}</div>
                <button class="btn btn-copy btn-sm" type="button">
                  <i class="fa-solid fa-copy me-1"></i>Copy
                </button>
              </div>
            </div>`;

          const img = card.querySelector('img');
          const fallback = card.querySelector('.fallback-icon');

          img.src = item.url;
          img.onload = () => {
            img.style.display = '';
            fallback.style.display = 'none';
          };
          img.onerror = () => {
            img.style.display = 'none';
            fallback.style.display = 'flex';
          };

          card.querySelector('.btn-copy').onclick = e => {
            e.stopPropagation();
            navigator.clipboard.writeText(item.url)
              .then(()=> Swal.fire({ icon:'success', title:'Copied!', timer:1200, showConfirmButton:false }))
              .catch(()=> Swal.fire('Error','Copy failed','error'));
          };

          card.onclick = () => window.open(item.url, '_blank');
          container.append(card);
        });
      } catch (err) {
        overlay.style.display = 'none';
        Swal.fire('Error','Could not load media','error');
      }
    }

    let pressTimer;
    document.addEventListener('mousedown', e => {
      const card = e.target.closest('.media-card');
      if (!card) return;
      pressTimer = setTimeout(async () => {
        const url = card.querySelector('.overlay .url').textContent;
        // Adjust id extraction depending on how you structure URLs (placeholder)
        const match = url.match(/\/media\/.*\/(\d+)\//); // change if needed
        const id = match ? parseInt(match[1]) : null;
        if (id) {
          const { isConfirmed } = await Swal.fire({
            title: 'Delete this file?',
            icon: 'warning',
            showCancelButton: true
          });
          if (isConfirmed) {
            const r2 = await API.remove(id).then(r=>r.json());
            if (r2.status === 'success') {
              Swal.fire('Deleted', r2.message, 'success');
              loadLibrary();
            } else {
              Swal.fire('Error', r2.message||'Delete failed','error');
            }
          }
        }
      }, 800);
    });
    document.addEventListener('mouseup', () => clearTimeout(pressTimer));

    const dropZone  = document.getElementById('dropZone'),
          fileInput = document.getElementById('fileInput'),
          uploadList= document.getElementById('uploadList');

    document.getElementById('pickFileBtn').onclick = () => fileInput.click();
    fileInput.onchange = () => handleFiles([...fileInput.files]);

    ['dragenter','dragover'].forEach(evt =>
      dropZone.addEventListener(evt, e => { e.preventDefault(); dropZone.classList.add('dragover'); })
    );
    ['dragleave','drop'].forEach(evt =>
      dropZone.addEventListener(evt, e => { e.preventDefault(); dropZone.classList.remove('dragover'); })
    );
    dropZone.addEventListener('drop', e => handleFiles([...e.dataTransfer.files]));

    function addUploadItem(name){
      const li = document.createElement('li');
      li.className = 'list-group-item upload-item';
      const wrapper = document.createElement('div');
      wrapper.className = 'd-flex justify-content-between align-items-start';
      const title = document.createElement('div');
      title.textContent = name;
      const statusBadge = document.createElement('span');
      statusBadge.className = 'badge bg-secondary ms-2';
      statusBadge.textContent = 'waiting';
      wrapper.append(title, statusBadge);
      li.append(wrapper);

      const progressWrapper = document.createElement('div');
      progressWrapper.className = 'progress-wrapper';
      progressWrapper.innerHTML = `
        <div class="progress" style="height:6px;">
          <div class="progress-bar" role="progressbar" style="width:0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
        </div>`;
      li.append(progressWrapper);

      uploadList.append(li);
      return { badge: statusBadge, progressEl: li.querySelector('.progress-bar') };
    }

    async function handleFiles(files){
      for (let f of files) {
        let { badge, progressEl } = addUploadItem(f.name);
        const form  = new FormData();
        form.append('file', f);

        try {
          const json = await API.uploadRaw(form, ({ loaded, total }) => {
            const pct = total ? Math.floor((loaded / total) * 100) : 0;
            progressEl.style.width = pct + '%';
            progressEl.setAttribute('aria-valuenow', pct);
          });
          if (json.status === 'success') {
            badge.className = 'badge bg-success ms-2';
            badge.textContent = 'done';
            progressEl.style.width = '100%';
          } else {
            badge.className = 'badge bg-danger ms-2';
            badge.textContent = 'error';
          }
        } catch (e) {
          badge.className = 'badge bg-danger ms-2';
          badge.textContent = 'error';
        }
      }
      await loadLibrary();
    }

    document.addEventListener('DOMContentLoaded', () => {
      loadLibrary();
      document.getElementById('library-tab')
        .addEventListener('shown.bs.tab', loadLibrary);
    });
  </script>
</body>
</html>
