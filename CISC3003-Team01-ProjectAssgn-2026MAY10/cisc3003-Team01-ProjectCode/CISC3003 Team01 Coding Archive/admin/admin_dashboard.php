<?php
declare(strict_types=1);
require_once __DIR__ . '/_guard.php';
if (!isset($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token']) || $_SESSION['csrf_token'] === '') {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = (string)$_SESSION['csrf_token'];
$layoutBase = '../';
$activePage = 'admin';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php require __DIR__ . '/../includes/favicon_links.php'; ?>
    <title data-i18n="adminTitle">Admin Portal</title>
    <meta name="csrf-token" content="<?= htmlspecialchars($csrfToken) ?>">
    <link rel="stylesheet" href="../assets/um_landing.css?v=<?= file_exists(__DIR__ . '/../assets/um_landing.css') ? filemtime(__DIR__ . '/../assets/um_landing.css') : time() ?>">
    <link rel="stylesheet" href="../assets/home-landing.css?v=<?= file_exists(__DIR__ . '/../assets/home-landing.css') ? filemtime(__DIR__ . '/../assets/home-landing.css') : time() ?>">
    <link rel="stylesheet" href="../style.css?v=<?= file_exists(__DIR__ . '/../style.css') ? filemtime(__DIR__ . '/../style.css') : time() ?>">
    <link rel="stylesheet" href="../assets/site-footer.css?v=<?= file_exists(__DIR__ . '/../assets/site-footer.css') ? filemtime(__DIR__ . '/../assets/site-footer.css') : time() ?>">
</head>
<body>
<?php require __DIR__ . '/../includes/public_header.php'; ?>

<main class="layout">
    <aside class="sidebar">
        <h3 data-i18n="adminNavigation">Admin Navigation</h3>
        <ul class="nav-list">
            <li><button class="nav-btn" data-target="vehicleSection" data-i18n="vehicleMgmt">Vehicle Management</button></li>
            <li><button class="nav-btn" data-target="staffSection" data-i18n="staffMgmt">Staff Accounts</button></li>
            <li><button class="nav-btn" data-target="stationSection" data-i18n="stationStatus">Station Status</button></li>
            <li><button class="nav-btn" data-target="abnormalSection" data-i18n="abnormalOrders">Abnormal Orders</button></li>
            <li><button class="nav-btn" data-target="paymentSection" data-i18n="paymentPending">Payment Pending</button></li>
            <li><button class="nav-btn" data-target="reportSection" data-i18n="reports">Reports</button></li>
        </ul>
    </aside>

    <section class="content-card">
        <div id="vehicleSection" class="section active">
            <h2 data-i18n="vehicleDispatch">Vehicle Dispatch & Status Update</h2>
            <table>
                <thead>
                <tr>
                    <th>ID</th><th data-i18n="model">Model</th><th data-i18n="brand">Brand</th><th data-i18n="status">Status</th><th data-i18n="station">Station</th><th data-i18n="action">Action</th>
                </tr>
                </thead>
                <tbody id="vehicleTbody"></tbody>
            </table>
        </div>

        <div id="stationSection" class="section">
            <h2 data-i18n="stationUsageTitle">Station Capacity & Usage</h2>
            <table>
                <thead>
                <tr><th data-i18n="station">Station</th><th data-i18n="capacity">Capacity</th><th data-i18n="occupied">Occupied</th><th data-i18n="usageRate">Usage</th></tr>
                </thead>
                <tbody id="stationTbody"></tbody>
            </table>
        </div>

        <div id="staffSection" class="section">
            <h2 data-i18n="staffMgmtTitle">Staff Account Management</h2>
            <p class="muted meta" data-i18n="staffFormHint">Form fields below show hint text only until you type. The table lists staff accounts stored in the database.</p>
            <p class="muted meta staff-rules" data-i18n="staffPasswordRules">Required fields: Campus ID, full name, email, and password. Password must be at least 8 characters with both letters and numbers. Demo seed data only includes one staff account—add more here or extend database/seed_demo_data.sql.</p>
            <div class="grid-2">
                <input id="staffCampusID" autocomplete="off" data-i18n-placeholder="staffCampusPlaceholder" placeholder="Campus ID">
                <input id="staffFullName" autocomplete="off" data-i18n-placeholder="staffNamePlaceholder" placeholder="Full name">
                <input id="staffEmail" type="email" autocomplete="off" data-i18n-placeholder="staffEmailPlaceholder" placeholder="name@example.com">
                <input id="staffPhone" autocomplete="off" data-i18n-placeholder="staffPhonePlaceholder" placeholder="Phone (optional)">
                <input id="staffPassword" type="password" autocomplete="new-password" data-i18n-placeholder="staffPasswordPlaceholder" placeholder="Password">
                <button id="addStaffBtn" type="button" data-i18n="addStaff">Add Staff</button>
            </div>
            <table class="mt-10">
                <thead>
                <tr>
                    <th>ID</th><th data-i18n="campusIdCol">Campus ID</th><th data-i18n="user">User</th><th data-i18n="emailCol">Email</th><th data-i18n="status">Status</th><th data-i18n="action">Action</th>
                </tr>
                </thead>
                <tbody id="staffTbody"></tbody>
            </table>
        </div>

        <div id="abnormalSection" class="section">
            <h2 data-i18n="abnormalTitle">Abnormal Orders (>24h)</h2>
            <table>
                <thead>
                <tr><th data-i18n="orderId">Order ID</th><th data-i18n="user">User</th><th data-i18n="vehicle">Vehicle</th><th data-i18n="elapsedHours">Elapsed (Hours)</th><th data-i18n="action">Action</th></tr>
                </thead>
                <tbody id="abnormalTbody"></tbody>
            </table>
        </div>

        <div id="paymentSection" class="section">
            <h2 data-i18n="paymentPendingTitle">Payment Pending Orders</h2>
            <button class="secondary" id="reloadPaymentBtn" data-i18n="paymentReload">Reload</button>
            <table>
                <thead>
                <tr>
                    <th data-i18n="orderId">Order ID</th>
                    <th data-i18n="user">User</th>
                    <th data-i18n="vehicle">Vehicle</th>
                    <th data-i18n="fee">Fee</th>
                    <th data-i18n="walletBalance">Wallet Balance</th>
                    <th data-i18n="status">Status</th>
                </tr>
                </thead>
                <tbody id="paymentPendingTbody"></tbody>
            </table>
        </div>

        <div id="reportSection" class="section">
            <h2 data-i18n="reportsTitle">Reports</h2>
            <p class="muted meta" data-i18n="reportKpiHint">KPI counts orders whose start time falls in the date range, grouped by start station. Revenue sums order fees (active rentals may show 0). Pick the current month—or dates when you actually rented—or import extra demo rows from database/seed_demo_data.sql.</p>
            <div class="grid-3" id="kpiWrap"></div>
            <div class="controls mt-10">
                <input id="reportFrom" type="date">
                <input id="reportTo" type="date">
                <button id="loadReportBtn" data-i18n="loadReportBtn">Load KPI Report</button>
            </div>
            <table>
                <thead><tr><th data-i18n="station">Station</th><th data-i18n="totalOrdersCol">Total Orders</th><th data-i18n="revenueCol">Revenue</th></tr></thead>
                <tbody id="reportStationBody"></tbody>
            </table>
        </div>
    </section>
</main>
<?php require __DIR__ . '/../includes/public_footer.php'; ?>
<script src="../assets/footer_i18n.js?v=<?= file_exists(__DIR__ . '/../assets/footer_i18n.js') ? filemtime(__DIR__ . '/../assets/footer_i18n.js') : time() ?>"></script>
<script>
function showSection(id) {
    document.querySelectorAll('.section').forEach(el => el.classList.remove('active'));
    document.getElementById(id).classList.add('active');
}
document.querySelectorAll('.nav-btn').forEach(btn => btn.addEventListener('click', async () => {
    showSection(btn.dataset.target);
    if (btn.dataset.target === 'staffSection') {
        await loadStaffAccounts();
    }
}));

const state = { lang: localStorage.getItem('lang') || 'en' };
const I18N = mergeFooterI18n({
  en: {
    adminTitle: 'Admin Portal',
    navBrand: 'UM Rental', navHome: 'Home', navFeatured: 'Services', navAbout: 'Why us', navHowto: 'How to use', navContact: 'Contact Us', cta: 'Login / Use Service',
    navAccount: 'My Account', navLogout: 'Logout', navMyRental: 'My rental', navUserDashboard: 'User Dashboard', navStaffHome: 'Staff portal', navStaffHomeShort: 'Staff', navAdminPortal: 'Admin portal',
    adminPortal: 'UM Admin Portal', backUser: 'Back to User Dashboard', logout: 'Logout',
    adminNavigation: 'Admin Navigation', vehicleMgmt: 'Vehicle Management', stationStatus: 'Station Status',
    staffMgmt: 'Staff Accounts', staffMgmtTitle: 'Staff Account Management', addStaff: 'Add Staff',
    abnormalOrders: 'Abnormal Orders', paymentPending: 'Payment Pending', reports: 'Reports', vehicleDispatch: 'Vehicle Dispatch & Status Update',
    model: 'Model', brand: 'Brand', status: 'Status', station: 'Station', action: 'Action',
    stationUsageTitle: 'Station Capacity & Usage', capacity: 'Capacity', occupied: 'Occupied', usageRate: 'Usage',
    abnormalTitle: 'Abnormal Orders (>24h)', orderId: 'Order ID', user: 'User', vehicle: 'Vehicle',
    paymentPendingTitle: 'Payment Pending Orders', paymentReload: 'Reload', fee: 'Fee', walletBalance: 'Wallet Balance', noPaymentPending: 'No payment pending orders.',
    elapsedHours: 'Elapsed (Hours)', reportsTitle: 'Reports', updateStatus: 'Update Status', assignStation: 'Assign Station',
    stationId: 'Station ID', noAbnormal: 'No abnormal orders', manualClose: 'Manual Close',
    updateSuccess: 'Vehicle status updated', assignSuccess: 'Station assigned', inputStation: 'Please input station ID',
    inputReason: 'Please enter force-close reason', inputFee: 'Please enter adjusted fee (MOP)', forceDone: 'Manual close completed',
    staffAdded: 'Staff account added', staffStatusUpdated: 'Staff account status updated', staffRemoved: 'Staff account removed',
    fillStaffRequired: 'Please fill campus ID, full name, email, password', removeConfirm: 'Confirm remove this account?',
    remove: 'Remove', noStaffAccount: 'No staff account.',
    staffFormHint: 'Form fields below show hint text only until you type. The table lists staff accounts stored in the database.',
    staffPasswordRules: 'Required: Campus ID, full name, email, password. Password: min 8 characters with letters and numbers. The demo database seed ships with only one staff login (t2000001)—use this form to create additional staff accounts.',
    staffPasswordInvalid: 'Password must be at least 8 characters and include both letters and numbers.',
    staffCampusPlaceholder: 'Campus ID (required)',
    staffNamePlaceholder: 'Full name (required)',
    staffEmailPlaceholder: 'Email (required)',
    staffPhonePlaceholder: 'Phone (optional)',
    staffPasswordPlaceholder: 'Password (min 8 chars, letters + numbers)',
    campusIdCol: 'Campus ID',
    emailCol: 'Email',
    loadReportBtn: 'Load KPI Report',
    totalOrdersCol: 'Total Orders',
    revenueCol: 'Revenue',
    reportKpiHint: 'KPI counts orders whose start time falls in the selected range and groups revenue by start station. Fees may be 0 for active rentals. Demo orders use dates relative to “today”—if you pick a past year (e.g. 2024) while data lives in the current year, counts look empty. Use this month’s dates or re-import database/seed_demo_data.sql.'
  },
  'zh-CN': {
    adminTitle: '管理后台',
    navBrand: 'UM 租赁', navHome: '首页', navFeatured: '服务', navAbout: '核心价值', navHowto: '使用说明', navContact: '联系我们', cta: '立即登录 / 使用服务',
    navAccount: '我的账户', navLogout: '退出', navMyRental: '我的租借', navUserDashboard: '用户仪表板', navStaffHome: '员工入口', navStaffHomeShort: '员工', navAdminPortal: '管理入口',
    adminPortal: 'UM 管理后台', backUser: '返回用户仪表板', logout: '登出',
    adminNavigation: '管理导航', vehicleMgmt: '车辆管理', stationStatus: '站点状态',
    staffMgmt: '职员帐号', staffMgmtTitle: '职员帐号管理', addStaff: '新增职员',
    abnormalOrders: '异常订单', paymentPending: '待付款订单', reports: '统计报表', vehicleDispatch: '车辆调度与状态更新',
    model: '型号', brand: '品牌', status: '状态', station: '站点', action: '操作',
    stationUsageTitle: '站点容量与使用率', capacity: '容量', occupied: '已占用', usageRate: '使用率',
    abnormalTitle: '异常订单监控（>24h）', orderId: '订单ID', user: '使用者', vehicle: '车辆',
    paymentPendingTitle: '待付款订单', paymentReload: '刷新', fee: '费用', walletBalance: '钱包余额', noPaymentPending: '暂无待付款订单。',
    elapsedHours: '已持有(小时)', reportsTitle: '数据统计报表', updateStatus: '更新状态', assignStation: '指派站点',
    stationId: '站点ID', noAbnormal: '目前没有异常订单', manualClose: '手动结单',
    updateSuccess: '车辆状态更新成功', assignSuccess: '站点指派成功', inputStation: '请输入站点ID',
    inputReason: '请输入手动结单理由', inputFee: '请输入手动调整金额（MOP）', forceDone: '手动结单完成',
    staffAdded: '已新增职员帐号', staffStatusUpdated: '职员帐号状态已更新', staffRemoved: '职员帐号已删除',
    fillStaffRequired: '请填写 campus ID、姓名、邮箱、密码', removeConfirm: '确认删除此帐号？',
    remove: '删除', noStaffAccount: '目前没有职员帐号。',
    staffFormHint: '下方输入框在填写前仅显示提示文字；表格中的才是数据库内的职员帐号。',
    staffPasswordRules: '必填：校园 ID、姓名、邮箱、密码。密码至少 8 位且含字母与数字。演示种子数据默认只有一名职员（t2000001）—可在此新增或修改 database/seed_demo_data.sql。',
    staffPasswordInvalid: '密码需至少 8 位，且同时包含字母与数字。',
    staffCampusPlaceholder: '校园 ID（必填）',
    staffNamePlaceholder: '姓名（必填）',
    staffEmailPlaceholder: '邮箱（必填）',
    staffPhonePlaceholder: '电话（可选）',
    staffPasswordPlaceholder: '密码（至少 8 位，含字母与数字）',
    campusIdCol: 'Campus ID',
    emailCol: '邮箱',
    loadReportBtn: '加载 KPI 报表',
    totalOrdersCol: '总订单数',
    revenueCol: '收入',
    reportKpiHint: 'KPI 按「租借开始时间」落在所选区间内统计，并按出发站点归类；收入为订单 fee 合计（进行中订单可能为 0）。演示订单日期相对于「今天」——若区间选在往年而数据在今年，会很少或为 0。请选择本月日期，或重新导入 database/seed_demo_data.sql。'
  },
  'zh-TW': {
    adminTitle: '管理後台',
    navBrand: 'UM 租賃', navHome: '首頁', navFeatured: '服務', navAbout: '核心價值', navHowto: '使用說明', navContact: '聯繫我們', cta: '立即登入 / 使用服務',
    navAccount: '我的帳戶', navLogout: '登出', navMyRental: '我的租借', navUserDashboard: '用戶儀表板', navStaffHome: '員工入口', navStaffHomeShort: '員工', navAdminPortal: '管理入口',
    adminPortal: 'UM 管理後台', backUser: '返回用戶儀表板', logout: '登出',
    adminNavigation: '管理導航', vehicleMgmt: '車輛管理', stationStatus: '站點狀態',
    staffMgmt: '職員帳號', staffMgmtTitle: '職員帳號管理', addStaff: '新增職員',
    abnormalOrders: '異常訂單', paymentPending: '待付款訂單', reports: '統計報表', vehicleDispatch: '車輛調度與狀態更新',
    model: '型號', brand: '品牌', status: '狀態', station: '站點', action: '操作',
    stationUsageTitle: '站點容量與使用率', capacity: '容量', occupied: '已占用', usageRate: '使用率',
    abnormalTitle: '異常訂單監控（>24h）', orderId: '訂單ID', user: '使用者', vehicle: '車輛',
    paymentPendingTitle: '待付款訂單', paymentReload: '刷新', fee: '費用', walletBalance: '錢包餘額', noPaymentPending: '暫無待付款訂單。',
    elapsedHours: '已持有(小時)', reportsTitle: '數據統計報表', updateStatus: '更新狀態', assignStation: '指派站點',
    stationId: '站點ID', noAbnormal: '目前沒有異常訂單', manualClose: '手動結單',
    updateSuccess: '車輛狀態更新成功', assignSuccess: '站點指派成功', inputStation: '請輸入站點ID',
    inputReason: '請輸入手動結單理由', inputFee: '請輸入手動調整金額（MOP）', forceDone: '手動結單完成',
    staffAdded: '已新增職員帳號', staffStatusUpdated: '職員帳號狀態已更新', staffRemoved: '職員帳號已刪除',
    fillStaffRequired: '請填寫 campus ID、姓名、電郵、密碼', removeConfirm: '確認刪除此帳號？',
    remove: '刪除', noStaffAccount: '目前沒有職員帳號。',
    staffFormHint: '下方輸入框在填寫前僅顯示提示文字；表格中的才是資料庫內的職員帳號。',
    staffPasswordRules: '必填：校園 ID、姓名、電郵、密碼。密碼至少 8 位且含字母與數字。演示種子資料預設只有一名職員（t2000001）—可在此新增或修改 database/seed_demo_data.sql。',
    staffPasswordInvalid: '密碼需至少 8 位，且同時包含字母與數字。',
    staffCampusPlaceholder: '校園 ID（必填）',
    staffNamePlaceholder: '姓名（必填）',
    staffEmailPlaceholder: '電郵（必填）',
    staffPhonePlaceholder: '電話（可選）',
    staffPasswordPlaceholder: '密碼（至少 8 位，含字母與數字）',
    campusIdCol: 'Campus ID',
    emailCol: '電郵',
    loadReportBtn: '載入 KPI 報表',
    totalOrdersCol: '總訂單數',
    revenueCol: '收入',
    reportKpiHint: 'KPI 按「租借開始時間」落在所選區間內統計，並按出發站歸類；收入為訂單 fee 合計（進行中訂單可能為 0）。若區間選在往年而演示訂單在近期，會顯示很少或為 0——請選本月日期，或重新匯入 database/seed_demo_data.sql。'
  }
});
function t(key) { return (I18N[state.lang] && I18N[state.lang][key]) || I18N.en[key] || key; }
function applyLanguage() {
  document.documentElement.lang = state.lang;
  document.title = t('adminTitle');
  document.querySelectorAll('[data-i18n]').forEach(el => { el.textContent = t(el.dataset.i18n); });
  document.querySelectorAll('[data-i18n-placeholder]').forEach(el => { el.placeholder = t(el.dataset.i18nPlaceholder); });
}

async function api(action, method = 'GET', payload = null, query = null) {
    const url = new URL('../rental_action.php', window.location.href);
    url.searchParams.set('action', action);
    if (query) {
        Object.entries(query).forEach(([k, v]) => {
            if (v !== undefined && v !== null && String(v) !== '') {
                url.searchParams.set(k, String(v));
            }
        });
    }
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const opts = { method, headers: { 'Accept': 'application/json', 'X-CSRF-Token': csrfToken } };
    if (payload) {
        opts.headers['Content-Type'] = 'application/json';
        opts.body = JSON.stringify(payload);
    }
    const res = await fetch(url, opts);
    const data = await res.json();
    if (!data.success) throw new Error(data.message || '操作失敗');
    return data;
}

async function loadVehicles() {
    const data = await api('admin_vehicles');
    document.getElementById('vehicleTbody').innerHTML = data.data.map(v => `
        <tr>
          <td>${v.id}</td>
          <td>${v.vehicle_type}</td>
          <td>${v.brand}</td>
          <td>${v.status}</td>
          <td>${v.station_name ?? '-'} (#${v.station_id ?? '-'})</td>
          <td>
            <select id="status-${v.id}">
              <option value="available" ${v.status === 'available' ? 'selected' : ''}>available (可租借)</option>
              <option value="maintenance" ${v.status === 'maintenance' ? 'selected' : ''}>maintenance</option>
              <option value="retired" ${v.status === 'retired' ? 'selected' : ''}>retired</option>
            </select>
            <button onclick="updateStatus(${v.id})">${t('updateStatus')}</button>
            <input id="station-${v.id}" class="input-compact" placeholder="${t('stationId')}">
            <button class="secondary" onclick="assignStation(${v.id})">${t('assignStation')}</button>
          </td>
        </tr>
    `).join('');
}

async function updateStatus(vehicleID) {
    const newStatus = document.getElementById(`status-${vehicleID}`).value;
    await api('admin_update_vehicle', 'POST', { vehicleID, newStatus });
    alert(t('updateSuccess'));
    await loadVehicles();
    await loadOverview();
}

async function assignStation(vehicleID) {
    const stationID = Number(document.getElementById(`station-${vehicleID}`).value);
    if (!stationID) return alert(t('inputStation'));
    await api('admin_assign_station', 'POST', { vehicleID, stationID });
    alert(t('assignSuccess'));
    await loadVehicles();
}

async function loadAbnormal() {
    const data = await api('admin_abnormal_orders');
    document.getElementById('abnormalTbody').innerHTML = data.data.map(o => `
      <tr class="danger-row">
        <td>${o.id}</td>
        <td>${o.user_id}</td>
        <td>${o.vehicle_id}</td>
        <td>${o.elapsed_hours}</td>
        <td>
          <button class="danger" onclick="forceEnd(${o.id})">${t('manualClose')}</button>
        </td>
      </tr>
    `).join('') || `<tr><td colspan="5">${t('noAbnormal')}</td></tr>`;
}

async function loadPaymentPending() {
    const data = await api('admin_payment_pending_orders');
    document.getElementById('paymentPendingTbody').innerHTML = (data.data || []).map((o) => `
      <tr>
        <td>${o.id}</td>
        <td>${o.campus_id}<br><span class="meta">${o.full_name} / ${o.email}</span></td>
        <td>${o.vehicle_id} / ${o.serial_no || '-'}</td>
        <td>MOP ${Number(o.fee || 0).toFixed(2)}</td>
        <td>MOP ${Number(o.balance || 0).toFixed(2)}</td>
        <td>${o.status}</td>
      </tr>
    `).join('') || `<tr><td colspan="6">${t('noPaymentPending')}</td></tr>`;
}

async function loadStaffAccounts() {
    const data = await api('admin_staff_accounts');
    document.getElementById('staffTbody').innerHTML = (data.data || []).map((u) => `
      <tr>
        <td>${u.id}</td>
        <td>${u.campus_id}</td>
        <td>${u.full_name}</td>
        <td>${u.email}</td>
        <td>
          <select id="staff-status-${u.id}">
            <option value="active" ${u.account_status === 'active' ? 'selected' : ''}>active</option>
            <option value="frozen" ${u.account_status === 'frozen' ? 'selected' : ''}>frozen</option>
            <option value="disabled" ${u.account_status === 'disabled' ? 'selected' : ''}>disabled</option>
          </select>
        </td>
        <td>
          <button onclick="updateStaffStatus(${u.id})">${t('updateStatus')}</button>
          <button class="danger" onclick="removeStaff(${u.id})">${t('remove')}</button>
        </td>
      </tr>
    `).join('') || `<tr><td colspan="6">${t('noStaffAccount')}</td></tr>`;
}

function alertApiError(e) {
    const msg = e && e.message ? String(e.message) : String(e || 'Request failed');
    alert(msg);
}

async function addStaff() {
    const campusID = document.getElementById('staffCampusID').value.trim();
    const fullName = document.getElementById('staffFullName').value.trim();
    const email = document.getElementById('staffEmail').value.trim();
    const phone = document.getElementById('staffPhone').value.trim();
    const password = document.getElementById('staffPassword').value;
    if (!campusID || !fullName || !email || !password) {
        return alert(t('fillStaffRequired') + '\n\n' + t('staffPasswordRules'));
    }
    if (password.length < 8 || !/[A-Za-z]/.test(password) || !/\d/.test(password)) {
        return alert(t('staffPasswordInvalid'));
    }
    try {
        await api('admin_add_staff', 'POST', { campusID, fullName, email, phone, password });
        alert(t('staffAdded'));
        document.getElementById('staffCampusID').value = '';
        document.getElementById('staffFullName').value = '';
        document.getElementById('staffEmail').value = '';
        document.getElementById('staffPhone').value = '';
        document.getElementById('staffPassword').value = '';
        await loadStaffAccounts();
    } catch (e) {
        alertApiError(e);
    }
}

async function updateStaffStatus(userID) {
    const accountStatus = document.getElementById(`staff-status-${userID}`).value;
    try {
        await api('admin_update_staff_status', 'POST', { userID, accountStatus });
        alert(t('staffStatusUpdated'));
        await loadStaffAccounts();
    } catch (e) {
        alertApiError(e);
    }
}

async function removeStaff(userID) {
    if (!confirm(t('removeConfirm'))) return;
    try {
        await api('admin_remove_staff', 'POST', { userID });
        alert(t('staffRemoved'));
        await loadStaffAccounts();
    } catch (e) {
        alertApiError(e);
    }
}

async function forceEnd(orderID) {
    const reason = prompt(t('inputReason'));
    if (!reason) return;
    const adjustFee = Number(prompt(t('inputFee'), '0') || '0');
    await api('admin_force_end', 'POST', { orderID, reason, adjustFee });
    alert(t('forceDone'));
    await loadAbnormal();
}

async function loadOverview() {
    const data = await api('admin_overview');
    const stationRows = data.data.stationUsage;
    document.getElementById('stationTbody').innerHTML = stationRows.map(s => {
        const ratio = s.capacity > 0 ? Math.round((s.occupied / s.capacity) * 100) : 0;
        return `<tr><td>${s.name}</td><td>${s.capacity}</td><td>${s.occupied}</td><td>${ratio}%</td></tr>`;
    }).join('');

    document.getElementById('kpiWrap').innerHTML = data.data.vehicle.map(v =>
        `<div class="kpi"><div>${v.status}</div><h3>${v.total}</h3></div>`
    ).join('');
}

async function loadReport() {
    const from = document.getElementById('reportFrom').value;
    const to = document.getElementById('reportTo').value;
    const rangeData = await api('report_kpi', 'GET', null, { from: from || undefined, to: to || undefined });
    const summary = rangeData.data.summary || {};
    const statusCards = (rangeData.data.byStatus || []).map((s) => `<div class="kpi"><div>${s.status}</div><h3>${s.total}</h3></div>`).join('');
    document.getElementById('kpiWrap').innerHTML = `
      <div class="kpi"><div>Total Orders</div><h3>${summary.total_orders || 0}</h3></div>
      <div class="kpi"><div>Total Revenue</div><h3>MOP ${Number(summary.total_revenue || 0).toFixed(2)}</h3></div>
      <div class="kpi"><div>Avg Minutes</div><h3>${Math.round(Number(summary.avg_minutes || 0))}</h3></div>
      ${statusCards}
    `;
    document.getElementById('reportStationBody').innerHTML = (rangeData.data.byStation || []).map((r) => `
      <tr><td>${r.name}</td><td>${r.total_orders}</td><td>${Number(r.revenue).toFixed(2)}</td></tr>
    `).join('') || '<tr><td colspan="3">No report data.</td></tr>';
}

(async function init() {
    const languageSelect = document.getElementById('languageSelect');
    languageSelect.value = state.lang;
    languageSelect.addEventListener('change', async () => {
        state.lang = languageSelect.value;
        localStorage.setItem('lang', state.lang);
        applyLanguage();
        await loadVehicles();
        await loadStaffAccounts();
        await loadOverview();
        await loadAbnormal();
        await loadPaymentPending();
    });
    applyLanguage();
    const today = new Date().toISOString().slice(0, 10);
    const first = new Date();
    first.setDate(1);
    document.getElementById('reportFrom').value = first.toISOString().slice(0, 10);
    document.getElementById('reportTo').value = today;
    document.getElementById('loadReportBtn').addEventListener('click', loadReport);
    document.getElementById('addStaffBtn').addEventListener('click', addStaff);
    document.getElementById('reloadPaymentBtn').addEventListener('click', loadPaymentPending);

    await loadVehicles();
    await loadStaffAccounts();
    await loadOverview();
    await loadAbnormal();
    await loadPaymentPending();
    await loadReport();
})();
</script>
</body>
</html>
