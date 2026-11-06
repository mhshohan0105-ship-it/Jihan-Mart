/*
 | Form widgets used on catalog pages:
 |  switchToggle  inline on/off switch that saves by AJAX (list pages)
 |  tomSelect     searchable select / tag input (Tom Select)
 |  datePicker    single date or date range (flatpickr)
 |  richText      Quill editor that writes HTML into a hidden textarea
 */
import TomSelect from 'tom-select';
import 'tom-select/dist/css/tom-select.css';
import flatpickr from 'flatpickr';
import 'flatpickr/dist/flatpickr.min.css';
import Quill from 'quill';
import 'quill/dist/quill.snow.css';

export function switchToggle({ url, field = null, value = false }) {
    return {
        on: !!value,
        busy: false,
        async flip() {
            if (this.busy) return;
            const next = !this.on;
            this.on = next; // optimistic
            this.busy = true;
            try {
                const res = await window.api(url, { method: 'POST', body: { ...(field ? { field } : {}), value: next ? 1 : 0 } });
                window.toast(res.message || 'Updated');
            } catch (e) {
                this.on = !next;
                window.toast(e.message, 'error');
            } finally {
                this.busy = false;
            }
        },
    };
}

export function tomSelect(options = {}) {
    return {
        ts: null,
        init() {
            const tags = this.$el.dataset.tags !== undefined;
            this.ts = new TomSelect(this.$el, {
                plugins: this.$el.multiple || tags ? ['remove_button'] : [],
                create: tags,
                createOnBlur: tags,
                persist: false,
                maxOptions: 500,
                allowEmptyOption: true,
                ...(tags ? { delimiter: ',' } : {}),
                ...options,
                onChange: () => this.$el.dispatchEvent(new Event('change', { bubbles: true })),
            });
        },
        destroy() {
            this.ts?.destroy();
        },
    };
}

export function datePicker(options = {}) {
    return {
        fp: null,
        init() {
            this.fp = flatpickr(this.$el, {
                enableTime: this.$el.dataset.time !== undefined,
                dateFormat: this.$el.dataset.time !== undefined ? 'Y-m-d H:i' : 'Y-m-d',
                altInput: true,
                altFormat: this.$el.dataset.time !== undefined ? 'd-m-Y h:i K' : 'd-m-Y',
                allowInput: true,
                ...options,
            });
        },
        destroy() {
            this.fp?.destroy();
        },
    };
}

export function richText() {
    return {
        init() {
            const textarea = this.$refs.input;
            const editor = this.$refs.editor;
            const quill = new Quill(editor, {
                theme: 'snow',
                modules: {
                    toolbar: [
                        [{ header: [2, 3, 4, false] }],
                        ['bold', 'italic', 'underline', 'strike'],
                        [{ color: [] }, { background: [] }],
                        [{ list: 'ordered' }, { list: 'bullet' }],
                        [{ align: [] }],
                        ['link', 'image', 'video', 'blockquote', 'code-block'],
                        ['clean'],
                    ],
                },
            });
            if (textarea.value) quill.clipboard.dangerouslyPasteHTML(textarea.value);
            quill.on('text-change', () => {
                textarea.value = quill.getText().trim() ? quill.root.innerHTML : '';
            });
        },
    };
}

let repeaterSeq = 0;

/**
 * Repeating rows (nav menu, sliders, banners...). Each row is an object; image fields keep an upload id
 * plus `<key>_url` for the preview. Rows are posted as name[i][key].
 */
export function repeater({ rows = [], blank = {}, max = 0 } = {}) {
    return {
        uid: `rep-${++repeaterSeq}`,
        rows: rows.map((r) => ({ ...r })),
        max,
        init() {
            window.addEventListener('media-selected', (e) => {
                const [uid, index, key] = String(e.detail.target).split('|');
                if (uid !== this.uid || !this.rows[index]) return;
                const file = e.detail.files[0];
                this.rows[index][key] = file ? file.id : '';
                this.rows[index][`${key}_url`] = file ? file.url : '';
            });
        },
        add() {
            if (!this.max || this.rows.length < this.max) this.rows.push({ ...blank });
        },
        remove(i) {
            this.rows.splice(i, 1);
        },
        pick(i, key) {
            window.dispatchEvent(new CustomEvent('open-media-picker', { detail: { target: `${this.uid}|${i}|${key}`, multiple: false, type: 'image', selected: [] } }));
        },
    };
}

/**
 * Action menu that floats above everything (position: fixed), so a scrolling table
 * cannot clip it. Opens below the button, or above it when there is no room below.
 */
export function floatingMenu() {
    return {
        open: false,
        style: 'visibility:hidden',
        init() {
            const close = () => this.close();
            window.addEventListener('scroll', close, true);
            window.addEventListener('resize', close);
        },
        toggle() {
            this.open ? this.close() : this.show();
        },
        show() {
            this.style = 'visibility:hidden';
            this.open = true;
            this.$nextTick(() => {
                const r = this.$refs.button.getBoundingClientRect();
                const m = this.$refs.menu;
                const h = m.offsetHeight;
                const w = m.offsetWidth;
                const top = window.innerHeight - r.bottom < h + 8 && r.top > h + 8 ? r.top - h - 4 : r.bottom + 4;
                const left = Math.max(8, Math.min(r.right - w, window.innerWidth - w - 8));
                this.style = `top:${top}px;left:${left}px`;
            });
        },
        close() {
            this.open = false;
        },
    };
}
