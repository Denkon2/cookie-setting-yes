(function () {
    'use strict';

    var storageKey = 'csy_consent';
    var visitorKey = 'csy_visitor_id';
    var banner = document.getElementById('csy-banner');
    var modal = document.getElementById('csy-preferences');
    var reopen = document.getElementById('csy-reopen');
    if (!banner || !modal || !window.CookieSettingYes) return;

    function getStored() {
        try {
            var value = JSON.parse(localStorage.getItem(storageKey));
            return value && value.version === CookieSettingYes.consentVersion ? value : null;
        } catch (error) {
            return null;
        }
    }

    function visitorId() {
        var id = localStorage.getItem(visitorKey);
        if (!id) {
            id = (window.crypto && crypto.randomUUID) ? crypto.randomUUID() : String(Date.now()) + '-' + Math.random().toString(36).slice(2);
            localStorage.setItem(visitorKey, id);
        }
        return id;
    }

    function setCookie(value) {
        var secure = location.protocol === 'https:' ? '; Secure' : '';
        document.cookie = storageKey + '=' + encodeURIComponent(JSON.stringify(value)) + '; path=/; max-age=31536000; SameSite=Lax' + secure;
    }

    function activateScripts(categories) {
        document.querySelectorAll('script[type="text/plain"][data-csy-category]').forEach(function (blocked) {
            if (!categories[blocked.dataset.csyCategory]) return;
            var script = document.createElement('script');
            Array.prototype.forEach.call(blocked.attributes, function (attribute) {
                if (attribute.name !== 'type' && attribute.name !== 'data-csy-category') script.setAttribute(attribute.name, attribute.value);
            });
            script.text = blocked.text || blocked.textContent;
            blocked.parentNode.replaceChild(script, blocked);
        });

        if (categories.external) {
            document.querySelectorAll('iframe[data-csy-src]').forEach(function (frame) {
                frame.setAttribute('src', frame.dataset.csySrc);
                frame.removeAttribute('data-csy-src');
                var placeholder = frame.closest('[data-csy-embed]');
                if (placeholder) {
                    var button = placeholder.querySelector('.csy-embed-placeholder__button');
                    if (button) button.remove();
                }
            });
        }
    }

    function syncControls(categories) {
        modal.querySelectorAll('[data-csy-category]').forEach(function (input) {
            if (!input.disabled) input.checked = !!categories[input.dataset.csyCategory];
        });
    }

    function sendLog(value, action) {
        if (!window.fetch) return;
        fetch(CookieSettingYes.endpoint, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            credentials: 'same-origin',
            body: JSON.stringify({visitorId: visitorId(), categories: value.categories, action: action})
        }).catch(function () {});
    }

    function googleConsent(categories) {
        if (!CookieSettingYes.googleConsentMode || typeof window.gtag !== 'function') return;
        window.gtag('consent', 'update', {
            analytics_storage: categories.analytics ? 'granted' : 'denied',
            ad_storage: categories.marketing ? 'granted' : 'denied',
            ad_user_data: categories.marketing ? 'granted' : 'denied',
            ad_personalization: categories.marketing ? 'granted' : 'denied',
            functionality_storage: categories.functional ? 'granted' : 'denied',
            personalization_storage: categories.functional ? 'granted' : 'denied',
            security_storage: 'granted'
        });
    }

    function syncWpConsentApi(categories) {
        window.wp_consent_type = 'optin';
        document.dispatchEvent(new CustomEvent('wp_consent_type_defined'));
        if (typeof window.wp_set_consent !== 'function') return;
        window.wp_set_consent('functional', categories.functional || categories.necessary ? 'allow' : 'deny');
        window.wp_set_consent('preferences', categories.functional ? 'allow' : 'deny');
        window.wp_set_consent('statistics', categories.analytics ? 'allow' : 'deny');
        window.wp_set_consent('statistics-anonymous', categories.analytics ? 'allow' : 'deny');
        window.wp_set_consent('marketing', categories.marketing ? 'allow' : 'deny');
    }

    function save(categories, action) {
        var value = {version: CookieSettingYes.consentVersion, categories: categories, updatedAt: new Date().toISOString()};
        localStorage.setItem(storageKey, JSON.stringify(value));
		setCookie(value);
		document.documentElement.dataset.csyConsent = 'saved';
		googleConsent(categories);
		syncWpConsentApi(categories);
        activateScripts(categories);
        syncControls(categories);
        sendLog(value, action);
        banner.hidden = true;
        modal.close();
        reopen.hidden = false;
    }

    function allCategories(enabled) {
        var result = {};
        Object.keys(CookieSettingYes.categories).forEach(function (slug) {
            result[slug] = !!CookieSettingYes.categories[slug].required || enabled;
        });
        return result;
    }

    function selectedCategories() {
        var result = {};
        modal.querySelectorAll('[data-csy-category]').forEach(function (input) {
            result[input.dataset.csyCategory] = input.disabled || input.checked;
        });
        return result;
    }

    document.querySelectorAll('[data-csy-action]').forEach(function (button) {
        button.addEventListener('click', function () {
            var action = button.dataset.csyAction;
            if (action === 'accept') save(allCategories(true), 'accept_all');
            if (action === 'reject') save(allCategories(false), 'reject_all');
            if (action === 'save') save(selectedCategories(), 'save');
        });
    });
    document.querySelectorAll('[data-csy-open], .csy-open-preferences').forEach(function (button) {
        button.addEventListener('click', function () { modal.showModal(); });
    });
    document.querySelectorAll('[data-csy-close]').forEach(function (button) {
        button.addEventListener('click', function () { modal.close(); });
    });
    reopen.addEventListener('click', function () { modal.showModal(); });

    var stored = getStored();
    if (stored) {
		googleConsent(stored.categories);
		syncWpConsentApi(stored.categories);
        syncControls(stored.categories);
        activateScripts(stored.categories);
        reopen.hidden = false;
    } else {
        banner.hidden = false;
    }
}());
