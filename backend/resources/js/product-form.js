import TomSelect from 'tom-select';

/*
 | Add / Edit product page.
 | Variants: chosen colours × chosen attribute values → one row per combination ("Red-XL")
 | with its own price, SKU, quantity and photo. Same order as ProductService::combinations().
 */
export function productForm(initial) {
    return {
        unitPrice: initial.unitPrice ?? 0,
        colorsOn: (initial.colors || []).length > 0,
        colors: (initial.colors || []).map(String),
        colorNames: initial.colorNames || {}, // id => name
        attributes: initial.attributes || [], // [{id, name, values: []}]
        choices: (initial.choices || []).map((c) => ({ ...c, attribute_id: Number(c.attribute_id), values: [...(c.values || [])] })),
        stocks: initial.stocks || {}, // variant => {price, sku, qty, image_upload_id}
        tiers: initial.tiers?.length ? initial.tiers : [],
        policy: initial.policy || 'full_cod',
        discountType: initial.discountType || 'flat',

        init() {
            // variant prices that were never edited follow the unit price
            this.$watch('unitPrice', (now, before) => {
                Object.values(this.stocks).forEach((s) => {
                    if (Number(s.price) === Number(before) || s.price === '' || s.price === null) s.price = now;
                });
            });
        },

        get variants() {
            const lists = [];
            if (this.colorsOn && this.colors.length) {
                lists.push(this.colors.map((id) => this.colorNames[id]).filter(Boolean));
            }
            this.choices.forEach((c) => c.values.length && lists.push(c.values));
            if (!lists.length) return [];
            return lists.reduce((acc, list) => acc.flatMap((prefix) => list.map((v) => (prefix ? `${prefix}-${v}` : v))), ['']);
        },

        stock(variant) {
            if (!this.stocks[variant]) {
                this.stocks[variant] = { price: this.unitPrice, sku: '', qty: 0, image_upload_id: '' };
            }
            return this.stocks[variant];
        },

        attributeName(id) {
            return this.attributes.find((a) => a.id === Number(id))?.name || '';
        },

        /** Attribute multi select changed: add/remove value pickers. */
        syncChoices(selectedIds) {
            const ids = selectedIds.map(Number);
            this.choices = this.choices.filter((c) => ids.includes(c.attribute_id));
            ids.forEach((id) => {
                if (!this.choices.some((c) => c.attribute_id === id)) this.choices.push({ attribute_id: id, values: [] });
            });
        },

        /** Tom Select tag picker for one attribute's values (existing values + type new ones). */
        valuePicker(el, choice) {
            const known = this.attributes.find((a) => a.id === choice.attribute_id)?.values || [];
            const options = [...new Set([...known, ...choice.values])].map((v) => ({ value: v, text: v }));
            new TomSelect(el, {
                options,
                items: choice.values,
                create: true,
                persist: false,
                plugins: ['remove_button'],
                onChange: (value) => {
                    choice.values = Array.isArray(value) ? value : String(value).split(',').filter(Boolean);
                },
            });
        },

        addTier() {
            this.tiers.push({ min_qty: '', price: '' });
        },
    };
}
