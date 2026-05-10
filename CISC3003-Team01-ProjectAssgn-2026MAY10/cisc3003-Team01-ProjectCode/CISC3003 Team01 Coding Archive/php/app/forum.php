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
$activePage = 'forum';
$currentUserId = (int)($_SESSION['user_id'] ?? 0);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php require $projectRoot . '/includes/favicon_links.php'; ?>
    <meta name="csrf-token" content="<?= htmlspecialchars($csrfToken) ?>">
    <meta name="current-user-id" content="<?= $currentUserId ?>">
    <title>Forum Plaza</title>
    <link rel="stylesheet" href="./assets/um_landing.css?v=<?= file_exists($projectRoot . '/assets/um_landing.css') ? filemtime($projectRoot . '/assets/um_landing.css') : time() ?>">
    <link rel="stylesheet" href="./assets/home-landing.css?v=<?= file_exists($projectRoot . '/assets/home-landing.css') ? filemtime($projectRoot . '/assets/home-landing.css') : time() ?>">
    <link rel="stylesheet" href="./assets/dashboard.css?v=<?= file_exists($projectRoot . '/assets/dashboard.css') ? filemtime($projectRoot . '/assets/dashboard.css') : time() ?>">
    <link rel="stylesheet" href="./assets/forum.css?v=<?= file_exists($projectRoot . '/assets/forum.css') ? filemtime($projectRoot . '/assets/forum.css') : time() ?>">
    <link rel="stylesheet" href="./assets/site-footer.css?v=<?= file_exists($projectRoot . '/assets/site-footer.css') ? filemtime($projectRoot . '/assets/site-footer.css') : time() ?>">
</head>
<body>
<?php require $projectRoot . '/includes/public_header.php'; ?>
<main class="wrap forum-wrap">
    <section class="card forum-compose">
        <h2 data-i18n="forumPlazaTitle">Forum Plaza</h2>
        <p class="meta" data-i18n="forumPlazaHint">Share photos, questions and tips. All logged-in users can see the posts.</p>
        <div class="forum-compose-grid">
            <div>
                <label for="postCategory" data-i18n="forumCategory">Category</label>
                <select id="postCategory">
                    <option value="help" data-i18n="forumCatHelp">Help</option>
                    <option value="experience" data-i18n="forumCatExperience">Experience</option>
                    <option value="suggestion" data-i18n="forumCatSuggestion">Suggestion</option>
                    <option value="lost_found" data-i18n="forumCatLostFound">Lost & Found</option>
                    <option value="other" data-i18n="forumCatOther">Other</option>
                </select>
            </div>
            <div>
                <label for="postTitle" data-i18n="forumPostTitle">Title</label>
                <input id="postTitle" maxlength="180" data-i18n-placeholder="forumTitlePlaceholder" placeholder="Write a short title">
            </div>
        </div>
        <label for="postContent" data-i18n="forumContent">Content</label>
        <textarea id="postContent" rows="5" data-i18n-placeholder="forumContentPlaceholder" placeholder="Share your thoughts..."></textarea>
        <div class="forum-image-row">
            <input id="postImage" type="file" accept="image/jpeg,image/png,image/webp,image/gif">
            <img id="postImagePreview" class="forum-image-preview is-hidden" alt="">
        </div>
        <div class="controls">
            <button id="publishPostBtn" data-i18n="forumPublishBtn">Publish Post</button>
            <span class="message" id="forumComposeMsg"></span>
        </div>
    </section>

    <section class="card">
        <div class="forum-filter-grid">
            <input id="feedKeyword" data-i18n-placeholder="forumSearchPlaceholder" placeholder="Search posts...">
            <select id="feedCategory">
                <option value="" data-i18n="forumFilterAll">All categories</option>
                <option value="help" data-i18n="forumCatHelp">Help</option>
                <option value="experience" data-i18n="forumCatExperience">Experience</option>
                <option value="suggestion" data-i18n="forumCatSuggestion">Suggestion</option>
                <option value="lost_found" data-i18n="forumCatLostFound">Lost & Found</option>
                <option value="other" data-i18n="forumCatOther">Other</option>
            </select>
            <button id="feedSearchBtn" class="secondary" data-i18n="forumSearchBtn">Search</button>
        </div>

        <div id="timeline" class="timeline-grid"></div>
        <div class="forum-pagination">
            <button id="timelinePrevBtn" class="secondary" type="button" data-i18n="forumPrevPage">Previous</button>
            <span id="timelinePageText" class="forum-page-indicator"></span>
            <button id="timelineNextBtn" class="secondary" type="button" data-i18n="forumNextPage">Next</button>
        </div>
    </section>
