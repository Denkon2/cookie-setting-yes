(function () {
    'use strict';

    var i18n = window.CookieSettingYesAdmin && window.CookieSettingYesAdmin.i18n ? window.CookieSettingYesAdmin.i18n : {};

    var storageKey = 'csy_admin_theme';
    var admin = document.querySelector('.csy-admin[data-csy-admin-theme]');
    var toggle = document.getElementById('csy-admin-theme-toggle');
    if (!admin || !toggle) return;

    function setTheme(theme) {
        admin.dataset.csyAdminTheme = theme;
        toggle.setAttribute('aria-pressed', theme === 'dark' ? 'true' : 'false');
        toggle.querySelector('.dashicons').className = 'dashicons ' + (theme === 'dark' ? 'dashicons-lightbulb' : 'dashicons-visibility');
    }

    var savedTheme = localStorage.getItem(storageKey);
    var initialTheme = savedTheme || (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
    setTheme(initialTheme);

    toggle.addEventListener('click', function () {
        var theme = admin.dataset.csyAdminTheme === 'dark' ? 'light' : 'dark';
        localStorage.setItem(storageKey, theme);
        setTheme(theme);
    });
}());

(function () {
    'use strict';

    var storageKey = 'csy_admin_panels';
    var panels = document.querySelectorAll('.csy-panel[id]');
    if (!panels.length) return;

    var savedState = {};
    try {
        savedState = JSON.parse(localStorage.getItem(storageKey)) || {};
    } catch (error) {
        savedState = {};
    }

    function saveState() {
        try {
            localStorage.setItem(storageKey, JSON.stringify(savedState));
        } catch (error) {}
    }

    function setCollapsed(panel, button, collapsed, persist) {
        var icon = button.querySelector('.dashicons');
        panel.classList.toggle('is-collapsed', collapsed);
        button.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
        button.setAttribute('aria-label', collapsed ? i18n.expandSection : i18n.collapseSection);
        button.setAttribute('title', collapsed ? i18n.expandSection : i18n.collapseSection);
        if (icon) icon.className = 'dashicons ' + (collapsed ? 'dashicons-arrow-down-alt2' : 'dashicons-arrow-up-alt2');

        if (persist) {
            savedState[panel.id] = collapsed;
            saveState();
        }
    }

    panels.forEach(function (panel) {
        var heading = panel.querySelector(':scope > .csy-panel__heading');
        if (!heading) return;

        var tools = document.createElement('div');
        tools.className = 'csy-panel__tools';
        Array.prototype.slice.call(heading.children, 1).forEach(function (element) {
            tools.appendChild(element);
        });

        var button = document.createElement('button');
        button.type = 'button';
        button.className = 'csy-panel__toggle';
        button.innerHTML = '<span class="dashicons dashicons-arrow-up-alt2" aria-hidden="true"></span>';
        tools.appendChild(button);
        heading.appendChild(tools);

        var body = document.createElement('div');
        var inner = document.createElement('div');
        body.className = 'csy-panel__body';
        body.id = panel.id + '-body';
        inner.className = 'csy-panel__body-inner';
        while (heading.nextSibling) inner.appendChild(heading.nextSibling);
        body.appendChild(inner);
        panel.appendChild(body);
        button.setAttribute('aria-controls', body.id);

        setCollapsed(panel, button, savedState[panel.id] === true, false);
        button.addEventListener('click', function () {
            setCollapsed(panel, button, !panel.classList.contains('is-collapsed'), true);
        });

        panel.csyExpand = function () {
            if (panel.classList.contains('is-collapsed')) setCollapsed(panel, button, false, true);
        };
    });

    document.addEventListener('click', function (event) {
        var link = event.target.closest('a[href^="#"]');
        if (!link) return;
        var href = link.getAttribute('href');
        if (!href || href.length < 2) return;
        var target = document.querySelector(href);
        var panel = target && target.closest('.csy-panel');
        if (panel && panel.csyExpand) panel.csyExpand();
    });
}());

(function () {
    'use strict';

    var i18n = window.CookieSettingYesAdmin && window.CookieSettingYesAdmin.i18n ? window.CookieSettingYesAdmin.i18n : {};

    var verification = document.querySelector('[data-csy-verification]');
    var inputs = document.querySelectorAll('[data-csy-manual-check]');
    if (!verification || !inputs.length) return;

    var count = document.querySelector('[data-csy-manual-count]');
    var status = document.querySelector('[data-csy-verification-status]');
    var changed = false;

    function updateVerification() {
        var completed = 0;
        inputs.forEach(function (input) {
            var item = input.closest('li');
            if (input.checked) completed++;
            if (item) item.classList.toggle('is-ready', input.checked);
        });

        if (count) count.textContent = completed + ' ' + i18n.countSeparator + ' ' + inputs.length;
        if (changed && status) status.textContent = i18n.checksChanged + ' ' + completed + ' ' + i18n.countSeparator + ' ' + inputs.length + '.';
    }

    inputs.forEach(function (input) {
        input.addEventListener('change', function () {
            changed = true;
            updateVerification();
        });
    });
}());
