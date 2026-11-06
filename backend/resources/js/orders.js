/*
 | Orders: list/detail modals (call history, comment, activity log) and the POS create/edit screen.
 */

export function orderModals(routes) {
    return {
        modal: null, // 'calls' | 'comment' | 'activity'
        order: null, // {id, code, note}
        calls: null,
        callNote: '',
        comment: '',
        activities: [],
        busy: false,

        /** Clicking a phone number logs a call right away (the tel: link still opens the dialer). */
        async logCall(order, badge) {
            if (badge) badge.textContent = Number(badge.textContent || 0) + 1; // optimistic
            try {
                const r = await window.api(routes.logCall, { method: 'POST', body: { order_id: order.id } });
                if (badge) badge.textContent = r.total_calls;
            } catch (e) {
                window.toast(e.message, 'error');
            }
        },
        async openCalls(order) {
            this.order = order;
            this.modal = 'calls';
            this.calls = null;
            this.calls = await window.api(routes.calls.replace('__ID__', order.id));
        },
        async addCallNote() {
            if (!this.callNote.trim()) return;
            this.busy = true;
            try {
                const r = await window.api(routes.callNote, { method: 'POST', body: { order_id: this.order.id, note: this.callNote } });
                window.toast(r.message);
                this.callNote = '';
                this.calls = await window.api(routes.calls.replace('__ID__', this.order.id));
            } catch (e) {
                window.toast(e.message, 'error');
            } finally {
                this.busy = false;
            }
        },
        openComment(order) {
            this.order = order;
            this.comment = order.note || '';
            this.modal = 'comment';
        },
        async saveComment() {
            this.busy = true;
            try {
                const r = await window.api(routes.note, { method: 'POST', body: { order_id: this.order.id, admin_note: this.comment } });
                window.toast(r.message);
                this.order.note = this.comment;
                window.dispatchEvent(new CustomEvent('order-note', { detail: { id: this.order.id, note: this.comment } }));
                this.modal = null;
            } catch (e) {
                window.toast(e.message, 'error');
            } finally {
                this.busy = false;
            }
        },
        async openActivity(order) {
            this.order = order;
            this.modal = 'activity';
            this.activities = (await window.api(routes.activities.replace('__ID__', order.id))).data;
        },
        // ---------- courier + fraud check (phase 4) ----------
        badges: {}, // phone => {level, rate, total, label}
        fraud: null,
        fraudBusy: false,
        async loadBadges(phones) {
            if (!routes.fraudBatch || !phones.length) return;
            try {
                this.badges = (await window.api(routes.fraudBatch, { method: 'POST', body: { phones } })).data;
            } catch (e) {
                /* badges are optional */
            }
        },
        async openFraud(order, live = false) {
            this.order = order;
            this.modal = 'fraud';
            this.fraudBusy = true;
            try {
                const q = new URLSearchParams({ phone: order.phone, force_refresh: live ? 1 : 0 });
                this.fraud = (await window.api(`${routes.fraud}?${q}`)).data;
                if (live) this.badges[order.phone] = this.fraud.overall;
            } catch (e) {
                window.toast(e.message, 'error');
            } finally {
                this.fraudBusy = false;
            }
        },
        async sendCourier(ids, courier = null) {
            if (!ids.length) return;
            try {
                const r = await window.api(routes.sendCourier, { method: 'POST', body: { ids, courier } });
                window.toast(r.message, r.failed?.length ? 'warning' : 'success');
                setTimeout(() => window.location.reload(), 1200);
            } catch (e) {
                window.toast(e.message, 'error');
            }
        },

        async setStatus(url, orderId, status, el) {
            const previous = el?.dataset.current;
            try {
                const r = await window.api(url, { method: 'POST', body: { order_id: orderId, status } });
                window.toast(r.message);
                if (el) el.dataset.current = status;
            } catch (e) {
                window.toast(e.message, 'error');
                if (el && previous) el.value = previous;
            }
        },
    };
}