</main>
<div id="imagePreviewModal" class="modal-backdrop forum-image-modal" aria-hidden="true">
    <div class="modal-panel forum-image-modal-panel" role="dialog" aria-modal="true" aria-labelledby="imagePreviewModalTitle">
        <div class="modal-head">
            <h3 id="imagePreviewModalTitle" data-i18n="forumImagePreviewTitle">Image Preview</h3>
            <button type="button" class="secondary" id="closeImagePreviewBtn" data-i18n="closeBtn">Close</button>
        </div>
        <div class="forum-image-modal-body">
            <img id="imagePreviewModalImg" class="forum-image-modal-img" alt="">
        </div>
    </div>
</div>
<?php require $projectRoot . '/includes/public_footer.php'; ?>
<script src="./assets/footer_i18n.js?v=<?= file_exists($projectRoot . '/assets/footer_i18n.js') ? filemtime($projectRoot . '/assets/footer_i18n.js') : time() ?>"></script>
<script>
const i18n = mergeFooterI18n({
    en: {
        pageTitle: 'Forum Plaza',
        navBrand: 'UM Rental',
        navHome: 'Home',
        navContact: 'Contact Us',
        navAccount: 'My Account',
        navLogout: 'Logout',
        navMyRental: 'My rental',
        navUserDashboard: 'User Dashboard',
        navStaffHome: 'Staff portal',
        navStaffHomeShort: 'Staff',
        navAdminPortal: 'Admin portal',
        closeBtn: 'Close',
        forumPlazaTitle: 'Forum Plaza',
        forumImagePreviewTitle: 'Image Preview',
        forumPlazaHint: 'Share photos, questions and tips. All logged-in users can see the posts.',
        forumCategory: 'Category',
        forumCatHelp: 'Help',
        forumCatExperience: 'Experience',
        forumCatSuggestion: 'Suggestion',
        forumCatLostFound: 'Lost & Found',
        forumCatOther: 'Other',
        forumPostTitle: 'Title',
        forumTitlePlaceholder: 'Write a short title',
        forumContent: 'Content',
        forumContentPlaceholder: 'Share your thoughts...',
        forumPublishBtn: 'Publish Post',
        forumSearchPlaceholder: 'Search posts...',
        forumFilterAll: 'All categories',
        forumSearchBtn: 'Search',
        forumPrevPage: 'Previous',
        forumNextPage: 'Next',
        forumPageText: 'Page {page} / {totalPages} · {total} posts',
        forumRequired: 'Please complete title and content.',
        forumNoPosts: 'No posts yet.',
        forumNoReplies: 'No replies yet.',
        forumReplyPlaceholder: 'Write a comment...',
        forumReplyBtn: 'Reply',
        forumRepliesBtn: 'View replies',
        forumHideRepliesBtn: 'Hide replies',
        forumDeleteBtn: 'Delete',
        forumDeleteConfirm: 'Delete this post?',
        forumPostSuccess: 'Post published.',
        forumReplySuccess: 'Reply posted.',
        forumPostedBy: 'By {name}',
    },
    'zh-CN': {
        pageTitle: '论坛广场',
        navBrand: 'UM 租赁',
        navHome: '首页',
        navContact: '联系我们',
        navAccount: '我的账户',
        navLogout: '退出',
        navMyRental: '我的租借',
        navUserDashboard: '用户仪表板',
        navStaffHome: '员工入口',
        navStaffHomeShort: '员工',
        navAdminPortal: '管理入口',
        closeBtn: '关闭',
        forumPlazaTitle: '论坛广场',
        forumImagePreviewTitle: '图片预览',
        forumPlazaHint: '可发布图片、问题与经验贴，登录用户都能看到。',
        forumCategory: '分类',
        forumCatHelp: '求助',
        forumCatExperience: '经验分享',
        forumCatSuggestion: '建议',
        forumCatLostFound: '失物招领',
        forumCatOther: '其他',
        forumPostTitle: '标题',
        forumTitlePlaceholder: '写一个简短标题',
        forumContent: '内容',
        forumContentPlaceholder: '分享你的想法...',
        forumPublishBtn: '发布帖子',
        forumSearchPlaceholder: '搜索帖子...',
        forumFilterAll: '全部分类',
        forumSearchBtn: '搜索',
        forumPrevPage: '上一页',
        forumNextPage: '下一页',
        forumPageText: '第 {page} / {totalPages} 页 · 共 {total} 条',
        forumRequired: '请填写标题和内容。',
        forumNoPosts: '暂无帖子。',
        forumNoReplies: '暂无回复。',
        forumReplyPlaceholder: '写下评论...',
        forumReplyBtn: '回复',
        forumRepliesBtn: '查看回复',
        forumHideRepliesBtn: '收起回复',
        forumDeleteBtn: '删除',
        forumDeleteConfirm: '确定删除这条帖子吗？',
        forumPostSuccess: '帖子已发布。',
        forumReplySuccess: '回复已发布。',
        forumPostedBy: '作者 {name}',
    },
    'zh-TW': {
        pageTitle: '論壇廣場',
        navBrand: 'UM 租賃',
        navHome: '首頁',
        navContact: '聯繫我們',
        navAccount: '我的帳戶',
        navLogout: '登出',
        navMyRental: '我的租借',
        navUserDashboard: '用戶儀表板',
        navStaffHome: '員工入口',
        navStaffHomeShort: '員工',
        navAdminPortal: '管理入口',
        closeBtn: '關閉',
        forumPlazaTitle: '論壇廣場',
        forumImagePreviewTitle: '圖片預覽',
        forumPlazaHint: '可發佈圖片、問題與經驗貼，登入用戶都能看到。',
        forumCategory: '分類',
        forumCatHelp: '求助',
        forumCatExperience: '經驗分享',
        forumCatSuggestion: '建議',
        forumCatLostFound: '失物招領',
        forumCatOther: '其他',
        forumPostTitle: '標題',
        forumTitlePlaceholder: '寫一個簡短標題',
        forumContent: '內容',
        forumContentPlaceholder: '分享你的想法...',
        forumPublishBtn: '發佈帖子',
        forumSearchPlaceholder: '搜尋帖子...',
        forumFilterAll: '全部分類',
        forumSearchBtn: '搜尋',
        forumPrevPage: '上一頁',
        forumNextPage: '下一頁',
        forumPageText: '第 {page} / {totalPages} 頁 · 共 {total} 條',
        forumRequired: '請填寫標題和內容。',
        forumNoPosts: '暫無帖子。',
        forumNoReplies: '暫無回覆。',
        forumReplyPlaceholder: '寫下評論...',
        forumReplyBtn: '回覆',
        forumRepliesBtn: '查看回覆',
        forumHideRepliesBtn: '收起回覆',
        forumDeleteBtn: '刪除',
        forumDeleteConfirm: '確定刪除這則帖子嗎？',
        forumPostSuccess: '帖子已發佈。',
        forumReplySuccess: '回覆已發佈。',
        forumPostedBy: '作者 {name}',
    }
});

