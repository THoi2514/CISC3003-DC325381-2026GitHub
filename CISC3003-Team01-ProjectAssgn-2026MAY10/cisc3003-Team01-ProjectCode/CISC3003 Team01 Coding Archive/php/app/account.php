<?php
declare(strict_types=1);
$projectRoot = dirname(__DIR__, 2);
require_once $projectRoot . '/includes/auth.php';
requireUserSession();
startAuthSession();
if (!isset($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token']) || $_SESSION['csrf_token'] === '') {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = (string)$_SESSION['csrf_token'];
$activePage = 'account';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php require $projectRoot . '/includes/favicon_links.php'; ?>
    <meta name="csrf-token" content="<?= htmlspecialchars($csrfToken) ?>">
    <title>My Account</title>
    <link rel="stylesheet" href="./assets/um_landing.css?v=<?= file_exists($projectRoot . '/assets/um_landing.css') ? filemtime($projectRoot . '/assets/um_landing.css') : time() ?>">
    <link rel="stylesheet" href="./assets/home-landing.css?v=<?= file_exists($projectRoot . '/assets/home-landing.css') ? filemtime($projectRoot . '/assets/home-landing.css') : time() ?>">
    <link rel="stylesheet" href="./assets/dashboard.css?v=<?= file_exists($projectRoot . '/assets/dashboard.css') ? filemtime($projectRoot . '/assets/dashboard.css') : time() ?>">
    <link rel="stylesheet" href="./assets/site-footer.css?v=<?= file_exists($projectRoot . '/assets/site-footer.css') ? filemtime($projectRoot . '/assets/site-footer.css') : time() ?>">
</head>
<body class="account-page">
<?php require $projectRoot . '/includes/public_header.php'; ?>
<main class="wrap">
    <section class="card grid-2">
        <div>
            <h2 data-i18n="profile">Profile</h2>
            <input id="fullName" placeholder="Full name" data-i18n-placeholder="fullNamePlaceholder">
            <input id="phone" placeholder="Phone" data-i18n-placeholder="phonePlaceholder">
            <button id="saveProfileBtn" data-i18n="saveProfile">Save Profile</button>
            <div class="message" id="profileMsg"></div>
        </div>
        <div>
            <h2 data-i18n="wallet">Wallet</h2>
            <div id="balanceText" data-i18n="balanceInit">Balance: -</div>
            <input id="topupAmount" type="number" min="1" step="1" placeholder="Top-up amount" data-i18n-placeholder="topupPlaceholder">
            <button id="openTopupModalBtn" data-i18n="topupWithCard">Top-up with card</button>
            <div id="adminTestWrap" class="is-hidden" style="margin-top:10px;">
                <input id="adminTestAmount" type="number" step="0.01" placeholder="Test balance amount" data-i18n-placeholder="adminTestPlaceholder">
                <button id="adminTestTopupBtn" class="secondary" data-i18n="adminTestTopup">Test Balance</button>
            </div>
            <div class="message" id="walletMsg"></div>
        </div>
    </section>
    <section class="card">
        <h2 data-i18n="walletTransactions">Wallet Transactions</h2>
        <table>
            <thead><tr><th data-i18n="colId">ID</th><th data-i18n="colType">Type</th><th data-i18n="colAmount">Amount</th><th data-i18n="colBalanceAfter">Balance After</th><th data-i18n="colReference">Reference</th><th data-i18n="colTime">Time</th></tr></thead>
            <tbody id="walletBody"></tbody>
        </table>
    </section>
    <section class="card">
        <h2 data-i18n="feedbackTitle">Feedback & Suggestions</h2>
        <div class="grid-2">
            <div class="feedback-form-block">
                <label for="feedbackCategory" data-i18n="feedbackCategory">Category</label>
                <select id="feedbackCategory">
                    <option value="bug" data-i18n="feedbackCatBug">Bug</option>
                    <option value="payment" data-i18n="feedbackCatPayment">Payment</option>
                    <option value="vehicle" data-i18n="feedbackCatVehicle">Vehicle</option>
                    <option value="station" data-i18n="feedbackCatStation">Station</option>
                    <option value="account" data-i18n="feedbackCatAccount">Account</option>
                    <option value="other" data-i18n="feedbackCatOther">Other</option>
                </select>
                <label for="feedbackTitleInput" data-i18n="feedbackTicketTitle">Title</label>
                <input id="feedbackTitleInput" maxlength="180" placeholder="e.g. Brake issue near E11" data-i18n-placeholder="feedbackTitlePlaceholder">
                <label for="feedbackDescInput" data-i18n="feedbackDescription">Description</label>
                <textarea id="feedbackDescInput" class="feedback-desc-input" rows="8" placeholder="Describe the issue details..." data-i18n-placeholder="feedbackDescPlaceholder"></textarea>
                <input id="editingFeedbackId" type="hidden" value="">
                <button id="submitFeedbackBtn" data-i18n="feedbackSubmitBtn">Submit Feedback</button>
                <button id="cancelFeedbackEditBtn" class="secondary is-hidden" type="button" data-i18n="feedbackCancelEdit">Cancel Edit</button>
                <div class="message" id="feedbackMsg"></div>
            </div>
            <div>
                <h3 data-i18n="feedbackHistory">My Feedback Tickets</h3>
                <table>
                    <thead>
                    <tr>
                        <th data-i18n="colId">ID</th>
                        <th data-i18n="feedbackCategory">Category</th>
                        <th data-i18n="feedbackTicketTitle">Title</th>
                        <th data-i18n="feedbackStatus">Status</th>
                        <th data-i18n="feedbackReply">Admin Reply</th>
                        <th data-i18n="action">Action</th>
                    </tr>
                    </thead>
                    <tbody id="feedbackBody"></tbody>
                </table>
            </div>
        </div>
    </section>
</main>
<div id="topupModal" class="modal-backdrop" aria-hidden="true">
    <div class="modal-panel" role="dialog" aria-modal="true" aria-labelledby="topupModalTitle">
        <div class="modal-head">
            <h3 id="topupModalTitle" data-i18n="topupModalTitle">Top-up by credit card</h3>
            <button class="secondary" id="closeTopupModalBtn" data-i18n="closeBtn">Close</button>
        </div>
        <div id="topupModalSummary" class="message" data-i18n="topupModalHint">Enter card details to complete payment securely.</div>
        <div class="modal-detail-grid">
            <div>
                <strong data-i18n="topupAmountLabel">Amount:</strong>
                <input id="modalTopupAmount" type="number" min="1" max="5000" step="0.01" data-i18n-placeholder="modalAmountPlaceholder" placeholder="Amount (MOP)">
            </div>
            <div>
                <strong data-i18n="cardHolderLabel">Cardholder:</strong>
                <input id="cardHolderName" type="text" data-i18n-placeholder="cardHolderPlaceholder" placeholder="Cardholder name">
            </div>
            <div>
                <strong data-i18n="cardNumberLabel">Card number:</strong>
                <input id="cardNumber" type="text" inputmode="numeric" maxlength="19" data-i18n-placeholder="cardNumberPlaceholder" placeholder="1234 5678 9012 3456">
            </div>
            <div>
                <strong data-i18n="cardExpiryLabel">Expiry (MM/YY):</strong>
                <input id="cardExpiry" type="text" inputmode="numeric" maxlength="5" data-i18n-placeholder="cardExpiryPlaceholder" placeholder="MM/YY">
            </div>
            <div>
                <strong data-i18n="cardCvcLabel">CVC:</strong>
                <input id="cardCvc" type="password" inputmode="numeric" maxlength="4" data-i18n-placeholder="cardCvcPlaceholder" placeholder="CVC">
            </div>
        </div>
        <div class="controls">
            <button id="confirmTopupBtn" data-i18n="confirmTopupBtn">Confirm payment</button>
        </div>
    </div>
</div>
<?php require $projectRoot . '/includes/public_footer.php'; ?>
<script src="./assets/footer_i18n.js?v=<?= file_exists($projectRoot . '/assets/footer_i18n.js') ? filemtime($projectRoot . '/assets/footer_i18n.js') : time() ?>"></script>
<script>
const i18n = mergeFooterI18n({
    en: {
        pageTitle: 'My Account',
        navBrand: 'UM Rental', navHome: 'Home', navFeatured: 'Services', navAbout: 'Why us', navHowto: 'How to use', navContact: 'Contact Us', cta: 'Login / Use Service',
        navAccount: 'My Account', navLogout: 'Logout', navMyRental: 'My rental', navUserDashboard: 'User Dashboard', navStaffHome: 'Staff portal', navStaffHomeShort: 'Staff', navAdminPortal: 'Admin portal',
        title: 'My Account', backDashboard: 'Back to Dashboard', logout: 'Logout',
        profile: 'Profile', fullNamePlaceholder: 'Full name', phonePlaceholder: 'Phone', saveProfile: 'Save Profile',
        wallet: 'Wallet', balanceInit: 'Balance: -', topupPlaceholder: 'Top-up amount', topup: 'Top-up',
        topupWithCard: 'Top-up with card', topupModalTitle: 'Top-up by credit card', topupModalHint: 'Enter card details to complete payment securely.',
        topupAmountLabel: 'Amount:', cardHolderLabel: 'Cardholder:', cardNumberLabel: 'Card number:', cardExpiryLabel: 'Expiry (MM/YY):', cardCvcLabel: 'CVC:',
        modalAmountPlaceholder: 'Amount (MOP)', cardHolderPlaceholder: 'Cardholder name', cardNumberPlaceholder: '1234 5678 9012 3456', cardExpiryPlaceholder: 'MM/YY', cardCvcPlaceholder: 'CVC',
        confirmTopupBtn: 'Confirm payment', closeBtn: 'Close',
        adminTestPlaceholder: 'Test balance amount', adminTestTopup: 'Test Balance',
        invalidCardInfo: 'Please provide valid card details.',
        walletTransactions: 'Wallet Transactions', colId: 'ID', colType: 'Type', colAmount: 'Amount', colBalanceAfter: 'Balance After', colReference: 'Reference', colTime: 'Time',
        balancePrefix: 'Balance', noWalletRecords: 'No wallet records yet.',
        forumTitle: 'Forum Center',
        forumCategory: 'Category',
        forumCatHelp: 'Help',
        forumCatExperience: 'Experience',
        forumCatSuggestion: 'Suggestion',
        forumCatLostFound: 'Lost & Found',
        forumCatOther: 'Other',
        forumPostTitle: 'Title',
        forumTitlePlaceholder: 'e.g. Best route from E11 to E2?',
        forumContent: 'Content',
        forumContentPlaceholder: 'Share your question or experience...',
        forumSubmitBtn: 'Post',
        forumCancelEdit: 'Cancel Edit',
        forumSearchPlaceholder: 'Search posts...',
        forumFilterAll: 'All categories',
        forumSearchBtn: 'Search',
        forumPublicList: 'Public Posts',
        forumMyPosts: 'My Posts',
        forumAuthor: 'Author',
        forumStatus: 'Status',
        forumNoPosts: 'No forum posts yet.',
        forumCreateSuccess: 'Forum post submitted.',
        forumUpdateSuccess: 'Forum post updated.',
        forumDeleteSuccess: 'Forum post deleted.',
        forumRequired: 'Please complete title and content.',
        forumStatusVisible: 'Visible',
        forumStatusLocked: 'Locked',
        forumStatusHidden: 'Hidden',
        forumStatusDeleted: 'Deleted',
        forumEditBtn: 'Edit',
        forumDeleteBtn: 'Delete',
        forumDeleteConfirm: 'Delete this post?',
        forumEditingBanner: 'Editing post #{id}',
        forumCannotEditStatus: 'Locked/hidden/deleted post cannot be edited by user.',
        forumPrevPage: 'Previous',
        forumNextPage: 'Next',
        forumPageText: 'Page {page} / {totalPages} · {total} posts',
        forumPostedBy: 'By {name}',
        forumOpenStandalone: 'Open Full Forum Page',
        feedbackTitle: 'Feedback & Suggestions',
        feedbackCategory: 'Category',
        feedbackCatBug: 'Bug',
        feedbackCatPayment: 'Payment',
        feedbackCatVehicle: 'Vehicle',
        feedbackCatStation: 'Station',
        feedbackCatAccount: 'Account',
        feedbackCatOther: 'Other',
        feedbackTicketTitle: 'Title',
        feedbackTitlePlaceholder: 'e.g. Brake issue near E11',
        feedbackDescription: 'Description',
        feedbackDescPlaceholder: 'Describe the issue details...',
        feedbackSubmitBtn: 'Submit Feedback',
        feedbackHistory: 'My Feedback Tickets',
        feedbackStatus: 'Status',
        feedbackReply: 'Admin Reply',
        action: 'Action',
        noFeedbackRecords: 'No feedback tickets yet.',
        feedbackSubmitSuccess: 'Feedback submitted.',
        feedbackUpdateSuccess: 'Feedback updated.',
        feedbackSubmitRequired: 'Please complete title and description.',
        feedbackStatusOpen: 'Open',
        feedbackStatusInProgress: 'In progress',
        feedbackStatusResolved: 'Resolved',
        feedbackStatusClosed: 'Closed',
        feedbackNoReplyYet: 'No reply yet.',
        feedbackEditBtn: 'Edit',
        feedbackDeleteBtn: 'Delete',
        feedbackDeleteConfirm: 'Delete this feedback ticket?',
        feedbackDeleteSuccess: 'Feedback deleted.',
        feedbackCancelEdit: 'Cancel Edit',
        feedbackEditingBanner: 'Editing feedback #{id}',
        feedbackCannotEditClosed: 'Resolved/closed feedback cannot be edited.',
    },
    'zh-CN': {
        pageTitle: '我的账户',
        navBrand: 'UM 租赁', navHome: '首页', navFeatured: '服务', navAbout: '核心价值', navHowto: '使用说明', navContact: '联系我们', cta: '立即登录 / 使用服务',
        navAccount: '我的账户', navLogout: '退出', navMyRental: '我的租借', navUserDashboard: '用户仪表板', navStaffHome: '员工入口', navStaffHomeShort: '员工', navAdminPortal: '管理入口',
        title: '我的账户', backDashboard: '返回仪表板', logout: '登出',
        profile: '个人资料', fullNamePlaceholder: '姓名', phonePlaceholder: '电话', saveProfile: '保存资料',
        wallet: '钱包', balanceInit: '余额: -', topupPlaceholder: '充值金额', topup: '充值',
        topupWithCard: '信用卡充值', topupModalTitle: '信用卡充值', topupModalHint: '请输入信用卡资料并确认支付。',
        topupAmountLabel: '金额：', cardHolderLabel: '持卡人：', cardNumberLabel: '卡号：', cardExpiryLabel: '到期日 (MM/YY)：', cardCvcLabel: '安全码：',
        modalAmountPlaceholder: '金额（MOP）', cardHolderPlaceholder: '持卡人姓名', cardNumberPlaceholder: '1234 5678 9012 3456', cardExpiryPlaceholder: 'MM/YY', cardCvcPlaceholder: '安全码',
        confirmTopupBtn: '确认支付', closeBtn: '关闭',
        adminTestPlaceholder: '测试入账金额', adminTestTopup: 'Test Balance',
        invalidCardInfo: '请填写有效的信用卡资料。',
        walletTransactions: '钱包流水', colId: '编号', colType: '类型', colAmount: '金额', colBalanceAfter: '余额(后)', colReference: '关联', colTime: '时间',
        balancePrefix: '余额', noWalletRecords: '暂无钱包记录。',
        forumTitle: '论坛中心',
        forumCategory: '分类',
        forumCatHelp: '求助',
        forumCatExperience: '经验分享',
        forumCatSuggestion: '建议',
        forumCatLostFound: '失物招领',
        forumCatOther: '其他',
        forumPostTitle: '标题',
        forumTitlePlaceholder: '例如 从 E11 到 E2 最快路线？',
        forumContent: '内容',
        forumContentPlaceholder: '请填写问题或分享内容...',
        forumSubmitBtn: '发布',
        forumCancelEdit: '取消编辑',
        forumSearchPlaceholder: '搜索帖子...',
        forumFilterAll: '全部分类',
        forumSearchBtn: '搜索',
        forumPublicList: '公开帖子',
        forumMyPosts: '我的帖子',
        forumAuthor: '作者',
        forumStatus: '状态',
        forumNoPosts: '暂无论坛帖子。',
        forumCreateSuccess: '帖子发布成功。',
        forumUpdateSuccess: '帖子已更新。',
        forumDeleteSuccess: '帖子已删除。',
        forumRequired: '请填写标题和内容。',
        forumStatusVisible: '可见',
        forumStatusLocked: '锁定',
        forumStatusHidden: '隐藏',
        forumStatusDeleted: '已删除',
        forumEditBtn: '编辑',
        forumDeleteBtn: '删除',
        forumDeleteConfirm: '确定删除这条帖子吗？',
        forumEditingBanner: '正在编辑帖子 #{id}',
        forumCannotEditStatus: '锁定/隐藏/删除状态的帖子不可编辑。',
        forumPrevPage: '上一页',
        forumNextPage: '下一页',
        forumPageText: '第 {page} / {totalPages} 页 · 共 {total} 条',
        forumPostedBy: '作者 {name}',
        forumOpenStandalone: '打开完整论坛页面',
        feedbackTitle: '反馈与建议',
        feedbackCategory: '分类',
        feedbackCatBug: '系统问题',
        feedbackCatPayment: '支付问题',
        feedbackCatVehicle: '车辆问题',
        feedbackCatStation: '站点问题',
        feedbackCatAccount: '账户问题',
        feedbackCatOther: '其他',
        feedbackTicketTitle: '标题',
        feedbackTitlePlaceholder: '例如 E11 附近刹车异常',
        feedbackDescription: '描述',
        feedbackDescPlaceholder: '请描述问题细节...',
        feedbackSubmitBtn: '提交反馈',
        feedbackHistory: '我的反馈工单',
        feedbackStatus: '状态',
        feedbackReply: '管理员回复',
        action: '操作',
        noFeedbackRecords: '暂无反馈工单。',
        feedbackSubmitSuccess: '反馈已提交。',
        feedbackUpdateSuccess: '反馈已更新。',
        feedbackSubmitRequired: '请填写标题和描述。',
        feedbackStatusOpen: '新建',
        feedbackStatusInProgress: '处理中',
        feedbackStatusResolved: '已解决',
        feedbackStatusClosed: '已关闭',
        feedbackNoReplyYet: '暂无回复。',
        feedbackEditBtn: '编辑',
        feedbackDeleteBtn: '删除',
        feedbackDeleteConfirm: '确定删除这条反馈工单吗？',
        feedbackDeleteSuccess: '反馈已删除。',
        feedbackCancelEdit: '取消编辑',
        feedbackEditingBanner: '正在编辑工单 #{id}',
        feedbackCannotEditClosed: '已解决/已关闭工单不可编辑。',
    },
    'zh-TW': {
        pageTitle: '我的帳戶',
        navBrand: 'UM 租賃', navHome: '首頁', navFeatured: '服務', navAbout: '核心價值', navHowto: '使用說明', navContact: '聯繫我們', cta: '立即登入 / 使用服務',
        navAccount: '我的帳戶', navLogout: '登出', navMyRental: '我的租借', navUserDashboard: '用戶儀表板', navStaffHome: '員工入口', navStaffHomeShort: '員工', navAdminPortal: '管理入口',
        title: '我的帳戶', backDashboard: '返回儀表板', logout: '登出',
        profile: '個人資料', fullNamePlaceholder: '姓名', phonePlaceholder: '電話', saveProfile: '儲存資料',
        wallet: '錢包', balanceInit: '餘額: -', topupPlaceholder: '儲值金額', topup: '儲值',
        topupWithCard: '信用卡儲值', topupModalTitle: '信用卡儲值', topupModalHint: '請輸入信用卡資料並確認付款。',
        topupAmountLabel: '金額：', cardHolderLabel: '持卡人：', cardNumberLabel: '卡號：', cardExpiryLabel: '到期日 (MM/YY)：', cardCvcLabel: '安全碼：',
        modalAmountPlaceholder: '金額（MOP）', cardHolderPlaceholder: '持卡人姓名', cardNumberPlaceholder: '1234 5678 9012 3456', cardExpiryPlaceholder: 'MM/YY', cardCvcPlaceholder: '安全碼',
        confirmTopupBtn: '確認付款', closeBtn: '關閉',
        adminTestPlaceholder: '測試入帳金額', adminTestTopup: 'Test Balance',
        invalidCardInfo: '請填寫有效的信用卡資料。',
        walletTransactions: '錢包流水', colId: '編號', colType: '類型', colAmount: '金額', colBalanceAfter: '餘額(後)', colReference: '關聯', colTime: '時間',
        balancePrefix: '餘額', noWalletRecords: '暫無錢包記錄。',
        forumTitle: '論壇中心',
        forumCategory: '分類',
        forumCatHelp: '求助',
        forumCatExperience: '經驗分享',
        forumCatSuggestion: '建議',
        forumCatLostFound: '失物招領',
        forumCatOther: '其他',
        forumPostTitle: '標題',
        forumTitlePlaceholder: '例如 從 E11 到 E2 最快路線？',
        forumContent: '內容',
        forumContentPlaceholder: '請填寫問題或分享內容...',
        forumSubmitBtn: '發佈',
        forumCancelEdit: '取消編輯',
        forumSearchPlaceholder: '搜尋帖子...',
        forumFilterAll: '全部分類',
        forumSearchBtn: '搜尋',
        forumPublicList: '公開帖子',
        forumMyPosts: '我的帖子',
        forumAuthor: '作者',
        forumStatus: '狀態',
        forumNoPosts: '暫無論壇帖子。',
        forumCreateSuccess: '帖子發佈成功。',
        forumUpdateSuccess: '帖子已更新。',
        forumDeleteSuccess: '帖子已刪除。',
        forumRequired: '請填寫標題和內容。',
        forumStatusVisible: '可見',
        forumStatusLocked: '鎖定',
        forumStatusHidden: '隱藏',
        forumStatusDeleted: '已刪除',
        forumEditBtn: '編輯',
        forumDeleteBtn: '刪除',
        forumDeleteConfirm: '確定刪除這則帖子嗎？',
        forumEditingBanner: '正在編輯帖子 #{id}',
        forumCannotEditStatus: '鎖定/隱藏/刪除狀態的帖子不可編輯。',
        forumPrevPage: '上一頁',
        forumNextPage: '下一頁',
        forumPageText: '第 {page} / {totalPages} 頁 · 共 {total} 條',
        forumPostedBy: '作者 {name}',
        forumOpenStandalone: '打開完整論壇頁面',
        feedbackTitle: '回饋與建議',
        feedbackCategory: '分類',
        feedbackCatBug: '系統問題',
        feedbackCatPayment: '付款問題',
        feedbackCatVehicle: '車輛問題',
        feedbackCatStation: '站點問題',
        feedbackCatAccount: '帳戶問題',
        feedbackCatOther: '其他',
        feedbackTicketTitle: '標題',
        feedbackTitlePlaceholder: '例如 E11 附近煞車異常',
        feedbackDescription: '描述',
        feedbackDescPlaceholder: '請描述問題細節...',
        feedbackSubmitBtn: '提交回饋',
        feedbackHistory: '我的回饋工單',
        feedbackStatus: '狀態',
        feedbackReply: '管理員回覆',
        action: '操作',
        noFeedbackRecords: '暫無回饋工單。',
        feedbackSubmitSuccess: '回饋已提交。',
        feedbackUpdateSuccess: '回饋已更新。',
        feedbackSubmitRequired: '請填寫標題和描述。',
        feedbackStatusOpen: '新建',
        feedbackStatusInProgress: '處理中',
        feedbackStatusResolved: '已解決',
        feedbackStatusClosed: '已關閉',
        feedbackNoReplyYet: '暫無回覆。',
        feedbackEditBtn: '編輯',
        feedbackDeleteBtn: '刪除',
        feedbackDeleteConfirm: '確定刪除這筆回饋工單嗎？',
        feedbackDeleteSuccess: '回饋已刪除。',
        feedbackCancelEdit: '取消編輯',
        feedbackEditingBanner: '正在編輯工單 #{id}',
        feedbackCannotEditClosed: '已解決/已關閉工單不可編輯。',
    }
});
let lang = localStorage.getItem('lang') || 'en';
let currentRole = '';
function t(k){ return (i18n[lang] && i18n[lang][k]) || i18n.en[k] || k; }
function applyLanguage(){
    document.documentElement.lang = lang;
    document.title = t('pageTitle');
    document.querySelectorAll('[data-i18n]').forEach(el => el.textContent = t(el.dataset.i18n));
    document.querySelectorAll('[data-i18n-placeholder]').forEach(el => el.placeholder = t(el.dataset.i18nPlaceholder));
}
async function api(action, method = 'GET', payload = null) {
    const url = new URL('./rental_action.php', window.location.href);
    url.searchParams.set('action', action);
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const options = { method, headers: { 'Accept': 'application/json', 'X-CSRF-Token': csrfToken } };
    if (payload) {
        options.headers['Content-Type'] = 'application/json';
        options.body = JSON.stringify(payload);
    }
    const res = await fetch(url, options);
    const data = await res.json();
    if (!data.success) throw new Error(data.message || 'Request failed');
    return data;
}

async function apiGet(action, params = {}) {
    const url = new URL('./rental_action.php', window.location.href);
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

function escapeHtml(s) {
    return String(s ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
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

function renderFeedbackRows(rows) {
    const body = document.getElementById('feedbackBody');
    if (!body) return;
    if (!rows || rows.length === 0) {
        body.innerHTML = `<tr><td colspan="6">${escapeHtml(t('noFeedbackRecords'))}</td></tr>`;
        return;
    }
    body.innerHTML = rows.map((row) => {
        const replies = Array.isArray(row.replies) ? row.replies : [];
        const latestReply = replies.length ? replies[replies.length - 1] : null;
        const replyText = latestReply
            ? `${escapeHtml(latestReply.reply_content)}<br><span class="meta">${escapeHtml(latestReply.admin_name || 'Admin')} · ${escapeHtml(latestReply.created_at || '')}</span>`
            : escapeHtml(t('feedbackNoReplyYet'));
        const canEdit = !['resolved', 'closed'].includes(String(row.status || ''));
        const encodedCategory = encodeURIComponent(String(row.category || 'other'));
        const encodedTitle = encodeURIComponent(String(row.title || ''));
        const encodedDesc = encodeURIComponent(String(row.description || ''));
        const actionBtns = canEdit
            ? `
                <button type="button" class="secondary" onclick="startFeedbackEdit(${Number(row.id)}, '${encodedCategory}', '${encodedTitle}', '${encodedDesc}')">${escapeHtml(t('feedbackEditBtn'))}</button>
                <button type="button" class="danger" onclick="deleteFeedback(${Number(row.id)})">${escapeHtml(t('feedbackDeleteBtn'))}</button>
              `
            : `<span class="meta">${escapeHtml(t('feedbackCannotEditClosed'))}</span>`;
        return `
            <tr>
                <td>${row.id}</td>
                <td>${escapeHtml(row.category || '-')}</td>
                <td>${escapeHtml(row.title || '-')}</td>
                <td>${escapeHtml(feedbackStatusText(String(row.status || 'open')))}</td>
                <td>${replyText}</td>
                <td>${actionBtns}</td>
            </tr>
        `;
    }).join('');
}

function resetFeedbackFormState() {
    const editingIdInput = document.getElementById('editingFeedbackId');
    const submitBtn = document.getElementById('submitFeedbackBtn');
    const cancelBtn = document.getElementById('cancelFeedbackEditBtn');
    if (editingIdInput) editingIdInput.value = '';
    if (submitBtn) submitBtn.textContent = t('feedbackSubmitBtn');
    if (cancelBtn) cancelBtn.classList.add('is-hidden');
}

function startFeedbackEdit(id, encodedCategory, encodedTitle, encodedDescription) {
    const category = decodeURIComponent(String(encodedCategory || 'other'));
    const title = decodeURIComponent(String(encodedTitle || ''));
    const description = decodeURIComponent(String(encodedDescription || ''));
    const editingIdInput = document.getElementById('editingFeedbackId');
    const titleInput = document.getElementById('feedbackTitleInput');
    const descInput = document.getElementById('feedbackDescInput');
    const catSelect = document.getElementById('feedbackCategory');
    const submitBtn = document.getElementById('submitFeedbackBtn');
    const cancelBtn = document.getElementById('cancelFeedbackEditBtn');
    const msgEl = document.getElementById('feedbackMsg');
    if (editingIdInput) editingIdInput.value = String(id);
    if (catSelect) catSelect.value = String(category || 'other');
    if (titleInput) titleInput.value = String(title || '');
    if (descInput) descInput.value = String(description || '');
    if (submitBtn) submitBtn.textContent = t('feedbackEditBtn');
    if (cancelBtn) cancelBtn.classList.remove('is-hidden');
    if (msgEl) msgEl.textContent = t('feedbackEditingBanner').replace('{id}', String(id));
}
window.startFeedbackEdit = startFeedbackEdit;

async function deleteFeedback(feedbackID) {
    if (!window.confirm(t('feedbackDeleteConfirm'))) return;
    const msgEl = document.getElementById('feedbackMsg');
    try {
        const data = await api('account_feedback_delete', 'POST', { feedbackID });
        msgEl.textContent = data.message || t('feedbackDeleteSuccess');
        if (Number(document.getElementById('editingFeedbackId').value || 0) === Number(feedbackID)) {
            document.getElementById('feedbackTitleInput').value = '';
            document.getElementById('feedbackDescInput').value = '';
            resetFeedbackFormState();
        }
        await loadFeedbacks();
    } catch (e) {
        msgEl.textContent = e.message;
    }
}
window.deleteFeedback = deleteFeedback;

async function loadFeedbacks() {
    const data = await api('account_feedback_list');
    renderFeedbackRows(data.data || []);
}

async function loadAccount() {
    const profile = await api('account_profile');
    currentRole = String(profile.data.role || '');
    document.getElementById('fullName').value = profile.data.full_name || '';
    document.getElementById('phone').value = profile.data.phone || '';
    document.getElementById('balanceText').textContent = `${t('balancePrefix')}: MOP ${Number(profile.data.balance || 0).toFixed(2)}`;
    document.getElementById('adminTestWrap').classList.toggle('is-hidden', currentRole !== 'admin');
    const wallet = await api('account_wallet_history');
    document.getElementById('walletBody').innerHTML = wallet.data.map((r) => `
      <tr>
        <td>${r.id}</td><td>${r.type}</td><td>${r.amount}</td><td>${r.balance_after}</td>
        <td>${r.reference_type || '-'}#${r.reference_id || '-'}</td><td>${r.created_at}</td>
      </tr>
    `).join('') || `<tr><td colspan="6">${t('noWalletRecords')}</td></tr>`;
    await loadFeedbacks();
}

document.getElementById('saveProfileBtn').addEventListener('click', async () => {
    try {
        const fullName = document.getElementById('fullName').value.trim();
        const phone = document.getElementById('phone').value.trim();
        const data = await api('account_update_profile', 'POST', { fullName, phone });
        document.getElementById('profileMsg').textContent = data.message;
    } catch (e) {
        document.getElementById('profileMsg').textContent = e.message;
    }
});

function openTopupModal() {
    const amount = Number(document.getElementById('topupAmount').value || 0);
    document.getElementById('modalTopupAmount').value = amount > 0 ? String(amount) : '';
    const modal = document.getElementById('topupModal');
    modal.classList.add('show');
    modal.setAttribute('aria-hidden', 'false');
}

function closeTopupModal() {
    const modal = document.getElementById('topupModal');
    modal.classList.remove('show');
    modal.setAttribute('aria-hidden', 'true');
}

document.getElementById('openTopupModalBtn').addEventListener('click', () => {
    openTopupModal();
});

document.getElementById('closeTopupModalBtn').addEventListener('click', () => {
    closeTopupModal();
});

document.getElementById('topupModal').addEventListener('click', (e) => {
    if (e.target.id === 'topupModal') closeTopupModal();
});

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeTopupModal();
});

document.getElementById('confirmTopupBtn').addEventListener('click', async () => {
    try {
        const amount = Number(document.getElementById('modalTopupAmount').value);
        const cardHolderName = document.getElementById('cardHolderName').value.trim();
        const cardNumber = String(document.getElementById('cardNumber').value || '').replace(/\s+/g, '');
        const cardExpiry = document.getElementById('cardExpiry').value.trim();
        const cardCvc = document.getElementById('cardCvc').value.trim();
        if (!amount || amount <= 0 || amount > 5000 || !cardHolderName || cardNumber.length < 12 || !/^\d{2}\/\d{2}$/.test(cardExpiry) || cardCvc.length < 3) {
            throw new Error(t('invalidCardInfo'));
        }
        const data = await api('account_topup', 'POST', { amount, payment_method: 'credit_card', card_last4: cardNumber.slice(-4) });
        document.getElementById('walletMsg').textContent = `${data.message}. New balance: MOP ${Number(data.data.balance).toFixed(2)}`;
        closeTopupModal();
        document.getElementById('topupAmount').value = '';
        document.getElementById('modalTopupAmount').value = '';
        document.getElementById('cardHolderName').value = '';
        document.getElementById('cardNumber').value = '';
        document.getElementById('cardExpiry').value = '';
        document.getElementById('cardCvc').value = '';
        await loadAccount();
    } catch (e) {
        document.getElementById('walletMsg').textContent = e.message;
    }
});

document.getElementById('adminTestTopupBtn').addEventListener('click', async () => {
    try {
        const amount = Number(document.getElementById('adminTestAmount').value);
        const data = await api('account_admin_test_topup', 'POST', { amount });
        document.getElementById('walletMsg').textContent = `${data.message}. New balance: MOP ${Number(data.data.balance).toFixed(2)}`;
        document.getElementById('adminTestAmount').value = '';
        await loadAccount();
    } catch (e) {
        document.getElementById('walletMsg').textContent = e.message;
    }
});

document.getElementById('submitFeedbackBtn').addEventListener('click', async () => {
    const title = document.getElementById('feedbackTitleInput').value.trim();
    const description = document.getElementById('feedbackDescInput').value.trim();
    const category = document.getElementById('feedbackCategory').value;
    const editingId = Number(document.getElementById('editingFeedbackId').value || 0);
    const msgEl = document.getElementById('feedbackMsg');
    if (!title || !description) {
        msgEl.textContent = t('feedbackSubmitRequired');
        return;
    }
    try {
        const data = editingId > 0
            ? await api('account_feedback_update', 'POST', { feedbackID: editingId, title, description, category })
            : await api('account_feedback_submit', 'POST', { title, description, category });
        msgEl.textContent = data.message || (editingId > 0 ? t('feedbackUpdateSuccess') : t('feedbackSubmitSuccess'));
        document.getElementById('feedbackTitleInput').value = '';
        document.getElementById('feedbackDescInput').value = '';
        resetFeedbackFormState();
        await loadFeedbacks();
    } catch (e) {
        msgEl.textContent = e.message;
    }
});

document.getElementById('cancelFeedbackEditBtn').addEventListener('click', () => {
    document.getElementById('feedbackTitleInput').value = '';
    document.getElementById('feedbackDescInput').value = '';
    resetFeedbackFormState();
    document.getElementById('feedbackMsg').textContent = '';
});

loadAccount().catch((err) => {
    document.getElementById('profileMsg').textContent = err.message;
});
const langSelect = document.getElementById('languageSelect');
langSelect.value = lang;
langSelect.addEventListener('change', () => {
    lang = langSelect.value;
    localStorage.setItem('lang', lang);
    applyLanguage();
    loadAccount().catch(() => {});
});
applyLanguage();
</script>
</body>
</html>
