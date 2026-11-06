@php($dots = \App\Models\OrderActivity::COLORS)
<ol class="relative space-y-4 border-l border-slate-200 pl-5 text-sm">
    <template x-for="a in activities">
        <li class="relative">
            <span class="absolute -left-[27px] top-1 size-3 rounded-full ring-4 ring-white" :class="@js($dots)[a.type] || 'bg-slate-400'"></span>
            <p class="font-semibold" x-text="a.title"></p>
            <p x-show="a.old || a.new"><span class="text-slate-400 line-through" x-text="a.old"></span> <span x-show="a.old && a.new">→</span> <span class="font-medium" x-text="a.new"></span></p>
            <p class="text-slate-600" x-show="a.note" x-text="a.note"></p>
            <p class="text-xs text-slate-400" x-text="a.by + ' · ' + a.at"></p>
        </li>
    </template>
    <li x-show="!activities.length" class="text-slate-500">{{ __('No Activity Recorded Yet') }}</li>
</ol>
