{{-- resources/views/subscription/standalone_plans.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Subscription Plans</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />

  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"/>

  <!-- FontAwesome -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet" crossorigin="anonymous" />

  <!-- Razorpay SDK -->
  <script src="https://checkout.razorpay.com/v1/checkout.js"></script>

  <style>
    :root {
      --radius: 16px;
      --transition: .35s cubic-bezier(.4,.2,.2,1);
      --shadow-light: 0 25px 60px -10px rgba(31,45,61,0.06);
      --shadow-strong: 0 40px 90px -15px rgba(31,45,61,0.15);
    }
    * { box-sizing:border-box; }
    body {
      background: linear-gradient(135deg,#eef5fd,#ffffff);
      font-family: system-ui,-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;
      color: #1f2d3d;
      margin:0;
      padding:0;
      line-height:1.5;
    }
    .pricing-hero {
      max-width: 1080px;
      margin: 0 auto 2.5rem;
      padding-top: 1rem;
    }
    .pricing-hero-inner {
      display: flex;
      flex-wrap: wrap;
      gap: 1rem;
      justify-content: space-between;
      align-items: center;
      background: white;
      padding: 1.25rem 1.5rem;
      border-radius: 14px;
      box-shadow: 0 25px 60px -10px rgba(31,45,61,0.08);
    }
    .pricing-hero h1 {
      font-size: 2.25rem;
      letter-spacing: -0.5px;
      margin-bottom: .25rem;
    }
    .small-desc {
      color: #556b9a;
      margin-bottom: .5rem;
      margin-top: 0;
    }

    .segmented {
      display: inline-flex;
      border-radius: 999px;
      overflow: hidden;
      background: #f0f5fd;
      border: 1px solid #d7e3f2;
      align-items: center;
    }
    .segmented button {
      border: none;
      background: transparent;
      padding: .55rem 1rem;
      font-weight: 600;
      cursor: pointer;
      position: relative;
      font-size: .8rem;
      color: #556b9a;
      display: inline-flex;
      align-items: center;
      gap: 6px;
      transition: all .2s ease;
      min-width: 100px;
    }
    .segmented button.active {
      background: white;
      color: #1f2d3d;
      box-shadow: 0 10px 30px -5px rgba(31,45,61,0.12);
      border-radius: 999px;
    }
    .segmented button .spinner-border {
      width: 1rem;
      height: 1rem;
    }

    #plansContainer { position: relative; }
    .plan-card {
      position: relative;
      border-radius: var(--radius);
      overflow: hidden;
      background: transparent;
      transition: transform var(--transition), filter var(--transition), box-shadow .25s ease;
      will-change: transform;
    }
    .plan-inner {
      border-radius: calc(var(--radius) - 2px);
      padding: 1.75rem 1.5rem 1.5rem;
      display: flex;
      flex-direction: column;
      height: 100%;
      position: relative;
      background: white;
      box-shadow: var(--shadow-light);
      transition: transform .3s ease, box-shadow .3s ease;
    }
    .plan-card:hover .plan-inner {
      transform: translateY(-5px);
      box-shadow: var(--shadow-strong);
    }
    .price {
      font-size: 1.9rem;
      font-weight: 700;
      line-height: 1.1;
      margin: 0;
    }
    .original-price {
      text-decoration: line-through;
      opacity: .65;
      font-size: .75rem;
      margin-right: .5rem;
      position: relative;
      top: 2px;
    }
    .price-label {
      font-size: .65rem;
      color: #6c7a93;
      margin-top: 2px;
      text-transform: uppercase;
      letter-spacing: .5px;
    }
    .feature-list {
      list-style: none;
      padding: 0;
      margin: 0;
      flex-grow: 1;
      margin-bottom: .5rem;
    }
    .feature-list li {
      display: flex;
      align-items: center;
      gap: .75rem;
      margin-bottom: .5rem;
      font-size: .85rem;
    }
    .badge-discount {
      background: #0d6efd;
      color: white;
      font-size: .55rem;
      padding: .35em .75em;
      border-radius: 999px;
      font-weight: 600;
      display: inline-block;
      text-transform: uppercase;
      letter-spacing: .5px;
    }
    .btn-primary-gradient {
      background: linear-gradient(135deg,#6f9ff8,#b6d4fe);
      color: white;
      border: none;
      padding: .75rem 1rem;
      border-radius: 10px;
      font-weight: 600;
      width: 100%;
      cursor: pointer;
      transition: filter .2s ease, transform .2s ease;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: .4rem;
      font-size: .85rem;
      box-shadow: 0 12px 40px -10px rgba(111,159,248,0.6);
    }
    .btn-primary-gradient:disabled { opacity:.6; cursor:not-allowed; }
    .btn-primary-gradient:hover:not(:disabled) {
      filter: brightness(1.08);
      transform: translateY(-1px);
    }
    .btn-activated {
      background: #e3f6ea;
      color: #0f5132;
      border: none;
      padding: .75rem 1rem;
      border-radius: 10px;
      font-weight: 600;
      width: 100%;
      cursor: default;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: .5rem;
      font-size: .85rem;
    }
    .tier-0 .plan-inner { background: #f7fbf9; }
    .tier-1 .plan-inner { background: #f5f9fe; }
    .tier-2 .plan-inner { background: #f2f5ff; }
    .tier-3 .plan-inner { background: #faf2fc; }
    .loading-placeholder {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: .5rem;
      padding: 2rem 0;
    }
    .plan-title {
      display: flex;
      align-items: center;
      gap: .6rem;
      margin-bottom: .25rem;
      font-size: 1.2rem;
      font-weight: 700;
    }
    .plan-title i {
      background: rgba(13,110,253,.1);
      padding: 6px;
      border-radius: 50%;
      font-size: .75rem;
      color: #0d6efd;
      display: inline-flex;
      align-items: center;
      justify-content: center;
    }
    .meta-row {
      display: flex;
      gap: .75rem;
      flex-wrap: wrap;
      margin-top: .75rem;
      font-size: .65rem;
      color: #556b9a;
    }
    .feature-bullet {
      width: 10px;
      height: 10px;
      background: #ced7eb;
      border-radius: 50%;
      display: inline-block;
      margin-right: 8px;
      flex-shrink:0;
    }
    .check {
      color: #198754;
      font-weight: 700;
      font-size: 1rem;
      width: 1.1em;
      display: inline-flex;
      align-items: center;
      justify-content: center;
    }
    .cross {
      color: #dc3545;
      font-weight: 700;
      font-size: 1rem;
      width: 1.1em;
      display: inline-flex;
      align-items: center;
      justify-content: center;
    }
    .billing-badge {
      background: #3f51b5;
      color: white;
      padding: 4px 10px;
      border-radius: 999px;
      font-size: .55rem;
      font-weight: 600;
      display: inline-block;
    }
    .toast-wrapper {
      position: fixed;
      top: 1rem;
      right: 1rem;
      z-index: 1050;
      width: auto;
    }
    .toast {
      border-radius: 10px;
      padding: .75rem 1rem;
      font-size: .85rem;
      box-shadow: 0 20px 50px -10px rgba(0,0,0,0.08);
    }
    .current-plan-banner {
      background: #ffffff;
      border: 1px solid #d9e3f0;
      padding: 1rem 1.25rem;
      border-radius: 14px;
      margin-bottom: 1rem;
      display: flex;
      align-items: flex-start;
      gap: 1rem;
      flex-wrap: wrap;
      position: relative;
      box-shadow: 0 25px 60px -10px rgba(31,45,61,0.08);
    }
    .current-plan-badge {
      background: #0d6efd;
      color: white;
      padding: 6px 16px;
      border-radius: 999px;
      font-weight: 600;
      font-size: .75rem;
      display: inline-block;
      letter-spacing: .5px;
    }
    .plan-progress {
      height: 8px;
      border-radius: 4px;
      background: #f0f5fd;
      overflow: hidden;
      margin-top: 4px;
    }
    .plan-progress-inner {
      height: 100%;
      background: linear-gradient(135deg,#6f9ff8,#b6d4fe);
      border-radius: 4px;
      transition: width .35s ease;
    }
    .small-feature-label {
      font-weight: 600;
      margin-right: 4px;
    }
    .history-loading-spinner {
      margin-left: 6px;
    }
    /* history modal adjustments */
    .history-table-wrapper {
      max-height: 60vh;
      overflow: auto;
    }
    .pagination-buttons button {
      margin: 0 .125rem;
    }

    /* Active ribbon */
    .ribbon {
      position: absolute;
      top: 0;
      right: 0;
      overflow: hidden;
      width: 120px;
      height: 80px;
      pointer-events: none;
    }
    .ribbon span {
      position: absolute;
      display: block;
      width: 160px;
      padding: 8px 0;
      background: linear-gradient(135deg,#6f9ff8,#b6d4fe);
      color: #fff;
      font-size: .6rem;
      font-weight: 700;
      text-transform: uppercase;
      text-align: center;
      transform: rotate(45deg);
      top: 12px;
      right: -30px;
      box-shadow: 0 10px 30px -5px rgba(111,159,248,0.6);
      border-radius: 4px;
      letter-spacing: .5px;
    }
  </style>
</head>
<body>
  <div class="container py-5">
      <div class="pricing-hero mb-4">
        <div class="pricing-hero-inner">
          <div class="flex-grow-1">
            <h1 class="fw-bold mb-1">Subscription Plans</h1>
            <p class="small-desc mb-0">If you have an active subscription, only it is shown. Otherwise, choose a plan to get started.</p>
          </div>
          <div class="d-flex align-items-center gap-3 flex-wrap">
            <div class="segmented" id="toggleGroup">
              <button class="active" data-type="monthly"><i class="fa-solid fa-calendar-week"></i> Monthly</button>
              <button data-type="yearly"><i class="fa-solid fa-calendar-day"></i> Yearly</button>
              <button data-type="history" id="mergedHistoryBtn"><i class="fa-regular fa-clock-rotate-left"></i> History <span class="history-spinner d-none"><div class="spinner-border spinner-border-sm text-primary" role="status"></div></span></button>
            </div>
          </div>
        </div>
      </div>

      <div id="plansContainer" class="row g-4">
          <div class="col-12 text-center loading-placeholder" id="loadingPlaceholder">
              <div class="spinner-border text-primary" role="status"></div>
              <div class="mt-2 text-muted">Loading plans...</div>
          </div>
      </div>
  </div>

  <!-- Toast container -->
  <div class="toast-wrapper" id="toastWrapper"></div>

  <!-- History Modal -->
  <div class="modal fade" id="historyModal" tabindex="-1" aria-labelledby="historyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Payment & Subscription History</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div id="historyLoading" class="text-center py-4">
            <div class="spinner-border text-primary" role="status"></div>
            <div class="mt-2 text-muted">Loading history...</div>
          </div>
          <div id="historyContent" style="display:none;">
            <div class="d-flex justify-content-between mb-2 flex-wrap gap-2">
              <div class="small text-muted" id="historyMeta"></div>
              <div class="pagination-buttons">
                <button class="btn btn-sm btn-outline-primary" id="prevHistoryBtn">Prev</button>
                <button class="btn btn-sm btn-outline-primary" id="nextHistoryBtn">Next</button>
              </div>
            </div>
            <div class="history-table-wrapper">
              <table class="table table-bordered table-hover table-sm">
                <thead class="table-light">
                  <tr>
                    <th>Payment ID</th>
                    <th>Plan</th>
                    <th>Billing Cycle</th>
                    <th>Paid Amount</th>
                    <th>Payment Status</th>
                    <th>Subscription Status</th>
                    <th>Started</th>
                    <th>Expires</th>
                    <th>Total Days</th>
                    <th>Used Days</th>
                    <th>Remaining Days</th>
                    <th>Taken At</th>
                  </tr>
                </thead>
                <tbody id="historyTableBody">
                  <!-- rows -->
                </tbody>
              </table>
            </div>
          </div>
          <div id="historyEmpty" class="text-center text-muted py-4" style="display:none;">
            No history available.
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Bootstrap Bundle JS (includes Popper) -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>

  <script>
    function getAuthHeader() {
      const token = sessionStorage.getItem('token');
      if (!token) return {};
      return { 'Authorization': `Bearer ${token}` };
    }

    function showToast(message, type = 'info') {
      const base = document.createElement('div');
      const colorClass = type === 'error' ? 'bg-danger text-white' : (type === 'success' ? 'bg-success text-white' : 'bg-white text-dark');
      const id = 'toast_' + Math.random().toString(36).substring(2,7);
      const wrapper = document.getElementById('toastWrapper');
      base.id = id;
      base.className = `toast align-items-center ${colorClass} border shadow mb-2 d-flex`;
      base.setAttribute('role','alert');
      base.style.minWidth = '240px';
      base.innerHTML = `
        <div class="flex-grow-1 px-2">${message}</div>
        <button type="button" class="btn-close me-2 m-auto" aria-label="Close" onclick="document.getElementById('${id}').remove()"></button>
      `;
      wrapper.appendChild(base);
      setTimeout(() => { base.remove(); }, 5000);
    }

    const currencyFormatter = new Intl.NumberFormat('en-IN', {
        style: 'currency',
        currency: 'INR',
        minimumFractionDigits: 0
    });
    function formatCurrency(v) {
        return currencyFormatter.format(v);
    }
    function daysBetween(from, to) {
      const msPerDay = 1000 * 60 * 60 * 24;
      return (to - from) / msPerDay;
    }

    let billingCycle = 'monthly';
    let currentSubscription = null;
    let currentPlanRaw = null;
    let historyPage = 1;
    const historyPerPage = 20;

    const plansContainer = document.getElementById('plansContainer');
    const toggleGroup = document.getElementById('toggleGroup');
    const historyModal = new bootstrap.Modal(document.getElementById('historyModal'));
    const mergedHistoryBtn = document.getElementById('mergedHistoryBtn');
    const historySpinner = mergedHistoryBtn.querySelector('.history-spinner');

    function setLoading() {
      plansContainer.innerHTML = `
        <div class="col-12 text-center loading-placeholder" id="loadingPlaceholder">
          <div class="spinner-border text-primary" role="status"></div>
          <div class="mt-2 text-muted">Loading plans...</div>
        </div>
      `;
    }

    async function fetchMyPlan() {
        try {
            const res = await fetch('/api/plans/my', {
              headers: {
                'Content-Type': 'application/json',
                ...getAuthHeader()
              }
            });
            if (!res.ok) return null;
            const json = await res.json();
            if (json.status !== 'success') return null;
            const plan = json.data.plan || {};
            const sub = json.data.subscription || {};
            if (!sub || !sub.is_active) return null;

            const now = new Date();
            const expiresAt = sub.expires_at ? new Date(sub.expires_at) : null;
            const startedAt = sub.started_at ? new Date(sub.started_at) : null;
            let remainingDays = 0;
            const totalDays = sub.billing_cycle === 'yearly' ? 365 : 30;
            if (expiresAt && startedAt) {
              remainingDays = Math.max(0, daysBetween(now, expiresAt));
            }

            return {
              plan_id: plan.id,
              billing_cycle: sub.billing_cycle,
              amount_decimal: parseFloat(sub.amount_decimal || 0),
              title: plan.title || '—',
              expires_at: sub.expires_at,
              started_at: sub.started_at,
              raw_plan: plan,
              remaining_days: remainingDays,
              total_days: totalDays,
              discount: plan.discount || 0
            };
        } catch (err) {
            console.warn('Could not fetch current plan', err);
            return null;
        }
    }

    function computeDisplayPrice(plan, cycleOverride = null) {
        const cycle = cycleOverride || billingCycle;
        let base = parseFloat(plan.price) || 0;
        if (cycle === 'yearly') base *= 12;
        if (plan.discount) base = base * ((100 - parseFloat(plan.discount)) / 100);
        return parseFloat(base.toFixed(2));
    }

    function sortByPrice(plans) {
        return plans.slice().sort((a, b) => computeDisplayPrice(a) - computeDisplayPrice(b));
    }

    function safeLimit(val) {
      if (val === null || val === undefined || val === '') return 'Unlimited';
      return val;
    }

    function clearActiveToggle() {
      toggleGroup.querySelectorAll('button').forEach(b => b.classList.remove('active'));
    }

    async function fetchPlans() {
        setLoading();
        try {
            currentSubscription = await fetchMyPlan();
            if (currentSubscription) {
              currentPlanRaw = currentSubscription.raw_plan;
              // keep billingCycle as from subscription
              billingCycle = currentSubscription.billing_cycle || billingCycle;
              clearActiveToggle();
              // mark billing toggle accordingly
              toggleGroup.querySelector(`button[data-type="${billingCycle}"]`)?.classList.add('active');
            }

            const res = await fetch('/api/plans', {
              headers: {
                'Content-Type': 'application/json',
                ...getAuthHeader()
              }
            });
            if (res.status === 401) {
              showToast('Unauthorized. Please login again.', 'error');
              return;
            }
            const json = await res.json();
            if (json.status !== 'success') throw new Error(json.message || 'Failed to load');
            renderPlans(json.data);
          // ensure history button not active
          toggleGroup.querySelector('button[data-type="history"]')?.classList.remove('active');
        } catch (err) {
            plansContainer.innerHTML = `<div class="col-12 text-center text-danger">Failed to load plans: ${err.message}</div>`;
            console.error(err);
        }
    }

    function renderCurrentPlanBanner() {
      if (!currentSubscription) return '';
      const now = new Date();
      const expires = currentSubscription.expires_at ? new Date(currentSubscription.expires_at) : null;
      const started = currentSubscription.started_at ? new Date(currentSubscription.started_at) : null;
      const remaining = Math.max(0, currentSubscription.remaining_days);
      const total = currentSubscription.total_days || (currentSubscription.billing_cycle === 'yearly' ? 365 : 30);
      const percentLeft = total ? Math.min(100, Math.max(0, ((remaining / total) * 100).toFixed(1))) : 0;

      return `
        <div class="col-12">
          <div class="current-plan-banner">
            <div style="flex:1; min-width:220px;">
              <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                <div class="current-plan-badge">Current Plan</div>
                <div><strong>${currentSubscription.title}</strong> (${currentSubscription.billing_cycle || billingCycle})</div>
              </div>
              <div class="mb-1"><span class="small-feature-label">Started:</span> ${started ? started.toLocaleDateString() : '-'}</div>
              <div class="mb-2"><span class="small-feature-label">Expires:</span> ${expires ? expires.toLocaleDateString() : '-'}</div>
              <div class="mb-2">
                <div class="d-flex justify-content-between" style="font-size:.75rem;">
                  <div>Time left</div>
                  <div>${remaining.toFixed(1)} / ${total} days</div>
                </div>
                <div class="plan-progress">
                  <div class="plan-progress-inner" style="width: ${percentLeft}%;"></div>
                </div>
              </div>
            </div>
          </div>
        </div>
      `;
    }

    function renderPlans(rawPlans) {
        plansContainer.innerHTML = '';

        if (currentSubscription) {
          plansContainer.insertAdjacentHTML('beforeend', renderCurrentPlanBanner());

          const plan = currentSubscription.raw_plan || {};
          const displayCycle = currentSubscription.billing_cycle || billingCycle;
          const price = computeDisplayPrice(plan, displayCycle);
          let originalPriceVal = parseFloat(plan.price) || 0;
          if (displayCycle === 'yearly') originalPriceVal *= 12;
          const hasDiscount = !!plan.discount;
          const templateLimit = safeLimit(plan.template_limit);
          const sendLimit = safeLimit(plan.send_limit);
          const listLimit = safeLimit(plan.list_limit);
          const canAddMailer = plan.can_add_mailer;

          const col = document.createElement('div');
          col.className = 'col-md-6 col-lg-4 mb-3 position-relative';
          col.innerHTML = `
            <div class="plan-card tier-0">
              <div class="plan-inner">
                <div class="ribbon"><span>Active</span></div>
                <div class="plan-title">
                  <i class="fa-solid fa-cubes-stacked"></i>
                  <div>${plan.title || '—'}</div>
                </div>
                <div class="small text-muted mb-2">${plan.description || 'No description provided.'}</div>
                <div class="my-3">
                  <div class="d-flex align-items-baseline gap-2">
                    ${hasDiscount ? `<div class="original-price">${formatCurrency(originalPriceVal)}</div>` : ''}
                    <div class="price">${formatCurrency(price)}</div>
                  </div>
                  <div class="price-label">${displayCycle === 'monthly' ? 'per month' : 'per year'}</div>
                </div>
                <ul class="feature-list mb-3">
                  <li><span class="feature-bullet"></span> Templates <span class="ms-auto">${templateLimit}</span></li>
                  <li><span class="feature-bullet"></span> Emails / Sends <span class="ms-auto">${sendLimit}</span></li>
                  <li><span class="feature-bullet"></span> Lists <span class="ms-auto">${listLimit}</span></li>
                  <li>${canAddMailer ? `<span class="check"><i class="fa-solid fa-check"></i></span>` : `<span class="cross"><i class="fa-solid fa-xmark"></i></span>`} <span class="ms-1">Add Mailer</span> <span class="ms-auto">${canAddMailer ? 'Yes' : 'No'}</span></li>
                </ul>
                <div class="d-grid mb-2">
                  <button class="btn-activated" disabled><i class="fa-solid fa-check"></i> Activated</button>
                </div>
                <div class="meta-row">
                  ${hasDiscount ? `<div class="badge-discount">${plan.discount}% off</div>` : ''}
                  <div>Created: ${plan.created_at ? plan.created_at.split(' ')[0] : '-'}</div>
                  <div><span class="billing-badge">${displayCycle === 'monthly' ? 'Monthly' : 'Yearly'}</span></div>
                </div>
              </div>
            </div>
          `;
          plansContainer.appendChild(col);
          return;
        }

        // no subscription: show all
        let plans = sortByPrice(rawPlans);
        if (!plans.length) {
          plansContainer.innerHTML = `<div class="col-12 text-center text-muted py-5">No plans available.</div>`;
          return;
        }

        plans.forEach((plan, index) => {
            const displayCycle = billingCycle;
            const price = computeDisplayPrice(plan, displayCycle);
            let originalPriceVal = parseFloat(plan.price) || 0;
            if (displayCycle === 'yearly') originalPriceVal *= 12;
            const hasDiscount = !!plan.discount;
            const templateLimit = safeLimit(plan.template_limit);
            const sendLimit = safeLimit(plan.send_limit);
            const listLimit = safeLimit(plan.list_limit);
            const canAddMailer = plan.can_add_mailer;

            let tierClass = 'tier-3';
            if (index === 0) tierClass = 'tier-0';
            else if (index === 1) tierClass = 'tier-1';
            else if (index === 2) tierClass = 'tier-2';

            const col = document.createElement('div');
            col.className = 'col-md-6 col-lg-4 mb-3';

            col.innerHTML = `
              <div class="plan-card ${tierClass}">
                <div class="plan-inner">
                  <div class="plan-title">
                    <i class="fa-solid fa-cubes-stacked"></i>
                    <div>${plan.title || '—'}</div>
                  </div>
                  <div class="small text-muted mb-2">${plan.description || 'No description provided.'}</div>

                  <div class="my-3">
                    <div class="d-flex align-items-baseline gap-2">
                      ${hasDiscount ? `<div class="original-price">${formatCurrency(originalPriceVal)}</div>` : ''}
                      <div class="price">${formatCurrency(price)}</div>
                    </div>
                    <div class="price-label">${displayCycle === 'monthly' ? 'per month' : 'per year'}</div>
                  </div>

                  <ul class="feature-list mb-3">
                      <li><span class="feature-bullet"></span> Templates <span class="ms-auto">${templateLimit}</span></li>
                      <li><span class="feature-bullet"></span> Emails / Sends <span class="ms-auto">${sendLimit}</span></li>
                      <li><span class="feature-bullet"></span> Lists <span class="ms-auto">${listLimit}</span></li>
                      <li>${canAddMailer ? `<span class="check"><i class="fa-solid fa-check"></i></span>` : `<span class="cross"><i class="fa-solid fa-xmark"></i></span>`} <span class="ms-1">Add Mailer</span> <span class="ms-auto">${canAddMailer ? 'Yes' : 'No'}</span></li>
                  </ul>

                  <div class="d-grid mb-2">
                    <button class="btn-primary-gradient buy-now" data-plan-id="${plan.id}">
                      <i class="fa-solid fa-credit-card me-1"></i> Buy Now
                    </button>
                  </div>

                  <div class="meta-row">
                    ${hasDiscount ? `<div class="badge-discount">${plan.discount}% off</div>` : ''}
                    <div>Created: ${plan.created_at ? plan.created_at.split(' ')[0] : '-'}</div>
                    <div><span class="billing-badge">${displayCycle === 'monthly' ? 'Monthly' : 'Yearly'}</span></div>
                  </div>
                </div>
              </div>
            `;
            plansContainer.appendChild(col);
        });

        document.querySelectorAll('.buy-now').forEach(btn => {
            btn.addEventListener('click', async () => {
                const planId = btn.getAttribute('data-plan-id');
                if (!planId) return;
                const origHTML = btn.innerHTML;
                btn.disabled = true;
                btn.innerHTML = `<div class="spinner-border spinner-border-sm me-2" role="status"></div>Processing`;
                showToast('Creating order...', 'info');

                try {
                  const resp = await fetch('/api/payments/create-order', {
                    method: 'POST',
                    headers: {
                      'Content-Type': 'application/json',
                      ...getAuthHeader()
                    },
                    body: JSON.stringify({
                      plan_id: planId,
                      billing_cycle: billingCycle
                    })
                  });
                  if (!resp.ok) {
                    showToast('Order creation failed', 'error');
                    return;
                  }
                  const orderRes = await resp.json();
                  if (orderRes.status !== 'success') {
                    showToast(orderRes.message || 'Order creation failed', 'error');
                    return;
                  }
                  const { order_id, amount, currency, key } = orderRes.data;
                  const options = {
                    key,
                    amount: Math.round(amount * 100),
                    currency,
                    name: 'Your App',
                    description: `${billingCycle} subscription`,
                    order_id: order_id,
                    handler: async function(response) {
                      showToast('Verifying payment...', 'info');
                      const verifyResp = await fetch('/api/payments/verify', {
                        method: 'POST',
                        headers: {
                          'Content-Type': 'application/json',
                          ...getAuthHeader()
                        },
                        body: JSON.stringify({
                          razorpay_order_id: response.razorpay_order_id,
                          razorpay_payment_id: response.razorpay_payment_id,
                          razorpay_signature: response.razorpay_signature,
                        })
                      });
                      const verifyRes = await verifyResp.json();
                      if (verifyRes.status === 'success') {
                        showToast('Payment successful and verified!', 'success');
                        fetchPlans();
                      } else {
                        showToast('Verification failed', 'error');
                      }
                    },
                    prefill: { email: '', name: '' },
                    theme: { color: '#6f9ff8' }
                  };
                  const rzp = new Razorpay(options);
                  rzp.on('payment.failed', function(res) {
                    console.error(res);
                    showToast('Payment failed: ' + (res.error?.description || ''), 'error');
                  });
                  rzp.open();
                } catch (e) {
                  console.error(e);
                  showToast('Something went wrong', 'error');
                } finally {
                  btn.disabled = false;
                  btn.innerHTML = origHTML;
                }
            });
        });
    }

    // toggle / history integrated logic
    toggleGroup.addEventListener('click', (e) => {
      const btn = e.target.closest('button');
      if (!btn) return;
      const type = btn.getAttribute('data-type');
      clearActiveToggle();
      btn.classList.add('active');

      if (type === 'monthly' || type === 'yearly') {
        billingCycle = type;
        fetchPlans();
      } else if (type === 'history') {
        // show loading indicator inside history button
        historySpinner.classList.remove('d-none');
        fetchHistory(1).finally(() => {
          historySpinner.classList.add('d-none');
        });
        historyModal.show();
      }
    });

    // History fetch & render
    async function fetchHistory(page = 1) {
      historyPage = page;
      document.getElementById('historyLoading').style.display = '';
      document.getElementById('historyContent').style.display = 'none';
      document.getElementById('historyEmpty').style.display = 'none';
      try {
        const resp = await fetch(`/api/payment-subscription-history?page=${page}&per_page=${historyPerPage}`, {
          headers: {
            'Content-Type': 'application/json',
            ...getAuthHeader()
          }
        });
        if (!resp.ok) throw new Error('Failed to load');
        const json = await resp.json();
        if (json.status !== 'success') throw new Error(json.message || 'No data');
        const data = json.data || [];
        const meta = json.meta || {};
        const tbody = document.getElementById('historyTableBody');
        tbody.innerHTML = '';
        if (!data.length) {
          document.getElementById('historyEmpty').style.display = '';
          document.getElementById('historyMeta').textContent = '';
          document.getElementById('historyLoading').style.display = 'none';
          return;
        }
        data.forEach(entry => {
          const payment = entry.payment || {};
          const plan = entry.plan || {};
          const sub = entry.subscription || {};
          const duration = entry.duration_days != null ? entry.duration_days.toFixed(1) : '-';
          const used = entry.used_days != null ? entry.used_days.toFixed(1) : '-';
          const remaining = entry.remaining_days != null ? entry.remaining_days : '-';
          const takenAt = payment.created_at ? new Date(payment.created_at).toLocaleString() : '-';
          const started = sub.started_at ? new Date(sub.started_at).toLocaleDateString() : '-';
          const expires = sub.expires_at ? new Date(sub.expires_at).toLocaleDateString() : '-';

          const tr = document.createElement('tr');
          tr.innerHTML = `
            <td>${payment.id || '-'}</td>
            <td>${plan.title || '-'}</td>
            <td>${payment.billing_cycle || '-'}</td>
            <td>${payment.amount != null ? formatCurrency(payment.amount ?? payment.amount_decimal) : '-'}</td>
            <td>${payment.status || '-'}</td>
            <td>${sub.status || '-'}</td>
            <td>${started}</td>
            <td>${expires}</td>
            <td>${duration}</td>
            <td>${used}</td>
            <td>${remaining}</td>
            <td>${takenAt}</td>
          `;
          tbody.appendChild(tr);
        });

        document.getElementById('historyMeta').textContent = `Page ${meta.page || 1} of ${meta.total_pages || 1}, total payments: ${meta.total_payments || data.length}`;
        document.getElementById('prevHistoryBtn').disabled = (meta.page || 1) <= 1;
        document.getElementById('nextHistoryBtn').disabled = (meta.page || 1) >= (meta.total_pages || 1);
        document.getElementById('historyLoading').style.display = 'none';
        document.getElementById('historyContent').style.display = '';
      } catch (err) {
        console.error(err);
        document.getElementById('historyLoading').style.display = 'none';
        document.getElementById('historyEmpty').style.display = '';
        document.getElementById('historyMeta').textContent = '';
      }
    }

    document.getElementById('prevHistoryBtn').addEventListener('click', () => {
      if (historyPage > 1) fetchHistory(historyPage - 1);
    });
    document.getElementById('nextHistoryBtn').addEventListener('click', () => {
      fetchHistory(historyPage + 1);
    });

    // initial load
    fetchPlans();
  </script>
</body>
</html>