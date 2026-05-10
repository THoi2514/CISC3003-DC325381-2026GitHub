function initPublicI18n(pageI18n) {
    const i18nMap =
        typeof mergeFooterI18n === 'function' ? mergeFooterI18n(pageI18n) : pageI18n;
    let lang = localStorage.getItem('lang') || 'en';

    function t(key) {
        return (i18nMap[lang] && i18nMap[lang][key]) || i18nMap.en[key] || key;
    }

    function applyLanguage() {
        document.documentElement.lang = lang;
        document.title = t('title');
        document.querySelectorAll('[data-i18n]').forEach((el) => {
            el.textContent = t(el.dataset.i18n);
        });
    }

    const langSelect = document.getElementById('languageSelect');
    if (langSelect) {
        langSelect.value = lang;
        langSelect.addEventListener('change', () => {
            lang = langSelect.value;
            localStorage.setItem('lang', lang);
            applyLanguage();
        });
    }
    applyLanguage();
}
