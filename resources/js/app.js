import './bootstrap';

document.addEventListener('DOMContentLoaded', () => {
    const privacyToggle = document.getElementById('financePrivacyToggle');
    const privacyStorageKey = 'al-suffaa-finance-privacy';
    const currencyPattern = /(?:\bRs\.?|\bPKR)\s*[\d,.]+/i;
    const financialInputSelector = [
        'input[name="monthly_fee"]',
        'input[name="basic_salary"]',
        'input[name="allowances"]',
        'input[name="deductions"]',
        'input[name="amount"]',
        'input[name="monthly_fee_amount"]',
        'input[name="uniform_amount"]',
        'input[name="books_amount"]',
        'input[name="paper_fund_amount"]',
        'input[name="other_amount"]',
        'input[name="previous_balance_amount"]',
        'input[name="discount_amount"]',
    ].join(',');

    const markFinancialValues = (root = document) => {
        const startingElement = root.nodeType === Node.TEXT_NODE ? root.parentElement : root;
        if (!startingElement) return;

        const elements = startingElement.matches?.('*')
            ? [startingElement, ...startingElement.querySelectorAll('*')]
            : [...startingElement.querySelectorAll('*')];

        elements.forEach((element) => {
            if (element.matches('script, style, noscript, .finance-privacy-toggle')) return;
            if (element.closest('.finance-privacy-toggle')) return;

            const directText = [...element.childNodes]
                .filter((node) => node.nodeType === Node.TEXT_NODE)
                .map((node) => node.textContent)
                .join(' ');
            const currencyCounter = element.dataset?.prefix?.trim().match(/^(Rs\.?|PKR)$/i);

            if (currencyPattern.test(directText) || currencyCounter) {
                element.classList.add('financial-value');
            }
        });

        if (startingElement.matches?.(financialInputSelector)) {
            startingElement.classList.add('financial-input');
        }
        startingElement.querySelectorAll?.(financialInputSelector).forEach((input) => {
            input.classList.add('financial-input');
        });
    };

    const updatePrivacyControl = () => {
        const hidden = document.documentElement.classList.contains('finance-private');
        const icon = privacyToggle?.querySelector('i');
        const label = privacyToggle?.querySelector('.finance-privacy-label');

        privacyToggle?.setAttribute('aria-pressed', String(hidden));
        privacyToggle?.setAttribute('aria-label', hidden ? 'Show financial amounts' : 'Hide financial amounts');
        privacyToggle?.setAttribute('title', hidden ? 'Show financial amounts' : 'Hide financial amounts');
        icon?.classList.toggle('fa-eye-slash', hidden);
        icon?.classList.toggle('fa-eye', !hidden);
        if (label) label.textContent = hidden ? 'Amounts hidden' : 'Amounts visible';
    };

    markFinancialValues();
    document.querySelectorAll('#feesChart, .mini-column-chart, .fee-progress, .fee-position-rate strong')
        .forEach((element) => element.classList.add('financial-visual'));
    document.body.classList.add('privacy-ready');
    updatePrivacyControl();

    privacyToggle?.addEventListener('click', () => {
        const hidden = document.documentElement.classList.toggle('finance-private');
        try {
            localStorage.setItem(privacyStorageKey, hidden ? 'hidden' : 'visible');
        } catch (error) {
            // The current page still toggles even when storage is unavailable.
        }
        updatePrivacyControl();
        window.dispatchEvent(new CustomEvent('finance-privacy-change', { detail: { hidden } }));
    });

    const privacyObserver = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            if (mutation.type === 'characterData') {
                markFinancialValues(mutation.target);
                return;
            }
            mutation.addedNodes.forEach((node) => {
                if (node.nodeType === Node.ELEMENT_NODE || node.nodeType === Node.TEXT_NODE) {
                    markFinancialValues(node);
                }
            });
        });
    });
    privacyObserver.observe(document.body, { childList: true, characterData: true, subtree: true });

    const sidebar = document.getElementById('portalSidebar');
    const menuButton = document.getElementById('mobileMenuButton');
    const scrim = document.getElementById('sidebarScrim');

    const setSidebar = (open) => {
        sidebar?.classList.toggle('is-open', open);
        scrim?.classList.toggle('is-open', open);
        menuButton?.setAttribute('aria-expanded', String(open));
        document.body.style.overflow = open ? 'hidden' : '';
    };

    menuButton?.addEventListener('click', () => setSidebar(!sidebar?.classList.contains('is-open')));
    scrim?.addEventListener('click', () => setSidebar(false));

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') setSidebar(false);
    });

    const revealItems = document.querySelectorAll(
        '.page-header, .hero-panel, .stat-card, .dashboard-card, .surface-card, .alert'
    );

    revealItems.forEach((item, index) => {
        item.dataset.reveal = '';
        item.style.setProperty('--reveal-delay', `${Math.min(index * 65, 420)}ms`);
    });

    if ('IntersectionObserver' in window) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.08 });

        revealItems.forEach((item) => observer.observe(item));
    } else {
        revealItems.forEach((item) => item.classList.add('is-visible'));
    }

    document.querySelectorAll('[data-count]').forEach((element) => {
        const target = Number(element.dataset.count || 0);
        const prefix = element.dataset.prefix || '';
        const suffix = element.dataset.suffix || '';
        const formatter = new Intl.NumberFormat('en-PK', { maximumFractionDigits: 0 });
        const duration = 950;
        const startedAt = performance.now();

        const tick = (now) => {
            const progress = Math.min((now - startedAt) / duration, 1);
            const eased = 1 - Math.pow(1 - progress, 4);
            element.textContent = `${prefix}${formatter.format(Math.round(target * eased))}${suffix}`;
            if (progress < 1) requestAnimationFrame(tick);
        };

        requestAnimationFrame(tick);
    });

    document.querySelectorAll('.btn').forEach((button) => {
        button.addEventListener('pointerdown', (event) => {
            const bounds = button.getBoundingClientRect();
            const size = Math.max(bounds.width, bounds.height);
            const ripple = document.createElement('span');
            ripple.className = 'ripple';
            ripple.style.width = ripple.style.height = `${size}px`;
            ripple.style.left = `${event.clientX - bounds.left - size / 2}px`;
            ripple.style.top = `${event.clientY - bounds.top - size / 2}px`;
            button.querySelector('.ripple')?.remove();
            button.appendChild(ripple);
            ripple.addEventListener('animationend', () => ripple.remove());
        });
    });

    document.querySelectorAll('form').forEach((form) => {
        form.addEventListener('submit', () => {
            const submit = form.querySelector('button[type="submit"]');
            if (!submit || submit.dataset.noLoading !== undefined) return;
            submit.classList.add('is-loading');
        });
    });
});
