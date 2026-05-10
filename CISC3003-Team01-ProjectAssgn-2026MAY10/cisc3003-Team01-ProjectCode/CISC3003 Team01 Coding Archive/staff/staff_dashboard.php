<?php
declare(strict_types=1);
require_once __DIR__ . '/_guard.php';
if (!isset($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token']) || $_SESSION['csrf_token'] === '') {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = (string)$_SESSION['csrf_token'];
$layoutBase = '../';
$activePage = 'staff';
$staffPortalIsAdmin = ((string)($_SESSION['role'] ?? '') === 'admin');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php require __DIR__ . '/../includes/favicon_links.php'; ?>
    <title>Staff Portal</title>
    <meta name="csrf-token" content="<?= htmlspecialchars($csrfToken) ?>">
    <meta name="staff-portal-admin" content="<?= $staffPortalIsAdmin ? '1' : '0' ?>">
    <link rel="stylesheet" href="../assets/um_landing.css?v=<?= file_exists(__DIR__ . '/../assets/um_landing.css') ? filemtime(__DIR__ . '/../assets/um_landing.css') : time() ?>">
    <link rel="stylesheet" href="../assets/home-landing.css?v=<?= file_exists(__DIR__ . '/../assets/home-landing.css') ? filemtime(__DIR__ . '/../assets/home-landing.css') : time() ?>">
    <link rel="stylesheet" href="../style.css?v=<?= file_exists(__DIR__ . '/../style.css') ? filemtime(__DIR__ . '/../style.css') : time() ?>">
    <link rel="stylesheet" href="../assets/site-footer.css?v=<?= file_exists(__DIR__ . '/../assets/site-footer.css') ? filemtime(__DIR__ . '/../assets/site-footer.css') : time() ?>">
</head>
<body>
<?php require __DIR__ . '/../includes/public_header.php'; ?>

<main class="layout">
    <aside class="sidebar">
        <h3 data-i18n="staffNavigation">Staff Navigation</h3>
        <ul class="nav-list">
            <li><button class="nav-btn" data-target="bikeSection" data-i18n="bikeManagement">Bicycle Management</button></li>
            <li><button class="nav-btn" data-target="studentSection" data-i18n="studentManagement">Student Account Management</button></li>
            <li><button class="nav-btn" data-target="feedbackSection" data-i18n="feedbackManagement">Feedback Management</button></li>
            <li><button class="nav-btn" data-target="forumSection" data-i18n="forumManagement">Forum Management</button></li>
        </ul>
    </aside>

    <section class="content-card">
        <div id="bikeSection" class="section active">
            <h2 data-i18n="bikeManagement">Bicycle Management</h2>
            <?php if ($staffPortalIsAdmin): ?>
            <div class="grid-2" id="adminFleetPanel">
                <div class="kpi">
                    <h3 data-i18n="addBicycle">Add Bicycle</h3>
                    <input id="serialNo" data-i18n-placeholder="serialNoPlaceholder" placeholder="Serial No (e.g. BK-0101)">
                    <select id="brandSelect"></select>
                    <select id="stationSelect"></select>
                    <input id="batteryLevel" type="number" min="0" max="100" data-i18n-placeholder="batteryPlaceholder" placeholder="Battery level (optional)">
                    <button id="addBikeBtn" data-i18n="addBicycle">Add Bicycle</button>
                    <div class="message" id="bikeFormMessage"></div>
                </div>
                <div class="kpi">
                    <h3 data-i18n="quickNotes">Quick Notes</h3>
                    <p class="meta" data-i18n="bikeNoteAdmin">Add/remove fleet: admin only. Use the table for issue notes (staff).</p>
                    <p class="meta" data-i18n="bikeNoteRented">Removing a bicycle is blocked if it is currently rented.</p>
                    <button class="secondary" id="exportBikesBtn" data-i18n="exportBikes">Export Bicycles CSV</button>
                    <textarea id="bikeCsvInput" rows="6" data-i18n-placeholder="bikeCsvPlaceholder" placeholder="Paste CSV: serial_no,vehicle_type,brand,station,battery_level,status"></textarea>
                    <button class="secondary" id="importBikesCsvBtn" data-i18n="importBikes">Import Vehicles CSV</button>
                </div>
            </div>
            <?php else: ?>
            <p class="meta staff-fleet-hint" data-i18n="bikeStaffHint">Add or remove bicycles is limited to admin. You can record minor on-site issues in the table below.</p>
            <p class="meta"><button class="secondary" id="exportBikesBtn" data-i18n="exportBikes">Export Bicycles CSV</button></p>
            <?php endif; ?>

            <table class="staff-bike-table">
                <thead>
                <tr>
                    <th>ID</th><th>Serial No</th><th>Brand</th><th>Status</th><th>Station</th><th>Current Renter</th><th>Rent Start</th><th>Last End Time</th>
                    <th data-i18n="colIssueNote">Minor issue / note</th>
                    <th data-i18n="action">Action</th>
                </tr>
                </thead>
                <tbody id="bikeTbody"></tbody>
            </table>
        </div>

        <div id="studentSection" class="section">
            <h2 data-i18n="studentManagement">Student Account Management</h2>
            <div class="grid-2">
                <div class="kpi">
                    <h3 data-i18n="addStudent">Add Student</h3>
                    <input id="studentCampusId" data-i18n-placeholder="studentCampusPlaceholder" placeholder="Campus ID (e.g. s2000010)">
                    <input id="studentFullName" data-i18n-placeholder="studentNamePlaceholder" placeholder="Full name">
                    <input id="studentEmail" type="email" data-i18n-placeholder="studentEmailPlaceholder" placeholder="Email">
                    <input id="studentPhone" data-i18n-placeholder="studentPhonePlaceholder" placeholder="Phone (optional)">
                    <input id="studentPassword" type="password" data-i18n-placeholder="studentPasswordPlaceholder" placeholder="Password (min 8 chars, letters + digits)">
                    <button id="addStudentBtn" data-i18n="addStudent">Add Student</button>
                    <div class="message" id="studentFormMessage"></div>
                </div>
                <div class="kpi">
                    <h3 data-i18n="quickNotes">Quick Notes</h3>
                    <p class="meta" data-i18n="studentRemoveHint1">Remove means permanent delete for students without related records.</p>
                    <p class="meta" data-i18n="studentRemoveHint2">If the student has orders/posts/feedback, remove will be blocked.</p>
                    <button class="secondary" id="exportStudentsBtn" data-i18n="exportStudents">Export Students CSV</button>
                    <textarea id="studentCsvInput" rows="6" data-i18n-placeholder="studentCsvPlaceholder" placeholder="Paste CSV: campus_id,full_name,email,phone,password"></textarea>
                    <button class="secondary" id="importStudentsCsvBtn" data-i18n="importStudents">Import Students CSV</button>
                    <select id="batchStudentStatus">
                        <option value="active">active</option>
                        <option value="frozen">frozen</option>
                        <option value="disabled">disabled</option>
                    </select>
                    <button class="secondary" id="batchStatusBtn" data-i18n="batchUpdate">Batch Update Selected</button>
                </div>
            </div>
            <table>
                <thead>
                <tr>
                    <th><input type="checkbox" id="selectAllStudents"></th><th>ID</th><th>Campus ID</th><th>Name</th><th>Email</th><th>Status</th><th>Action</th>
                </tr>
                </thead>
                <tbody id="studentTbody"></tbody>
            </table>
        </div>

        <div id="feedbackSection" class="section">
            <h2 data-i18n="feedbackManagement">Feedback Management</h2>
            <div class="grid-2">
                <div class="kpi">
                    <h3 data-i18n="feedbackReplyBox">Reply / Update Status</h3>
                    <label for="feedbackFilterStatus" data-i18n="feedbackFilterStatus">Filter status</label>
                    <select id="feedbackFilterStatus">
                        <option value="" data-i18n="feedbackFilterAll">All</option>
                        <option value="open" data-i18n="feedbackStatusOpen">Open</option>
                        <option value="in_progress" data-i18n="feedbackStatusInProgress">In progress</option>
                        <option value="resolved" data-i18n="feedbackStatusResolved">Resolved</option>
                        <option value="closed" data-i18n="feedbackStatusClosed">Closed</option>
                    </select>
                    <button id="reloadFeedbackBtn" class="secondary" data-i18n="feedbackReload">Reload</button>
                    <div class="message" id="feedbackAdminMsg"></div>
                </div>
            </div>
            <table>
                <thead>
                <tr>
                    <th>ID</th>
                    <th data-i18n="feedbackTicket">Ticket</th>
                    <th data-i18n="feedbackReporter">Reporter</th>
                    <th data-i18n="feedbackStatusLabel">Status</th>
                    <th data-i18n="feedbackReplies">Replies</th>
                    <th data-i18n="feedbackAction">Action</th>
                </tr>
                </thead>
                <tbody id="feedbackAdminTbody"></tbody>
            </table>
        </div>
        <div id="forumSection" class="section">
            <h2 data-i18n="forumManagement">Forum Management</h2>
            <div class="grid-2">
                <div class="kpi">
                    <h3 data-i18n="forumModerationBox">Moderation</h3>
                    <label for="forumFilterStatus" data-i18n="forumFilterStatus">Filter status</label>
                    <select id="forumFilterStatus">
                        <option value="" data-i18n="forumFilterAll">All</option>
                        <option value="visible" data-i18n="forumStatusVisible">Visible</option>
                        <option value="locked" data-i18n="forumStatusLocked">Locked</option>
                        <option value="hidden" data-i18n="forumStatusHidden">Hidden</option>
                        <option value="deleted" data-i18n="forumStatusDeleted">Deleted</option>
                    </select>
                    <button id="reloadForumBtn" class="secondary" data-i18n="forumReload">Reload</button>
                    <div class="message" id="forumAdminMsg"></div>
                </div>
            </div>
            <table>
                <thead>
                <tr>
                    <th>ID</th>
                    <th data-i18n="forumTicket">Post</th>
                    <th data-i18n="forumReporter">Author</th>
                    <th data-i18n="forumStatusLabel">Status</th>
                    <th data-i18n="forumAction">Action</th>
                </tr>
                </thead>
                <tbody id="forumAdminTbody"></tbody>
            </table>
        </div>
    </section>
</main>
<?php require __DIR__ . '/../includes/public_footer.php'; ?>
<script src="../assets/footer_i18n.js?v=<?= file_exists(__DIR__ . '/../assets/footer_i18n.js') ? filemtime(__DIR__ . '/../assets/footer_i18n.js') : time() ?>"></script>
<script>
const i18n = mergeFooterI18n({
    en: {
        pageTitle: 'Staff Portal',
        navBrand: 'UM Rental', navHome: 'Home', navFeatured: 'Services', navAbout: 'Why us', navHowto: 'How to use', navContact: 'Contact Us', cta: 'Login / Use Service',
        navAccount: 'My Account', navLogout: 'Logout', navMyRental: 'My rental', navUserDashboard: 'User Dashboard', navStaffHome: 'Staff portal', navStaffHomeShort: 'Staff', navAdminPortal: 'Admin portal',
        title: 'UM Staff Portal', backDashboard: 'Back to User Dashboard', logout: 'Logout',
        staffNavigation: 'Staff Navigation', bikeManagement: 'Bicycle Management', studentManagement: 'Student Account Management',
        addBicycle: 'Add Bicycle', quickNotes: 'Quick Notes', exportBikes: 'Export Bicycles CSV', importBikes: 'Import Vehicles CSV',
        bikeCsvPlaceholder: 'Paste CSV: serial_no,vehicle_type,brand,station,battery_level,status',
        serialNoPlaceholder: 'Serial No (e.g. BK-0101)',
        batteryPlaceholder: 'Battery level (optional)',
        addStudent: 'Add Student', exportStudents: 'Export Students CSV', importStudents: 'Import Students CSV', batchUpdate: 'Batch Update Selected',
        studentCampusPlaceholder: 'Campus ID (e.g. s2000010)',
        studentNamePlaceholder: 'Full name',
        studentEmailPlaceholder: 'Email',
        studentPhonePlaceholder: 'Phone (optional)',
        studentPasswordPlaceholder: 'Password (min 8 chars, letters + digits)',
        studentCsvPlaceholder: 'Paste CSV: campus_id,full_name,email,phone,password',
        studentRemoveHint1: 'Remove means permanent delete for students without related records.',
        studentRemoveHint2: 'If the student has orders/posts/feedback, remove will be blocked.',
        bikeStaffHint: 'Add or remove bicycles is limited to admin. You can record minor on-site issues in the table below.',
        bikeNoteAdmin: 'Fleet add/remove: admin only. All roles can add issue notes below.',
        bikeNoteRented: 'Removing a bicycle is blocked if it is currently rented.',
        colIssueNote: 'Minor issue / note',
        issuePlaceholder: 'e.g. loose brake, scratch — for ops follow-up',
        saveIssueBtn: 'Save note',
        removeBikeBtn: 'Remove',
        action: 'Action',
        feedbackManagement: 'Feedback Management',
        feedbackReplyBox: 'Reply / Update Status',
        feedbackFilterStatus: 'Filter status',
        feedbackFilterAll: 'All',
        feedbackStatusOpen: 'Open',
        feedbackStatusInProgress: 'In progress',
        feedbackStatusResolved: 'Resolved',
        feedbackStatusClosed: 'Closed',
        feedbackReload: 'Reload',
        feedbackTicket: 'Ticket',
        feedbackReporter: 'Reporter',
        feedbackStatusLabel: 'Status',
        feedbackReplies: 'Replies',
        feedbackAction: 'Action',
        feedbackReplyPlaceholder: 'Write a reply...',
        feedbackSaveBtn: 'Submit',
        feedbackNoReply: 'No reply yet',
        forumManagement: 'Forum Management',
        forumModerationBox: 'Moderation',
        forumFilterStatus: 'Filter status',
        forumFilterAll: 'All',
        forumStatusVisible: 'Visible',
        forumStatusLocked: 'Locked',
        forumStatusHidden: 'Hidden',
        forumStatusDeleted: 'Deleted',
        forumReload: 'Reload',
        forumTicket: 'Post',
        forumReporter: 'Author',
        forumStatusLabel: 'Status',
        forumAction: 'Action',
        forumSaveBtn: 'Update status',
        forumNoRows: 'No forum posts found',
        feedbackReplyRequired: 'Please provide reply content or status.',
        updatedText: 'Updated',
        removeBikeConfirm: 'Remove this bicycle?',
        removeStudentConfirm: 'Remove this student account?',
        studentAddedSuccess: 'Student added successfully.',
        bikeAddedSuccess: 'Bicycle added successfully.',
        selectAtLeastOneStudent: 'Select at least one student.',
        importedSummary: 'Imported: {created}, Errors: {errors}',
    },
    'zh-CN': {
        pageTitle: '员工管理门户',
        navBrand: 'UM 租赁', navHome: '首页', navFeatured: '服务', navAbout: '核心价值', navHowto: '使用说明', navContact: '联系我们', cta: '立即登录 / 使用服务',
        navAccount: '我的账户', navLogout: '退出', navMyRental: '我的租借', navUserDashboard: '用户仪表板', navStaffHome: '员工入口', navStaffHomeShort: '员工', navAdminPortal: '管理入口',
        title: 'UM 员工管理门户', backDashboard: '返回用户仪表板', logout: '登出',
        staffNavigation: '员工导航', bikeManagement: '自行车管理', studentManagement: '学生账户管理',
        addBicycle: '新增自行车', quickNotes: '快速说明', exportBikes: '导出自行车 CSV', importBikes: '导入车辆 CSV',
        bikeCsvPlaceholder: '粘贴 CSV：serial_no,vehicle_type,brand,station,battery_level,status',
        serialNoPlaceholder: '车身编号（例如 BK-0101）',
        batteryPlaceholder: '电量（可选）',
        addStudent: '新增学生', exportStudents: '导出学生 CSV', importStudents: '导入学生 CSV', batchUpdate: '批量更新已选',
        studentCampusPlaceholder: 'Campus ID（例如 s2000010）',
        studentNamePlaceholder: '姓名',
        studentEmailPlaceholder: '邮箱',
        studentPhonePlaceholder: '电话（可选）',
        studentPasswordPlaceholder: '密码（至少8位，含字母与数字）',
        studentCsvPlaceholder: '粘贴 CSV：campus_id,full_name,email,phone,password',
        studentRemoveHint1: '删除代表永久删除无关联记录的学生账号。',
        studentRemoveHint2: '若该学生有关联订单/帖子/反馈，则会阻止删除。',
        bikeStaffHint: '新增/删除车辆仅限管理员。可在下表填写车辆现场小问题备注。',
        bikeNoteAdmin: '车辆增减仅限管理员；备注栏职员均可填写。',
        bikeNoteRented: '车辆租借中时不可删除。',
        colIssueNote: '小问题 / 备注',
        issuePlaceholder: '如：刹车偏松、外壳刮痕 — 供运维跟进',
        saveIssueBtn: '保存备注',
        removeBikeBtn: '删除',
        action: '操作',
        feedbackManagement: '反馈工单管理',
        feedbackReplyBox: '回复 / 更新状态',
        feedbackFilterStatus: '筛选状态',
        feedbackFilterAll: '全部',
        feedbackStatusOpen: '新建',
        feedbackStatusInProgress: '处理中',
        feedbackStatusResolved: '已解决',
        feedbackStatusClosed: '已关闭',
        feedbackReload: '刷新',
        feedbackTicket: '工单',
        feedbackReporter: '提交人',
        feedbackStatusLabel: '状态',
        feedbackReplies: '回复',
        feedbackAction: '操作',
        feedbackReplyPlaceholder: '输入回复内容...',
        feedbackSaveBtn: '提交',
        feedbackNoReply: '暂无回复',
        forumManagement: '论坛帖子管理',
        forumModerationBox: '内容审核',
        forumFilterStatus: '筛选状态',
        forumFilterAll: '全部',
        forumStatusVisible: '可见',
        forumStatusLocked: '锁定',
        forumStatusHidden: '隐藏',
        forumStatusDeleted: '已删除',
        forumReload: '刷新',
        forumTicket: '帖子',
        forumReporter: '作者',
        forumStatusLabel: '状态',
        forumAction: '操作',
        forumSaveBtn: '更新状态',
        forumNoRows: '暂无论坛帖子',
        feedbackReplyRequired: '请填写回复内容或状态。',
        updatedText: '已更新',
        removeBikeConfirm: '确定删除这辆自行车吗？',
        removeStudentConfirm: '确定删除该学生账号吗？',
        studentAddedSuccess: '学生账号新增成功。',
        bikeAddedSuccess: '自行车新增成功。',
        selectAtLeastOneStudent: '请至少选择一名学生。',
        importedSummary: '已导入：{created}，错误：{errors}',
    },
    'zh-TW': {
        pageTitle: '職員管理入口',
        navBrand: 'UM 租賃', navHome: '首頁', navFeatured: '服務', navAbout: '核心價值', navHowto: '使用說明', navContact: '聯繫我們', cta: '立即登入 / 使用服務',
        navAccount: '我的帳戶', navLogout: '登出', navMyRental: '我的租借', navUserDashboard: '用戶儀表板', navStaffHome: '員工入口', navStaffHomeShort: '員工', navAdminPortal: '管理入口',
        title: 'UM 職員管理入口', backDashboard: '返回用戶儀表板', logout: '登出',
        staffNavigation: '職員導覽', bikeManagement: '自行車管理', studentManagement: '學生帳戶管理',
        addBicycle: '新增自行車', quickNotes: '快速說明', exportBikes: '匯出自行車 CSV', importBikes: '匯入車輛 CSV',
        bikeCsvPlaceholder: '貼上 CSV：serial_no,vehicle_type,brand,station,battery_level,status',
        serialNoPlaceholder: '車身編號（例如 BK-0101）',
        batteryPlaceholder: '電量（可選）',
        addStudent: '新增學生', exportStudents: '匯出學生 CSV', importStudents: '匯入學生 CSV', batchUpdate: '批次更新已選',
        studentCampusPlaceholder: 'Campus ID（例如 s2000010）',
        studentNamePlaceholder: '姓名',
        studentEmailPlaceholder: '電郵',
        studentPhonePlaceholder: '電話（可選）',
        studentPasswordPlaceholder: '密碼（至少8位，含字母與數字）',
        studentCsvPlaceholder: '貼上 CSV：campus_id,full_name,email,phone,password',
        studentRemoveHint1: '刪除代表永久刪除無關聯紀錄的學生帳號。',
        studentRemoveHint2: '若該學生有關聯訂單/帖子/回饋，將無法刪除。',
        bikeStaffHint: '新增／刪除車輛僅限管理員。可在下表填寫車輛現場小問題備註。',
        bikeNoteAdmin: '車輛增刪僅限管理員；備註欄職員均可填寫。',
        bikeNoteRented: '車輛租借中時不可刪除。',
        colIssueNote: '小問題 / 備註',
        issuePlaceholder: '如：煞車偏鬆、外殼刮痕 — 供運維跟進',
        saveIssueBtn: '儲存備註',
        removeBikeBtn: '刪除',
        action: '操作',
        feedbackManagement: '回饋工單管理',
        feedbackReplyBox: '回覆 / 更新狀態',
        feedbackFilterStatus: '篩選狀態',
        feedbackFilterAll: '全部',
        feedbackStatusOpen: '新建',
        feedbackStatusInProgress: '處理中',
        feedbackStatusResolved: '已解決',
        feedbackStatusClosed: '已關閉',
        feedbackReload: '刷新',
        feedbackTicket: '工單',
        feedbackReporter: '提交人',
        feedbackStatusLabel: '狀態',
        feedbackReplies: '回覆',
        feedbackAction: '操作',
        feedbackReplyPlaceholder: '輸入回覆內容...',
        feedbackSaveBtn: '提交',
        feedbackNoReply: '暫無回覆',
        forumManagement: '論壇帖子管理',
        forumModerationBox: '內容審核',
        forumFilterStatus: '篩選狀態',
        forumFilterAll: '全部',
        forumStatusVisible: '可見',
        forumStatusLocked: '鎖定',
        forumStatusHidden: '隱藏',
        forumStatusDeleted: '已刪除',
        forumReload: '刷新',
        forumTicket: '帖子',
        forumReporter: '作者',
        forumStatusLabel: '狀態',
        forumAction: '操作',
        forumSaveBtn: '更新狀態',
        forumNoRows: '暫無論壇帖子',
        feedbackReplyRequired: '請填寫回覆內容或狀態。',
        updatedText: '已更新',
        removeBikeConfirm: '確定刪除此自行車嗎？',
        removeStudentConfirm: '確定刪除此學生帳號嗎？',
        studentAddedSuccess: '學生帳號新增成功。',
        bikeAddedSuccess: '自行車新增成功。',
        selectAtLeastOneStudent: '請至少選擇一名學生。',
        importedSummary: '已匯入：{created}，錯誤：{errors}',
    }
});
let lang = localStorage.getItem('lang') || 'en';
function t(k){ return (i18n[lang] && i18n[lang][k]) || i18n.en[k] || k; }
function tf(k, vars = {}) {
    return Object.keys(vars).reduce((acc, key) => acc.replace(new RegExp(`\\{${key}\\}`, 'g'), String(vars[key])), t(k));
}
function applyLanguage(){
    document.documentElement.lang = lang;
    document.title = t('pageTitle');
    document.querySelectorAll('[data-i18n]').forEach(el => el.textContent = t(el.dataset.i18n));
    document.querySelectorAll('[data-i18n-placeholder]').forEach(el => el.placeholder = t(el.dataset.i18nPlaceholder));
}
function showSection(id) {
    document.querySelectorAll('.section').forEach(el => el.classList.remove('active'));
    const target = document.getElementById(id);
    if (target) target.classList.add('active');
}
document.querySelectorAll('.nav-btn').forEach(btn => btn.addEventListener('click', () => showSection(btn.dataset.target)));

function getInitialSection() {
    const hash = (window.location.hash || '').replace('#', '');
    if (hash === 'studentSection' || hash === 'bikeSection' || hash === 'feedbackSection' || hash === 'forumSection') return hash;
    const section = new URLSearchParams(window.location.search).get('section');
    if (section === 'student') return 'studentSection';
    if (section === 'bike') return 'bikeSection';
    if (section === 'feedback') return 'feedbackSection';
    if (section === 'forum') return 'forumSection';
    return 'bikeSection';
}

async function api(action, method = 'GET', payload = null) {
    const url = new URL('../rental_action.php', window.location.href);
    url.searchParams.set('action', action);
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const opts = { method, headers: { 'Accept': 'application/json', 'X-CSRF-Token': csrfToken } };
    if (payload) {
        opts.headers['Content-Type'] = 'application/json';
        opts.body = JSON.stringify(payload);
    }
    const res = await fetch(url, opts);
    const data = await res.json();
    if (!data.success) throw new Error(data.message || 'Request failed');
    return data;
}

async function apiGet(action, params = {}) {
    const url = new URL('../rental_action.php', window.location.href);
    url.searchParams.set('action', action);
    Object.entries(params).forEach(([k, v]) => {
        if (v !== null && v !== undefined && String(v) !== '') {
            url.searchParams.set(k, String(v));
        }
    });
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const res = await fetch(url.toString(), { method: 'GET', headers: { 'Accept': 'application/json', 'X-CSRF-Token': csrfToken } });
    const data = await res.json();
    if (!data.success) throw new Error(data.message || 'Request failed');
    return data;
}

function populateBikeForm(brands, stations) {
    const brandSelect = document.getElementById('brandSelect');
    const stationSelect = document.getElementById('stationSelect');
    if (!brandSelect || !stationSelect) return;
    brandSelect.innerHTML = '<option value="">Select brand</option>' + brands.map(b => `<option value="${b.id}">${b.name}</option>`).join('');
    stationSelect.innerHTML = '<option value="">Select station</option>' + stations.map(s => `<option value="${s.id}">${s.name}</option>`).join('');
}

function escapeHtml(s) {
    return String(s ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function feedbackStatusText(status) {
    const keyByStatus = {
        open: 'feedbackStatusOpen',
        in_progress: 'feedbackStatusInProgress',
        resolved: 'feedbackStatusResolved',
        closed: 'feedbackStatusClosed',
    };
    return t(keyByStatus[status] || 'feedbackStatusOpen');
}

function forumStatusText(status) {
    const keyByStatus = {
        visible: 'forumStatusVisible',
        locked: 'forumStatusLocked',
        hidden: 'forumStatusHidden',
        deleted: 'forumStatusDeleted',
    };
    return t(keyByStatus[status] || 'forumStatusVisible');
}

async function loadForumAdmin() {
    const filterStatus = document.getElementById('forumFilterStatus')?.value || '';
    const data = await apiGet('staff_forum_list', { status: filterStatus });
    const tbody = document.getElementById('forumAdminTbody');
    if (!tbody) return;
    const rows = data.data || [];
    if (!rows.length) {
        tbody.innerHTML = `<tr><td colspan="5">${escapeHtml(t('forumNoRows'))}</td></tr>`;
        return;
    }
    tbody.innerHTML = rows.map((f) => {
        const userLabel = `${escapeHtml(f.full_name || '-') }<br><span class="meta">${escapeHtml(f.campus_id || '-') } / ${escapeHtml(f.email || '-')}</span>`;
        return `
            <tr>
                <td>${f.id}</td>
                <td><strong>[${escapeHtml(f.category || 'other')}]</strong> ${escapeHtml(f.title || '-')}<br><span class="meta">${escapeHtml(f.content || '')}</span></td>
                <td>${userLabel}</td>
                <td>${escapeHtml(forumStatusText(String(f.status || 'visible')))}</td>
                <td>
                    <select id="forum-status-${f.id}">
                        <option value="visible">${escapeHtml(t('forumStatusVisible'))}</option>
                        <option value="locked">${escapeHtml(t('forumStatusLocked'))}</option>
                        <option value="hidden">${escapeHtml(t('forumStatusHidden'))}</option>
                        <option value="deleted">${escapeHtml(t('forumStatusDeleted'))}</option>
                    </select>
                    <button type="button" class="secondary" onclick="updateForumStatus(${f.id})">${escapeHtml(t('forumSaveBtn'))}</button>
                </td>
            </tr>
        `;
    }).join('');
    rows.forEach((f) => {
        const sel = document.getElementById(`forum-status-${f.id}`);
        if (sel) sel.value = String(f.status || 'visible');
    });
}

async function updateForumStatus(postID) {
    const status = (document.getElementById(`forum-status-${postID}`)?.value || '').trim();
    const msg = document.getElementById('forumAdminMsg');
    try {
        const data = await api('staff_forum_update_status', 'POST', { postID, status });
        if (msg) msg.textContent = data.message || 'Updated';
        await loadForumAdmin();
    } catch (e) {
        if (msg) msg.textContent = e.message;
    }
}
window.updateForumStatus = updateForumStatus;

async function loadFeedbacksAdmin() {
    const filterStatus = document.getElementById('feedbackFilterStatus')?.value || '';
    const data = await apiGet('staff_feedback_list', { status: filterStatus });
    const tbody = document.getElementById('feedbackAdminTbody');
    if (!tbody) return;
    const rows = data.data || [];
    if (!rows.length) {
        tbody.innerHTML = `<tr><td colspan="6">${escapeHtml(t('feedbackNoReply'))}</td></tr>`;
        return;
    }
    tbody.innerHTML = rows.map((f) => {
        const replies = Array.isArray(f.replies) ? f.replies : [];
        const replyHtml = replies.length
            ? replies.map((r) => `<div><strong>${escapeHtml(r.admin_name || 'Admin')}:</strong> ${escapeHtml(r.reply_content)}<br><span class="meta">${escapeHtml(r.created_at || '')}</span></div>`).join('<hr>')
            : `<span class="meta">${escapeHtml(t('feedbackNoReply'))}</span>`;
        const userLabel = `${escapeHtml(f.full_name || '-') }<br><span class="meta">${escapeHtml(f.campus_id || '-') } / ${escapeHtml(f.email || '-')}</span>`;
        return `
            <tr>
                <td>${f.id}</td>
                <td><strong>[${escapeHtml(f.category || 'other')}]</strong> ${escapeHtml(f.title || '-')}<br><span class="meta">${escapeHtml(f.description || '')}</span></td>
                <td>${userLabel}</td>
                <td>${escapeHtml(feedbackStatusText(String(f.status || 'open')))}</td>
                <td>${replyHtml}</td>
                <td>
                    <textarea id="feedback-reply-${f.id}" rows="3" class="issue-field" placeholder="${escapeHtml(t('feedbackReplyPlaceholder'))}"></textarea>
                    <select id="feedback-status-${f.id}">
                        <option value="">(${escapeHtml(t('feedbackStatusLabel'))})</option>
                        <option value="open">${escapeHtml(t('feedbackStatusOpen'))}</option>
                        <option value="in_progress">${escapeHtml(t('feedbackStatusInProgress'))}</option>
                        <option value="resolved">${escapeHtml(t('feedbackStatusResolved'))}</option>
                        <option value="closed">${escapeHtml(t('feedbackStatusClosed'))}</option>
                    </select>
                    <button type="button" class="secondary" onclick="submitFeedbackReply(${f.id})">${escapeHtml(t('feedbackSaveBtn'))}</button>
                </td>
            </tr>
        `;
    }).join('');
}

async function submitFeedbackReply(feedbackID) {
    const replyContent = (document.getElementById(`feedback-reply-${feedbackID}`)?.value || '').trim();
    const status = (document.getElementById(`feedback-status-${feedbackID}`)?.value || '').trim();
    const msg = document.getElementById('feedbackAdminMsg');
    if (!replyContent && !status) {
        if (msg) msg.textContent = t('feedbackReplyRequired');
        return;
    }
    try {
        const data = await api('staff_feedback_reply', 'POST', { feedbackID, replyContent, status });
        if (msg) msg.textContent = data.message || t('updatedText');
        await loadFeedbacksAdmin();
    } catch (e) {
        if (msg) msg.textContent = e.message;
    }
}
window.submitFeedbackReply = submitFeedbackReply;

async function loadBicycles() {
    const data = await api('staff_bicycles');
    populateBikeForm(data.data.brands, data.data.stations);
    const isAdmin = document.querySelector('meta[name="staff-portal-admin"]')?.content === '1';
    document.getElementById('bikeTbody').innerHTML = data.data.bicycles.map((b) => {
        const noteRaw = b.issue_note != null ? String(b.issue_note) : '';
        const noteEsc = escapeHtml(noteRaw);
        const removeBtn = isAdmin
            ? `<button type="button" class="danger" onclick="removeBicycle(${b.id})">${escapeHtml(t('removeBikeBtn'))}</button>`
            : '';
        return `
      <tr>
        <td>${b.id}</td>
        <td>${b.serial_no}</td>
        <td>${b.brand}</td>
        <td>${b.status}</td>
        <td>${b.station_name ?? '-'}</td>
        <td>${b.renter_campus_id ? `${b.renter_campus_id} / ${b.renter_name ?? '-'} / ${b.renter_phone ?? '-'}` : '-'}</td>
        <td>${b.rent_start_time ?? '-'}</td>
        <td>${b.last_end_time ? `${b.last_end_time} (${b.last_renter_name ?? '-'})` : '-'}</td>
        <td><textarea class="issue-field" id="issue-${b.id}" rows="2" placeholder="${escapeHtml(t('issuePlaceholder'))}">${noteEsc}</textarea></td>
        <td>
          <button type="button" class="secondary" onclick="saveIssueNote(${b.id})">${escapeHtml(t('saveIssueBtn'))}</button>
          ${removeBtn}
        </td>
      </tr>`;
    }).join('');
}

async function saveIssueNote(vehicleID) {
    const ta = document.getElementById(`issue-${vehicleID}`);
    await api('staff_update_vehicle_issue', 'POST', { vehicleID, issueNote: ta ? ta.value : '' });
    await loadBicycles();
}
window.saveIssueNote = saveIssueNote;

async function loadStudents() {
    const data = await api('staff_students');
    document.getElementById('studentTbody').innerHTML = data.data.map(u => `
      <tr>
        <td><input type="checkbox" class="student-select" value="${u.id}"></td>
        <td>${u.id}</td>
        <td>${u.campus_id}</td>
        <td>${u.full_name}</td>
        <td>${u.email}</td>
        <td>
          <select id="student-status-${u.id}">
            <option value="active" ${u.account_status === 'active' ? 'selected' : ''}>active</option>
            <option value="frozen" ${u.account_status === 'frozen' ? 'selected' : ''}>frozen</option>
            <option value="disabled" ${u.account_status === 'disabled' ? 'selected' : ''}>disabled</option>
          </select>
        </td>
        <td>
          <button onclick="updateStudentStatus(${u.id})">Update</button>
          <button class="danger" onclick="removeStudent(${u.id})">Remove</button>
        </td>
      </tr>
    `).join('');
}

async function removeBicycle(vehicleID) {
    if (!confirm(t('removeBikeConfirm'))) return;
    await api('staff_remove_bicycle', 'POST', { vehicleID });
    await loadBicycles();
}

async function updateStudentStatus(userID) {
    const accountStatus = document.getElementById(`student-status-${userID}`).value;
    await api('staff_update_student_status', 'POST', { userID, accountStatus });
    await loadStudents();
}

async function removeStudent(userID) {
    if (!confirm(t('removeStudentConfirm'))) return;
    await api('staff_remove_student', 'POST', { userID });
    await loadStudents();
}

document.getElementById('addStudentBtn').addEventListener('click', async () => {
    const campusID = document.getElementById('studentCampusId').value.trim();
    const fullName = document.getElementById('studentFullName').value.trim();
    const email = document.getElementById('studentEmail').value.trim();
    const phone = document.getElementById('studentPhone').value.trim();
    const password = document.getElementById('studentPassword').value;
    const msg = document.getElementById('studentFormMessage');

    try {
        await api('staff_add_student', 'POST', { campusID, fullName, email, phone, password });
        msg.textContent = t('studentAddedSuccess');
        document.getElementById('studentCampusId').value = '';
        document.getElementById('studentFullName').value = '';
        document.getElementById('studentEmail').value = '';
        document.getElementById('studentPhone').value = '';
        document.getElementById('studentPassword').value = '';
        await loadStudents();
    } catch (e) {
        msg.textContent = e.message;
    }
});

const addBikeBtn = document.getElementById('addBikeBtn');
if (addBikeBtn) {
    addBikeBtn.addEventListener('click', async () => {
        const serialNo = document.getElementById('serialNo').value.trim();
        const brandID = Number(document.getElementById('brandSelect').value);
        const stationID = Number(document.getElementById('stationSelect').value);
        const batteryLevelRaw = document.getElementById('batteryLevel').value;
        const batteryLevel = batteryLevelRaw === '' ? null : Number(batteryLevelRaw);
        const msg = document.getElementById('bikeFormMessage');

        try {
            await api('staff_add_bicycle', 'POST', { serialNo, brandID, stationID, batteryLevel });
            msg.textContent = t('bikeAddedSuccess');
            document.getElementById('serialNo').value = '';
            document.getElementById('batteryLevel').value = '';
            await loadBicycles();
        } catch (e) {
            msg.textContent = e.message;
        }
    });
}

(async function init() {
    showSection(getInitialSection());
    await loadBicycles();
    await loadStudents();
    await loadFeedbacksAdmin();
    await loadForumAdmin();
})();
const langSelect = document.getElementById('languageSelect');
langSelect.value = lang;
langSelect.addEventListener('change', () => {
    lang = langSelect.value;
    localStorage.setItem('lang', lang);
    applyLanguage();
    loadFeedbacksAdmin().catch(() => {});
    loadForumAdmin().catch(() => {});
});
applyLanguage();

document.getElementById('selectAllStudents').addEventListener('change', (e) => {
    document.querySelectorAll('.student-select').forEach((el) => {
        el.checked = e.target.checked;
    });
});
document.getElementById('batchStatusBtn').addEventListener('click', async () => {
    const userIDs = Array.from(document.querySelectorAll('.student-select:checked')).map((el) => Number(el.value));
    if (userIDs.length === 0) {
        alert(t('selectAtLeastOneStudent'));
        return;
    }
    const accountStatus = document.getElementById('batchStudentStatus').value;
    await api('staff_batch_student_status', 'POST', { userIDs, accountStatus });
    await loadStudents();
});
document.getElementById('importStudentsCsvBtn').addEventListener('click', async () => {
    const csvText = document.getElementById('studentCsvInput').value;
    const data = await api('staff_import_students_csv', 'POST', { csvText });
    alert(tf('importedSummary', { created: data.data.created, errors: data.data.errors.length }));
    await loadStudents();
});
document.getElementById('exportStudentsBtn').addEventListener('click', () => {
    window.location.href = '../rental_action.php?action=staff_export_students';
});
document.getElementById('exportBikesBtn').addEventListener('click', () => {
    window.location.href = '../rental_action.php?action=staff_export_bicycles';
});
const importBikesCsvBtn = document.getElementById('importBikesCsvBtn');
if (importBikesCsvBtn) {
    importBikesCsvBtn.addEventListener('click', async () => {
        const csvText = document.getElementById('bikeCsvInput').value;
        const data = await api('staff_import_bicycles_csv', 'POST', { csvText });
        alert(tf('importedSummary', { created: data.data.created, errors: data.data.errors.length }));
        await loadBicycles();
    });
}
const reloadFeedbackBtn = document.getElementById('reloadFeedbackBtn');
if (reloadFeedbackBtn) {
    reloadFeedbackBtn.addEventListener('click', async () => {
        await loadFeedbacksAdmin();
    });
}
const feedbackFilterStatus = document.getElementById('feedbackFilterStatus');
if (feedbackFilterStatus) {
    feedbackFilterStatus.addEventListener('change', async () => {
        await loadFeedbacksAdmin();
    });
}
const reloadForumBtn = document.getElementById('reloadForumBtn');
if (reloadForumBtn) {
    reloadForumBtn.addEventListener('click', async () => {
        await loadForumAdmin();
    });
}
const forumFilterStatus = document.getElementById('forumFilterStatus');
if (forumFilterStatus) {
    forumFilterStatus.addEventListener('change', async () => {
        await loadForumAdmin();
    });
}
</script>
</body>
</html>
