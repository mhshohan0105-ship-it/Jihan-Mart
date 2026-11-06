/*
 | Media library pieces:
 |  uploader     drag and drop multi upload with per file progress (one request per file)
 |  mediaPicker  the single global "choose files" modal
 |  uploadField  a form field: hidden input with upload ids + previews + Browse button
 */

const routes = () => window.__mediaRoutes;

function xhrUpload(url, file, onProgress) {
    return new Promise((resolve, reject) => {
        const xhr = new XMLHttpRequest();
        const body = new FormData();
        body.append('file', file);
        xhr.open('POST', url);
        xhr.setRequestHeader('Accept', 'application/json');
        xhr.setRequestHeader('X-CSRF-TOKEN', document.querySelector('meta[name="csrf-token"]').content);
        xhr.upload.onprogress = (e) => e.lengthComputable && onProgress(Math.round((e.loaded / e.total) * 100));
        xhr.onload = () => {
            let data = {};
            try {
                data = JSON.parse(xhr.responseText);
            } catch (e) {
                /* not json */
            }
            if (xhr.status >= 200 && xhr.status < 300) return resolve(data.data);
            const first = data.errors ? Object.values(data.errors)[0]?.[0] : null;
            reject(new Error(first || data.message || `Upload failed (${xhr.status})`));
        };
        xhr.onerror = () => reject(new Error('Network error'));
        xhr.send(body);
    });
}

export function uploader({ onUploaded } = {}) {
    return {
        queue: [],
        dragging: false,
        add(fileList) {
            [...fileList].forEach((file) => this.queue.push({ file, name: file.name, progress: 0, status: 'waiting', error: null }));
            this.run();
        },
        async run() {
            for (const item of this.queue) {
                if (item.status !== 'waiting') continue;
                item.status = 'uploading';
                try {
                    const upload = await xhrUpload(routes().store, item.file, (p) => (item.progress = p));
                    item.status = 'done';
                    item.progress = 100;
                    onUploaded ? onUploaded(upload) : this.$dispatch('media-uploaded', upload);
                } catch (e) {
                    item.status = 'error';
                    item.error = e.message;
                }
            }
        },
        get busy() {
            return this.queue.some((i) => i.status === 'uploading' || i.status === 'waiting');
        },
    };
}

export function mediaPicker() {
    return {
        open: false,
        tab: 'library',
        target: null,
        multiple: false,
        type: 'image',
        files: [],
        selected: [],
        search: '',
        sort: 'newest',
        page: 1,
        lastPage: 1,
        loading: false,
        init() {
            window.addEventListener('open-media-picker', (e) => this.show(e.detail));
        },
        show({ target, multiple = false, type = 'image', selected = [] }) {
            Object.assign(this, { open: true, tab: 'library', target, multiple, type, selected: [...selected], page: 1 });
            this.load();
        },
        async load(append = false) {
            this.loading = true;
            const q = new URLSearchParams({ search: this.search, sort: this.sort, page: this.page, ...(this.type ? { type: this.type } : {}) });
            try {
                const res = await window.api(`${routes().json}?${q}`);
                this.files = append ? [...this.files, ...res.data] : res.data;
                this.lastPage = res.last_page;
            } catch (e) {
                window.toast(e.message, 'error');
            } finally {
                this.loading = false;
            }
        },
        more() {
            this.page++;
            this.load(true);
        },
        isSelected(file) {
            return this.selected.some((f) => f.id === file.id);
        },
        toggle(file) {
            if (this.isSelected(file)) {
                this.selected = this.selected.filter((f) => f.id !== file.id);
            } else {
                this.selected = this.multiple ? [...this.selected, file] : [file];
            }
        },
        uploaded(file) {
            this.files = [file, ...this.files.filter((f) => f.id !== file.id)];
            this.toggle(file);
        },
        confirm() {
            window.dispatchEvent(new CustomEvent('media-selected', { detail: { target: this.target, files: this.selected } }));
            this.open = false;
        },
    };
}

let fieldSeq = 0;

export function uploadField({ value = '', multiple = false, type = 'image' } = {}) {
    return {
        id: `upload-field-${++fieldSeq}`,
        files: [],
        multiple,
        type,
        get value() {
            return this.files.map((f) => f.id).join(',');
        },
        async init() {
            window.addEventListener('media-selected', (e) => {
                if (e.detail.target === this.id) this.files = e.detail.files;
            });
            if (value) {
                try {
                    const res = await window.api(`${routes().byIds}?ids=${encodeURIComponent(value)}`);
                    this.files = res.data;
                } catch (e) {
                    /* previews only */
                }
            }
        },
        browse() {
            window.dispatchEvent(new CustomEvent('open-media-picker', { detail: { target: this.id, multiple: this.multiple, type: this.type, selected: this.files } }));
        },
        remove(file) {
            this.files = this.files.filter((f) => f.id !== file.id);
        },
    };
}
