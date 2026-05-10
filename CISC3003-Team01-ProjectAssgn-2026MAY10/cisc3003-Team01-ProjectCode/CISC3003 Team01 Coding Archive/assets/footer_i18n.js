/**
 * Shared footer copy for all surfaces (public, dashboard, staff, admin).
 * Loaded before page i18n; use mergeFooterI18n(pageMap) to combine with page strings.
 */
const FOOTER_I18N = {
    en: {
        navBrand: 'UM Rental',
        navForum: 'Forum',
        footColAbout: 'About UM Mobility',
        footLinkHome: 'Home',
        footLinkAbout: 'About the system',
        footLinkServices: 'Services',
        footLinkContact: 'Contact',
        footColPartner: 'Portals & access',
        footLinkLogin: 'Login',
        footLinkRegister: 'Register',
        footLinkStaff: 'Staff portal',
        footLinkAdmin: 'Admin portal',
        footColLegal: 'Terms & policies',
        footLinkTerms: 'Terms of service',
        footLinkPrivacy: 'Privacy policy',
        footLinkCookies: 'Cookie notice',
        footColPay: 'Payment methods',
        footPayHint: 'Wallet & campus payments (demo badges)',
        footLegalNotice:
            '© CISC3003 Team 01 · UM Mobility. Campus bicycle & scooter rental platform (academic project). Not a commercial travel agency. Email: dc32510@um.edu.mo · Service hours Mon–Fri 10:00–19:00 (Macau time). University of Macau, Taipa, Macau SAR.',
        footSocialLabel: 'Follow us',
    },
    'zh-CN': {
        navBrand: 'UM 租赁',
        navForum: '论坛',
        footColAbout: '认识 UM Mobility',
        footLinkHome: '首页',
        footLinkAbout: '关于系统',
        footLinkServices: '服务',
        footLinkContact: '联系我们',
        footColPartner: '入口与协作',
        footLinkLogin: '登录',
        footLinkRegister: '注册',
        footLinkStaff: '员工门户',
        footLinkAdmin: '管理员后台',
        footColLegal: '条款与政策',
        footLinkTerms: '服务条款',
        footLinkPrivacy: '隐私政策',
        footLinkCookies: 'Cookie 说明',
        footColPay: '付款方式',
        footPayHint: '钱包与校园支付（示意）',
        footLegalNotice:
            '© CISC3003 第01组 · 澳大智慧出行。校园单车与滑板车租赁平台（课程／演示项目，非商业旅行社）。联系：dc32510@um.edu.mo · 服务时间：周一至周五 10:00–19:00（澳门时间）。澳门大学（氹仔）。',
        footSocialLabel: '关注我们',
    },
    'zh-TW': {
        navBrand: 'UM 租賃',
        navForum: '論壇',
        footColAbout: '認識 UM Mobility',
        footLinkHome: '首頁',
        footLinkAbout: '關於系統',
        footLinkServices: '服務',
        footLinkContact: '聯絡我們',
        footColPartner: '入口與協作',
        footLinkLogin: '登入',
        footLinkRegister: '註冊',
        footLinkStaff: '職員入口',
        footLinkAdmin: '管理後台',
        footColLegal: '條款與政策',
        footLinkTerms: '服務條款',
        footLinkPrivacy: '隱私權政策',
        footLinkCookies: 'Cookie 說明',
        footColPay: '付款方式',
        footPayHint: '錢包與校園支付（示意）',
        footLegalNotice:
            '© CISC3003 第01組 · 澳門智慧出行。校園單車與滑板車租賃平台（課程／演示專案，非商業旅行社）。聯絡：dc32510@um.edu.mo · 服務時間：週一至週五 10:00–19:00（澳門時間）。澳門大學（氹仔）。',
        footSocialLabel: '關注我們',
    },
};

function mergeFooterI18n(pageI18n) {
    const langs = ['en', 'zh-CN', 'zh-TW'];
    const merged = {};
    langs.forEach((lang) => {
        merged[lang] = Object.assign({}, FOOTER_I18N[lang] || {}, pageI18n[lang] || {});
    });
    return merged;
}
