<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Manage Campaign</title>

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
  <link rel="stylesheet" href="{{ asset('assets/css/common/main.css') }}">
  <link rel="stylesheet" href="{{ asset('assets/css/pages/manageCampaign/createCampaign.css') }}">
  <style>
    .wizard-progress {
      background: #e9ecef;
      height: 4px;
      border-radius: 2px;
      overflow: hidden;
    }
    .wizard-progress-bar {
      background: #0d6efd;
      width: 0;
      height: 100%;
      transition: width .3s;
    }
    .attachment-item .btn {
      border-top-left-radius: 0;
      border-bottom-left-radius: 0;
    }
    #subsSearch {
      max-width: 280px;
    }
    .wizard-card {
      max-width: 900px;
    }
    .step-content { display: none; }
    .step-content.active { display: block; }
  </style>
</head>
<body class="bg-light">
  <div class="container py-5">
    <div class="wizard-card mx-auto">
      <ul class="nav wizard-nav justify-content-between">
        <li class="nav-item">
          <span class="nav-link active" data-step="1">
            Mailing List
          </span>
        </li>
        <li class="nav-item">
          <span class="nav-link" data-step="2">
            Template &amp; Subject
          </span>
        </li>
        <li class="nav-item">
          <span class="nav-link" data-step="3">
            Details &amp; Attachments
          </span>
        </li>
        <li class="nav-item">
          <span class="nav-link" data-step="4">
            Schedule
          </span>
        </li>
      </ul>

      <div class="wizard-progress mb-4">
        <div class="wizard-progress-bar" id="wizardProgressBar"></div>
      </div>

      <div class="wizard-body bg-white p-4 rounded shadow-sm">
        <div class="d-flex justify-content-between align-items-center mb-4">
          <h1 class="h4"><i class="fa-solid fa-bullhorn me-2"></i>Create Campaign</h1>
          <button class="btn btn-outline-secondary" id="refreshBtn" type="button">
            <i class="fa-solid fa-arrows-rotate"></i>
          </button>
        </div>

        <form id="campaignForm" enctype="multipart/form-data">
          <!-- STEP 1: LIST + SUBSCRIBER PREVIEW -->
          <div class="step-content active" data-step="1">
            <div class="mb-3">
              <label class="form-label">Mailing List</label>
              <select id="list_id" name="list_id" class="form-select" required>
                <option value="">Loading…</option>
              </select>
            </div>

            <div id="listPreview" class="d-none">
              <div class="d-flex flex-wrap align-items-center gap-3 mb-2">
                <p class="mb-0">
                  <strong>Total Subscribers:</strong>
                  <span id="totalSubsCount">0</span>
                </p>
                <div class="ms-auto">
                  <input type="text" id="subsSearch" class="form-control form-control-sm"
                         placeholder="Search name or email…" autocomplete="off">
                </div>
              </div>

              <div id="subsLoading" class="text-center my-3 d-none">
                <div class="spinner-border spinner-border-sm" role="status">
                  <span class="visually-hidden">Loading...</span>
                </div>
                Loading subscribers…
              </div>

              <div class="table-responsive mb-2 d-none" id="subsTableContainer">
                <table class="table table-sm table-bordered">
                  <thead class="table-light">
                    <tr><th>Name</th><th>Email</th></tr>
                  </thead>
                  <tbody id="subsTableBody"></tbody>
                </table>
              </div>
              <nav>
                <ul class="pagination pagination-sm justify-content-center d-none" id="subsPager"></ul>
              </nav>
              <div id="subsNoResults" class="alert alert-warning py-2 px-3 d-none">
                No matching subscribers found.
              </div>
            </div>
          </div>

          <!-- STEP 2: TEMPLATE + SUBJECT OVERRIDE -->
          <div class="step-content" data-step="2">
            <div class="mb-3">
              <label class="form-label">Template</label>
              <select id="template_id" name="template_id" class="form-select" required>
                <option value="">Loading…</option>
              </select>
            </div>
            <div id="tmplPreview" class="border p-3 mb-3 rounded d-none">
              <div class="preview-tools">
                <button class="device-btn active" data-device="desktop">
                  <i class="fa-solid fa-desktop"></i>
                </button>
                <button class="device-btn" data-device="tablet">
                  <i class="fa-solid fa-tablet-screen-button"></i>
                </button>
                <button class="device-btn" data-device="mobile">
                  <i class="fa-solid fa-mobile-screen-button"></i>
                </button>
              </div>
              <div class="campaign-preview-container desktop">
                <h5 id="previewSubject" class="mb-3"></h5>
                <iframe
                  id="previewFrame"
                  class="w-100 flex-grow-1"
                  style="border:none;"
                ></iframe>
              </div>
            </div>
            
            <div class="mb-3 d-none" id="subjectOverrideContainer">
              <label class="form-label">Subject Override</label>
              <input type="text" id="subject_override" name="subject_override" class="form-control">
            </div>
          </div>

            <!-- STEP 3: DETAILS & ATTACHMENTS -->
          <div class="step-content" data-step="3">
            <div class="mb-3">
              <label class="form-label">Campaign Title</label>
              <input type="text" id="title" name="title" class="form-control" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Reply-To Address</label>
              <input type="email" id="reply_to_address" name="reply_to_address" class="form-control" required>
            </div>
            <div class="mb-3">
              <label class="form-label">From Email Address</label>
              <select id="from_address" name="from_address" class="form-select" required></select>
            </div>
            <div class="mb-3">
              <label class="form-label">From Name</label>
              <input type="text" id="from_name" name="from_name" class="form-control" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Attachments</label><br>
              <button type="button" class="btn btn-outline-secondary btn-sm mb-2" id="addAttachmentBtn">
                <i class="fa-solid fa-paperclip me-1"></i>Add Attachment
              </button>
              <div id="attachmentsContainer"></div>
            </div>
          </div>

          <!-- STEP 4: SCHEDULE -->
          <div class="step-content" data-step="4">
            <div class="mb-3">
              <label class="form-label">Send Option</label><br>
              <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="schedule_option" id="optNow" value="now" checked>
                <label class="form-check-label" for="optNow">Now</label>
              </div>
              <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="schedule_option" id="optSch" value="scheduled">
                <label class="form-check-label" for="optSch">Scheduled</label>
              </div>
            </div>

            <div class="mb-3 d-none" id="dtPicker">
              <label class="form-label">Date &amp; Time</label>
              <input type="datetime-local" id="scheduled_at" name="scheduled_at" class="form-control" step="1">
              <div class="form-text">
                Select local time for sending. Must be in the future.
              </div>
            </div>
          </div>

          <div class="wizard-footer d-flex justify-content-between">
            <button type="button" class="btn btn-secondary" id="prevBtn" disabled>
              <i class="fa-solid fa-arrow-left me-1"></i>Previous
            </button>
            <button type="button" class="btn btn-primary" id="nextBtn">
              Next <i class="fa-solid fa-arrow-right ms-1"></i>
            </button>
            <button type="submit" class="btn btn-success d-none" id="submitBtn">
              <span class="spinner-border spinner-border-sm me-2 d-none" id="submitSpinner"></span>
              Create Campaign
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Dependencies -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <script>
  $(function(){
    const token = sessionStorage.getItem('token');
    $('#refreshBtn').click(()=> location.reload());

    const envFromAddress = '{{ config("mail.from.address") }}';
    const envFromName    = '{{ config("mail.from.name") }}';

    // State for subscribers (Step 1)
    let currentListSubs = [];  // full
    let filteredSubs    = [];  // filtered
    let currentSubsPage = 1;
    const subsPerPage   = 5;

    let step = 1;

    /** Utility: convert Date -> "YYYY-MM-DDTHH:MM" in local time */
    function toLocalDateTimeValue(dateObj){
      const tzOffsetMs = dateObj.getTimezoneOffset() * 60000;
      const localISO = new Date(dateObj.getTime() - tzOffsetMs).toISOString().slice(0,16);
      return localISO;
    }

    function showStep(n) {
      step = n;
      $('.step-content').removeClass('active')
        .filter('[data-step="'+n+'"]').addClass('active');
      $('.wizard-nav .nav-link').removeClass('active')
        .filter('[data-step="'+n+'"]').addClass('active');

      $('#prevBtn').prop('disabled', n===1);
      if(n < 4){
        $('#nextBtn').show();
        $('#submitBtn').addClass('d-none');
      } else {
        $('#nextBtn').hide();
        $('#submitBtn').removeClass('d-none');
      }

      const pct = ((n-1)/3)*100; // 0, 33.33, 66.66, 100
      $('#wizardProgressBar').css('width', pct+'%');
    }

    function loadLists(){
      $.ajax({ url:'/api/lists', headers:{Authorization:'Bearer '+token} })
       .done(res=>{
         const sel = $('#list_id').empty().append('<option value="">-- select --</option>');
         res.data.forEach(l=> sel.append(`<option value="${l.id}">${l.title}</option>`));
       });
    }

    function loadTemplates(){
      $.ajax({ url:'/api/templates', headers:{Authorization:'Bearer '+token} })
       .done(res=>{
         const sel = $('#template_id').empty().append('<option value="">-- select --</option>');
         res.data.forEach(t=>{
           sel.append(`<option value="${t.id}"
                         data-subject="${t.subject}"
                         data-body="${encodeURIComponent(t.body_html)}">
                         ${t.name}
                       </option>`);
         });
       });
    }

    // function loadMailerEmails(){
    //   $.ajax({ url:'/api/mailer', headers:{Authorization:'Bearer '+token} })
    //    .done(res=>{
    //      const sel = $('#from_address').empty();
    //      sel.append(`<option value="${envFromAddress}">${envFromAddress} (Env default)</option>`);
    //      res.data.forEach(m=>{
    //        sel.append(`<option value="${m.from_address}" data-name="${m.from_name}">
    //                     ${m.from_address}
    //                   </option>`);
    //      });
    //      const dbDef = res.data.find(m=>m.is_default);
    //      sel.val(dbDef?dbDef.from_address:envFromAddress);
    //    });
    // }
//     function loadMailerEmails(){
//   $.ajax({
//     url: '/api/mailer',
//     headers: { Authorization: 'Bearer ' + token }
//   }).always(() => {
//     const sel = $('#from_address').empty();
//     sel.append(
//       `<option value="${envFromAddress}">${envFromAddress} (Env default)</option>`
//     );
//     // Force-select env default (redundant but explicit)
//     sel.val(envFromAddress);
//   });
// }
function loadMailerEmails(){
  $.ajax({
    url: '/api/mailer',
    headers: { Authorization: 'Bearer ' + token }
  })
  .done(res => {
    const sel = $('#from_address').empty();

    // find the one mailer marked `is_default`
    const dbDefault = res.data.find(m => m.is_default);

    if (dbDefault) {
      sel.append(
        `<option value="${dbDefault.from_address}" data-name="${dbDefault.from_name}">` +
          `${dbDefault.from_address}` +
        `</option>`
      );
      sel.val(dbDefault.from_address);
    } else {
      // no default in DB → show a placeholder
      sel.append(
        `<option disabled>No default mailer configured</option>`
      );
    }
  })
  .fail(() => {
    // on AJAX error → show an error placeholder
    const sel = $('#from_address').empty();
    sel.append(
      `<option disabled>Unable to load mailers</option>`
    );
  });
}




    // List change -> fetch subscribers
    $('#list_id').change(function(){
      const listId = $(this).val();
      if(!listId){
        $('#listPreview').addClass('d-none');
        return;
      }

      $('#listPreview').removeClass('d-none');
      $('#subsLoading').removeClass('d-none');
      $('#subsTableContainer, #subsPager, #subsNoResults').addClass('d-none');
      $('#subsSearch').val('');

      $.ajax({
        url:`/api/lists/${listId}/users`,
        headers:{Authorization:'Bearer '+token}
      }).done(res=>{
        currentListSubs = res.data || [];
        filteredSubs    = currentListSubs.slice();
        currentSubsPage = 1;
        $('#totalSubsCount').text(currentListSubs.length);
        renderSubsTable();
        renderSubsPager();
        $('#subsLoading').addClass('d-none');
        $('#subsTableContainer').toggleClass('d-none', filteredSubs.length===0);
        $('#subsPager').toggleClass('d-none', filteredSubs.length<=subsPerPage);
      }).fail(()=>{
        $('#subsLoading').addClass('d-none');
        Swal.fire('Error','Failed to load subscribers','error');
      });
    });

    // Live search
    $('#subsSearch').on('input', function(){
      const q = $(this).val().trim().toLowerCase();
      if(!q){
        filteredSubs = currentListSubs.slice();
      } else {
        filteredSubs = currentListSubs.filter(u =>
          (u.name && u.name.toLowerCase().includes(q)) ||
          (u.email && u.email.toLowerCase().includes(q))
        );
      }
      currentSubsPage = 1;
      renderSubsTable();
      renderSubsPager();
    });

    function renderSubsTable(){
      const $no = $('#subsNoResults');
      if(filteredSubs.length === 0){
        $('#subsTableBody').empty();
        $('#subsTableContainer, #subsPager').addClass('d-none');
        $no.removeClass('d-none');
        return;
      }
      $no.addClass('d-none');
      $('#subsTableContainer').removeClass('d-none');

      const start = (currentSubsPage-1)*subsPerPage;
      const slice = filteredSubs.slice(start, start+subsPerPage);
      const body  = slice.map(u=>`
        <tr>
          <td>${u.name ?? ''}</td>
          <td>${u.email ?? ''}</td>
        </tr>
      `).join('');
      $('#subsTableBody').html(body);
    }

    function renderSubsPager(){
      const totalPages = Math.ceil(filteredSubs.length/subsPerPage)||1;
      const $p = $('#subsPager').empty();
      if(totalPages <= 1){
        $p.addClass('d-none');
        return;
      }
      $p.removeClass('d-none');

      function btn(html, p, disabled){
        const el = $(`<li class="page-item${disabled?' disabled':''}">
                        <a class="page-link" href="#">${html}</a>
                      </li>`);
        if(!disabled){
          el.on('click', e=>{
            e.preventDefault();
            currentSubsPage = p;
            renderSubsTable();
            renderSubsPager();
          });
        }
        return el;
      }

      $p.append(btn('&laquo;',1,currentSubsPage===1))
        .append(btn('&lsaquo;',currentSubsPage-1,currentSubsPage===1))
        .append(`<li class="page-item disabled"><span class="page-link">${currentSubsPage}/${totalPages}</span></li>`)
        .append(btn('&rsaquo;',currentSubsPage+1,currentSubsPage===totalPages))
        .append(btn('&raquo;',totalPages,currentSubsPage===totalPages));
    }

    // Template change preview
    $('#template_id').change(function(){
      const opt = $(this).find(':selected');
      if (opt.val()) {
        $('#previewSubject').text(opt.data('subject') || '');
        $('#tmplPreview').removeClass('d-none');
        $('#previewFrame').attr('srcdoc', decodeURIComponent(opt.attr('data-body')));
        $('#subjectOverrideContainer').removeClass('d-none');
        initWizardPreviewTools();  // ← initialize toggles
      } else {
        $('#tmplPreview, #subjectOverrideContainer').addClass('d-none');
      }
    });

    function initWizardPreviewTools(){
      const $tools = $('#tmplPreview .preview-tools');
      $tools.find('.device-btn').off('click').on('click', function(){
        $tools.find('.device-btn').removeClass('active');
        $(this).addClass('active');
        const dev = $(this).data('device');
        $('#tmplPreview .campaign-preview-container')
          .removeClass('desktop tablet mobile')
          .addClass(dev);
      });
    }


    // Add attachment
    $('#addAttachmentBtn').click(()=>{
      $('#attachmentsContainer').append(`
        <div class="input-group mb-2 attachment-item">
          <input type="file" name="attachments[]" class="form-control" />
          <button type="button" class="btn btn-outline-danger deleteAttachmentBtn">
            <i class="fa-solid fa-trash"></i>
          </button>
        </div>
      `);
    });
    // Delete attachment (delegate)
    $('#attachmentsContainer').on('click', '.deleteAttachmentBtn', function(){
      $(this).closest('.attachment-item').remove();
    });

    // Navigation buttons
    $('#nextBtn').click(()=>{
      const $curr = $('.step-content.active');
      // Basic required check
      const invalid = $curr.find('[required]').filter((i,el)=>!$(el).val()).length;
      if(invalid){
        Swal.fire('Error','Please fill all required fields','error');
        return;
      }
      if(step < 4) showStep(step+1);
    });

    $('#prevBtn').click(()=>{
      if(step > 1) showStep(step-1);
    });

    // Schedule toggle
    $('input[name="schedule_option"]').on('change', function(){
      const isScheduled = this.value === 'scheduled';
      const $wrap  = $('#dtPicker');
      const $input = $('#scheduled_at');

      if(isScheduled){
        $wrap.removeClass('d-none');
        // Default now + 2 minutes
        const defaultDate = new Date(Date.now() + 2*60*1000);
        $input.val(toLocalDateTimeValue(defaultDate))
              .prop('required', true)
              .attr('min', toLocalDateTimeValue(new Date()));
      } else {
        $wrap.addClass('d-none');
        $input.val('')
              .prop('required', false)
              .removeAttr('min');
      }
    });
    // Initialize schedule state
    $('input[name="schedule_option"]:checked').trigger('change');

    // Form submit
    $('#campaignForm').submit(function(e){
      e.preventDefault();

      // Additional validation: if scheduled, ensure future
      const opt = $('input[name="schedule_option"]:checked').val();
      if(opt === 'scheduled'){
        const val = $('#scheduled_at').val();
        if(!val){
          return Swal.fire('Error','Please select a scheduled date/time','error');
        }
        const picked = new Date(val);
        if(picked.getTime() <= Date.now()){
          return Swal.fire('Error','Scheduled time must be in the future','error');
        }
      }

      $('#submitSpinner').removeClass('d-none');
      const formData = new FormData(this);

      if(opt === 'now'){
        formData.set('schedule_option','now');
        formData.set('scheduled_at', new Date().toISOString());
      }

      $.ajax({
        url:'/api/campaign',
        method:'POST',
        headers:{Authorization:'Bearer '+token},
        processData:false,
        contentType:false,
        data: formData
      })
      .done(()=>{
        Swal.fire('Success','Campaign created','success')
          .then(()=> location.reload());
      })
      .fail(err=>{
        $('#submitSpinner').addClass('d-none');
        Swal.fire('Error', err.responseJSON?.message || 'Request failed', 'error');
      });
    });

    // Initial loads
    loadLists();
    loadTemplates();
    loadMailerEmails();
    showStep(1);
  });
  </script>
</body>
</html>
