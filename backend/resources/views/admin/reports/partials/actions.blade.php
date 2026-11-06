@can('export_reports')
    <a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}" class="btn-light no-print"><x-icon name="download" class="size-4" /> {{ __('Export to Excel / CSV') }}</a>
@endcan
<button type="button" onclick="window.print()" class="btn-light no-print">{{ __('Print Report') }}</button>
