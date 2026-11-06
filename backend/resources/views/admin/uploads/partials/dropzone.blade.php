{{-- used inside x-data="uploader(...)" --}}
<label class="flex cursor-pointer flex-col items-center justify-center rounded-2xl border-2 border-dashed px-6 py-12 text-center transition"
       :class="dragging ? 'border-brand-500 bg-brand-50' : 'border-slate-200 hover:border-brand-500/60 hover:bg-slate-50'"
       @dragover.prevent="dragging = true" @dragleave.prevent="dragging = false" @drop.prevent="dragging = false; add($event.dataTransfer.files)">
    <span class="grid size-14 place-items-center rounded-2xl bg-brand-50 text-brand-500"><x-icon name="upload" class="size-7" /></span>
    <span class="mt-4 font-semibold">{{ __('Drop files here or click to browse') }}</span>
    <span class="mt-1 text-xs text-slate-500">{{ __('Images are converted to WebP automatically. Max 20 MB per file.') }}</span>
    <input type="file" multiple class="sr-only" @change="add($event.target.files); $event.target.value = ''">
</label>
<ul class="mt-4 space-y-2" x-show="queue.length">
    <template x-for="(item, i) in queue" :key="i">
        <li class="rounded-xl border border-slate-100 px-4 py-3 text-sm">
            <div class="flex items-center justify-between gap-3">
                <span class="truncate" x-text="item.name"></span>
                <span class="shrink-0 text-xs font-semibold"
                      :class="{ 'text-emerald-600': item.status === 'done', 'text-red-600': item.status === 'error', 'text-slate-500': item.status === 'waiting' || item.status === 'uploading' }"
                      x-text="item.status === 'error' ? item.error : (item.status === 'done' ? @js(__('Done')) : item.progress + '%')"></span>
            </div>
            <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-slate-100">
                <div class="h-full rounded-full transition-all" :class="item.status === 'error' ? 'bg-red-500' : 'bg-gradient-to-r from-brand-500 to-accent-500'" :style="`width:${item.progress}%`"></div>
            </div>
        </li>
    </template>
</ul>
