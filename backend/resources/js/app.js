import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';
import { mediaPicker, uploadField, uploader } from './media';
import { datePicker, floatingMenu, repeater, richText, switchToggle, tomSelect } from './widgets';
import { productForm } from './product-form';
import { orderModals, posScreen } from './orders';
import { chart } from './charts';

const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content;

/** fetch wrapper: sends CSRF + JSON, throws with the server's message on error. */
window.api = async (url, { method = 'GET', body, headers = {} } = {}) => {
    const isForm = body instanceof FormData;
    const res = await fetch(url, {
        method,
        headers: {
            Accept: 'application/json',
            'X-CSRF-TOKEN': csrf(),
            'X-Requested-With': 'XMLHttpRequest',
            ...(body && !isForm ? { 'Content-Type': 'application/json' } : {}),
            ...headers,
        },
        body: body && !isForm ? JSON.stringify(body) : body,
    });
    const data = await res.json().catch(() => ({}));
    if (!res.ok) {
        const first = data.errors ? Object.values(data.errors)[0]?.[0] : null;
        throw new Error(first || data.message || `Request failed (${res.status})`);
    }
    return data;
};

document.addEventListener('alpine:init', () => {
    // toast('Saved') / toast('Oops', 'error')
    Alpine.store('toasts', {
        items: [],
        push(message, type = 'success') {
            const id = Date.now() + Math.random();
            this.items.push({ id, message, type });
            setTimeout(() => this.remove(id), 3500);
        },
        remove(id) {
            this.items = this.items.filter((t) => t.id !== id);
        },
    });
    window.toast = (message, type) => Alpine.store('toasts').push(message, type);

    // shared "Delete Confirmation" modal: confirmDelete(url, { onDone })
    Alpine.store('confirm', {
        open: false,
        url: null,
        title: '',
        message: '',
        busy: false,
        onDone: null,
        ask(url, { title, message, onDone } = {}) {
            Object.assign(this, { open: true, url, title: title || null, message: message || null, onDone: onDone || null, busy: false });
        },
        async run() {
            this.busy = true;
            try {
                const data = await window.api(this.url, { method: 'DELETE' });
                window.toast(data.message || 'Deleted');
                this.open = false;
                this.onDone ? this.onDone(data) : window.location.reload();
            } catch (e) {
                window.toast(e.message, 'error');
            } finally {
                this.busy = false;
            }
        },
    });
    window.confirmDelete = (url, opts) => Alpine.store('confirm').ask(url, opts);

    Alpine.store('sidebar', {
        collapsed: localStorage.getItem('jm-sidebar') === '1',
        mobileOpen: false,
        toggle() {
            if (window.innerWidth < 1024) {
                this.mobileOpen = !this.mobileOpen;
                return;
            }
            this.collapsed = !this.collapsed;
            try {
                localStorage.setItem('jm-sidebar', this.collapsed ? '1' : '0');
            } catch (e) {
                /* storage unavailable */
            }
        },
    });

    Alpine.data('mediaPicker', mediaPicker);
    Alpine.data('uploadField', uploadField);
    Alpine.data('uploader', uploader);
    Alpine.data('repeater', repeater);
    Alpine.data('floatingMenu', floatingMenu);
    Alpine.data('switchToggle', switchToggle);
    Alpine.data('tomSelect', tomSelect);
    Alpine.data('datePicker', datePicker);
    Alpine.data('richText', richText);
    Alpine.data('productForm', productForm);
    Alpine.data('orderModals', orderModals);
    Alpine.data('posScreen', posScreen);
    Alpine.data('chart', chart);
});

// flash messages rendered by Blade
document.addEventListener('alpine:initialized', () => {
    (window.__flash || []).forEach(([type, msg]) => window.toast(msg, type));
});

Alpine.plugin(collapse);
window.Alpine = Alpine;
Alpine.start();