let lang = localStorage.getItem('lang') || 'en';
const currentUserId = Number(document.querySelector('meta[name="current-user-id"]')?.content || 0);
const feedState = { page: 1, pageSize: 8, total: 0, totalPages: 1 };
const replyCache = {};

function t(key) { return (i18n[lang] && i18n[lang][key]) || i18n.en[key] || key; }
function escapeHtml(v) {
    return String(v ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}
function applyLanguage() {
    document.documentElement.lang = lang;
    document.title = t('pageTitle');
    document.querySelectorAll('[data-i18n]').forEach((el) => { el.textContent = t(el.dataset.i18n); });
    document.querySelectorAll('[data-i18n-placeholder]').forEach((el) => { el.placeholder = t(el.dataset.i18nPlaceholder); });
    renderPagination();
}
function apiUrl(action, params = {}) {
    const url = new URL('./rental_action.php', window.location.href);
    url.searchParams.set('action', action);
    Object.entries(params).forEach(([k, v]) => {
        if (v !== undefined && v !== null && String(v) !== '') url.searchParams.set(k, String(v));
    });
    return url.toString();
}
async function apiGet(action, params = {}) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const res = await fetch(apiUrl(action, params), { headers: { Accept: 'application/json', 'X-CSRF-Token': csrfToken } });
    const data = await res.json();
    if (!data.success) throw new Error(data.message || 'Request failed');
    return data;
}
async function apiPostJson(action, payload = {}) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const res = await fetch(apiUrl(action), {
        method: 'POST',
        headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
        body: JSON.stringify(payload),
    });
    const data = await res.json();
    if (!data.success) throw new Error(data.message || 'Request failed');
    return data;
}
async function apiPostForm(action, formData) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const res = await fetch(apiUrl(action), {
        method: 'POST',
        headers: { Accept: 'application/json', 'X-CSRF-Token': csrfToken },
        body: formData,
    });
    const data = await res.json();
    if (!data.success) throw new Error(data.message || 'Request failed');
    return data;
}
function imageUrl(path) {
    if (!path) return '';
    if (/^https?:\/\//i.test(path)) return path;
    return `./${String(path).replace(/^\/+/, '')}`;
}
function categoryLabel(category) {
    const m = { help: 'forumCatHelp', experience: 'forumCatExperience', suggestion: 'forumCatSuggestion', lost_found: 'forumCatLostFound', other: 'forumCatOther' };
    return t(m[category] || 'forumCatOther');
}
function renderPagination() {
    const prevBtn = document.getElementById('timelinePrevBtn');
    const nextBtn = document.getElementById('timelineNextBtn');
    const textEl = document.getElementById('timelinePageText');
    prevBtn.disabled = feedState.page <= 1;
    nextBtn.disabled = feedState.page >= feedState.totalPages;
    textEl.textContent = t('forumPageText')
        .replace('{page}', String(feedState.page))
        .replace('{totalPages}', String(feedState.totalPages))
        .replace('{total}', String(feedState.total));
}
function renderReplies(postId) {
    const box = document.getElementById(`reply-list-${postId}`);
    if (!box) return;
    const replies = replyCache[String(postId)] || [];
    if (!replies.length) {
        box.innerHTML = `<div class="meta">${escapeHtml(t('forumNoReplies'))}</div>`;
        return;
    }
    box.innerHTML = replies.map((r) => `
        <div class="reply-item">
            <strong>${escapeHtml(r.full_name || '-')}</strong>
            <span class="meta">${escapeHtml(r.campus_id || '-')} · ${escapeHtml(r.created_at || '')}</span>
            <p>${escapeHtml(r.reply_content || '')}</p>
        </div>
    `).join('');
}
function renderFeed(posts) {
    const timeline = document.getElementById('timeline');
    if (!Array.isArray(posts) || !posts.length) {
        timeline.innerHTML = `<div class="forum-empty">${escapeHtml(t('forumNoPosts'))}</div>`;
        return;
    }
    timeline.innerHTML = posts.map((p) => {
        const isOwner = Number(p.user_id || 0) === currentUserId;
        const img = p.image_path ? `<img class="timeline-image" src="${escapeHtml(imageUrl(p.image_path))}" alt="" role="button" tabindex="0">` : '';
        const ownerAction = isOwner ? `<button type="button" class="danger timeline-delete-btn" onclick="deletePost(${Number(p.id)})">${escapeHtml(t('forumDeleteBtn'))}</button>` : '';
        return `
            <article class="timeline-card" id="post-${p.id}">
                <div class="timeline-head">
                    <div>
                        <span class="forum-chip">${escapeHtml(categoryLabel(String(p.category || 'other')))}</span>
                        <h3>${escapeHtml(p.title || '-')}</h3>
                        <p class="meta">${escapeHtml(t('forumPostedBy').replace('{name}', String(p.full_name || '-')))} · ${escapeHtml(p.created_at || '')}</p>
                    </div>
                    ${ownerAction}
                </div>
                <p class="timeline-content">${escapeHtml(p.content || '')}</p>
                ${img}
                <div class="timeline-reply-box">
                    <button class="secondary" type="button" onclick="toggleReplies(${Number(p.id)})" id="reply-toggle-${p.id}">${escapeHtml(t('forumRepliesBtn'))}</button>
                    <div id="reply-wrap-${p.id}" class="is-hidden">
                        <div id="reply-list-${p.id}" class="reply-list"></div>
                        <div class="reply-compose">
                            <textarea id="reply-input-${p.id}" rows="2" placeholder="${escapeHtml(t('forumReplyPlaceholder'))}"></textarea>
                            <button type="button" onclick="submitReply(${Number(p.id)})">${escapeHtml(t('forumReplyBtn'))}</button>
                        </div>
                    </div>
                </div>
            </article>
        `;
    }).join('');
}
function openImagePreview(src) {
    const modal = document.getElementById('imagePreviewModal');
    const img = document.getElementById('imagePreviewModalImg');
    if (!modal || !img || !src) return;
    img.src = src;
    modal.classList.add('show');
    modal.setAttribute('aria-hidden', 'false');
}

function closeImagePreview() {
    const modal = document.getElementById('imagePreviewModal');
    const img = document.getElementById('imagePreviewModalImg');
    if (!modal || !img) return;
    modal.classList.remove('show');
    modal.setAttribute('aria-hidden', 'true');
    img.removeAttribute('src');
}

document.getElementById('timeline').addEventListener('click', (e) => {
    const img = e.target instanceof HTMLElement ? e.target.closest('.timeline-image') : null;
    if (!img || !(img instanceof HTMLImageElement)) return;
    openImagePreview(img.src);
});

document.getElementById('timeline').addEventListener('keydown', (e) => {
    if (e.key !== 'Enter' && e.key !== ' ') return;
    const img = e.target instanceof HTMLElement ? e.target.closest('.timeline-image') : null;
    if (!img || !(img instanceof HTMLImageElement)) return;
    e.preventDefault();
    openImagePreview(img.src);
});

document.getElementById('closeImagePreviewBtn').addEventListener('click', closeImagePreview);
document.getElementById('imagePreviewModal').addEventListener('click', (e) => {
    if (e.target.id === 'imagePreviewModal') closeImagePreview();
});

async function loadFeed(page = feedState.page) {
    const keyword = document.getElementById('feedKeyword').value.trim();
    const category = document.getElementById('feedCategory').value;
    const data = await apiGet('account_forum_feed', { keyword, category, page, pageSize: feedState.pageSize });
    const pg = data.pagination || {};
    feedState.page = Number(pg.page || 1);
    feedState.pageSize = Number(pg.pageSize || feedState.pageSize);
    feedState.total = Number(pg.total || 0);
    feedState.totalPages = Number(pg.totalPages || 1);
    renderFeed(data.data || []);
    renderPagination();
}
async function toggleReplies(postId) {
    const wrap = document.getElementById(`reply-wrap-${postId}`);
    const btn = document.getElementById(`reply-toggle-${postId}`);
    if (!wrap || !btn) return;
    const willOpen = wrap.classList.contains('is-hidden');
    wrap.classList.toggle('is-hidden');
    btn.textContent = willOpen ? t('forumHideRepliesBtn') : t('forumRepliesBtn');
    if (!willOpen) return;
    if (!replyCache[String(postId)]) {
        const data = await apiGet('forum_post_replies', { postID: postId });
        replyCache[String(postId)] = data.data || [];
    }
    renderReplies(postId);
}
window.toggleReplies = toggleReplies;

async function submitReply(postId) {
    const input = document.getElementById(`reply-input-${postId}`);
    const content = (input?.value || '').trim();
    if (!content) return;
    try {
        await apiPostJson('forum_post_reply_submit', { postID: postId, replyContent: content });
        input.value = '';
        const data = await apiGet('forum_post_replies', { postID: postId });
        replyCache[String(postId)] = data.data || [];
        renderReplies(postId);
    } catch (e) {
        alert(e.message);
    }
}
window.submitReply = submitReply;

async function deletePost(postId) {
    if (!confirm(t('forumDeleteConfirm'))) return;
    try {
        await apiPostJson('account_forum_delete', { postID: postId });
        await loadFeed(feedState.page);
    } catch (e) {
        alert(e.message);
    }
}
window.deletePost = deletePost;

document.getElementById('publishPostBtn').addEventListener('click', async () => {
    const title = document.getElementById('postTitle').value.trim();
    const content = document.getElementById('postContent').value.trim();
    const category = document.getElementById('postCategory').value;
    const imageFile = document.getElementById('postImage').files[0];
    const msg = document.getElementById('forumComposeMsg');
    if (!title || !content) {
        msg.textContent = t('forumRequired');
        return;
    }
    const fd = new FormData();
    fd.append('title', title);
    fd.append('content', content);
    fd.append('category', category);
    if (imageFile) fd.append('postImage', imageFile);
    try {
        const data = await apiPostForm('account_forum_submit', fd);
        msg.textContent = data.message || t('forumPostSuccess');
        document.getElementById('postTitle').value = '';
        document.getElementById('postContent').value = '';
        document.getElementById('postImage').value = '';
        document.getElementById('postImagePreview').classList.add('is-hidden');
        feedState.page = 1;
        await loadFeed(1);
    } catch (e) {
        msg.textContent = e.message;
    }
});
document.getElementById('postImage').addEventListener('change', () => {
    const file = document.getElementById('postImage').files[0];
    const img = document.getElementById('postImagePreview');
    if (!file) {
        img.classList.add('is-hidden');
        img.removeAttribute('src');
        return;
    }
    const reader = new FileReader();
    reader.onload = (ev) => {
        img.src = String(ev.target?.result || '');
        img.classList.remove('is-hidden');
    };
    reader.readAsDataURL(file);
});
document.getElementById('feedSearchBtn').addEventListener('click', async () => {
    feedState.page = 1;
    await loadFeed(1);
});
document.getElementById('feedCategory').addEventListener('change', async () => {
    feedState.page = 1;
    await loadFeed(1);
});
document.getElementById('feedKeyword').addEventListener('keydown', async (e) => {
    if (e.key !== 'Enter') return;
    feedState.page = 1;
    await loadFeed(1);
});
document.getElementById('timelinePrevBtn').addEventListener('click', async () => {
    if (feedState.page <= 1) return;
    await loadFeed(feedState.page - 1);
});
document.getElementById('timelineNextBtn').addEventListener('click', async () => {
    if (feedState.page >= feedState.totalPages) return;
    await loadFeed(feedState.page + 1);
});
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeImagePreview();
});

const langSelect = document.getElementById('languageSelect');
langSelect.value = lang;
langSelect.addEventListener('change', () => {
    lang = langSelect.value;
    localStorage.setItem('lang', lang);
    applyLanguage();
    loadFeed(feedState.page).catch(() => {});
});

applyLanguage();
loadFeed().catch((e) => {
    document.getElementById('timeline').innerHTML = `<div class="forum-empty">${escapeHtml(e.message)}</div>`;
});
</script>
</body>
</html>