export function posScreen(config) {
    return {
        search: '',
        category: '',
        brand: '',
        products: [],
        page: 1,
        lastPage: 1,
        loading: false,
        cart: config.cart || [], // [{product_id, name, image, variant, price, quantity, max}]
        chooser: null, // product waiting for a variant choice
        customer: config.customer || { name: '', phone: '', email: '', address: '', state_id: '', city_id: '' },
        customerQuery: '',
        customerResults: [],
        states: config.states || [],
        discount: config.discount || 0,
        discountType: config.discountType || 'flat',
        shippingOverride: config.shippingOverride ?? null,
        advance: config.advance || 0,
        advanceMethod: config.advanceMethod || 'cod',
        senderNo: config.senderNo || '',
        trxId: config.trxId || '',
        note: config.note || '',
        saving: false,

        init() {
            this.load();
            this.$watch('customer.state_id', () => {
                if (!this.cities.some((c) => String(c.id) === String(this.customer.city_id))) this.customer.city_id = '';
            });
        },
        async load(append = false) {
            this.loading = true;
            const q = new URLSearchParams({ search: this.search, category: this.category, brand: this.brand, page: this.page });
            try {
                const r = await window.api(`${config.routes.products}?${q}`);
                this.products = append ? [...this.products, ...r.data] : r.data;
                this.lastPage = r.last_page;
            } finally {
                this.loading = false;
            }
        },
        reload() {
            this.page = 1;
            this.load();
        },
        more() {
            this.page++;
            this.load(true);
        },
        pick(p) {
            if (p.variant_product) {
                this.chooser = p;
                return;
            }
            this.add(p, p.stocks[0]);
        },
        add(p, stock) {
            const variant = stock?.variant || '';
            const line = this.cart.find((l) => l.product_id === p.id && l.variant === variant);
            if (line) {
                line.quantity++;
            } else {
                this.cart.push({ product_id: p.id, name: p.name, image: p.image, variant, price: stock?.price ?? p.price, quantity: 1, max: stock?.qty ?? p.stock });
            }
            this.chooser = null;
        },
        async findCustomers() {
            if (this.customerQuery.trim().length < 2) {
                this.customerResults = [];
                return;
            }
            this.customerResults = (await window.api(`${config.routes.customers}?q=${encodeURIComponent(this.customerQuery)}`)).data;
        },
        useCustomer(c) {
            this.customer = { name: c.name, phone: c.phone, email: c.email || '', address: c.address || '', state_id: c.state_id || '', city_id: c.city_id || '' };
            this.$nextTick(() => (this.customer.city_id = c.city_id || ''));
            this.customerResults = [];
            this.customerQuery = '';
        },
        get cities() {
            return this.states.find((s) => String(s.id) === String(this.customer.state_id))?.cities || [];
        },
        get subtotal() {
            return this.cart.reduce((sum, l) => sum + Number(l.price || 0) * Number(l.quantity || 0), 0);
        },
        get areaCost() {
            const city = this.cities.find((c) => String(c.id) === String(this.customer.city_id));
            if (config.freeShippingMin > 0 && this.subtotal >= config.freeShippingMin) return 0;
            return city ? Number(city.cost) : 0;
        },
        get shipping() {
            return this.shippingOverride !== null && this.shippingOverride !== '' ? Number(this.shippingOverride) : this.areaCost;
        },
        get discountAmount() {
            const d = Number(this.discount || 0);
            return Math.min(this.discountType === 'percent' ? (this.subtotal * Math.min(d, 100)) / 100 : d, this.subtotal);
        },
        get total() {
            return Math.max(0, this.subtotal + this.shipping - this.discountAmount);
        },
        get due() {
            return Math.max(0, this.total - Number(this.advance || 0));
        },
        money(n) {
            return '৳' + Number(n || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },
        async submit() {
            this.saving = true;
            try {
                const r = await window.api(config.routes.save, {
                    method: config.method,
                    body: {
                        ...this.customer,
                        items: this.cart.map((l) => ({ product_id: l.product_id, variant: l.variant, quantity: l.quantity, price: l.price })),
                        discount: this.discount,
                        discount_type: this.discountType,
                        shipping_cost: this.shippingOverride === '' ? null : this.shippingOverride,
                        advance_amount: this.advance,
                        advance_method: this.advanceMethod,
                        sender_no: this.senderNo,
                        transaction_id: this.trxId,
                        customer_note: this.note,
                    },
                });
                window.toast(r.message);
                window.location = r.redirect;
            } catch (e) {
                window.toast(e.message, 'error');
            } finally {
                this.saving = false;
            }
        },
    };
}
