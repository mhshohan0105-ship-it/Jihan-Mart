<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BusinessSetting;
use App\Models\Tax;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

/** Setup & Configurations pages. Plain values save through business-settings/update, secrets through env-key-update. */
class SetupController extends Controller
{
    /** Features activation: group => [setting => [label, default]]. */
    public const FEATURES = [
        'System' => [
            'force_https' => ['HTTPS Activation', false],
            'maintenance_mode' => ['Maintenance Mode', false],
        ],
        'Invoice Setup' => [
            'invoice_show_sku' => ['Show SKU on invoice', true],
            'invoice_show_image' => ['Show product image on invoice', true],
        ],
        'Business Related' => [
            'coupon_system' => ['Coupon System', true],
            'review_auto_approve' => ['Auto approve customer reviews', false],
        ],
        'Payment Related' => [
            'cash_payment' => ['Cash on Delivery', true],
            'bkash_payment' => ['bKash (send money + TrxID)', true],
            'nagad_payment' => ['Nagad (send money + TrxID)', true],
            'rocket_payment' => ['Rocket (send money + TrxID)', true],
        ],
    ];

    /** Pages that are only a settings form: slug => [view, permission]. */
    public const PAGES = [
        'pos-configuration' => ['pos', 'manage_general_settings'],
        'smtp-settings' => ['smtp', 'manage_smtp'],
        'payment-method' => ['payment', 'manage_payment_methods'],
        'order-configuration' => ['order', 'manage_order_configuration'],
        'file-system' => ['file-system', 'manage_file_system'],
        'social-login' => ['social-login', 'manage_social_login'],
        'facebook-chat' => ['facebook-chat', 'manage_facebook'],
        'facebook-comment' => ['facebook-comment', 'manage_facebook'],
        'google-analytics' => ['analytics', 'manage_google'],
        'google-recaptcha' => ['recaptcha', 'manage_google'],
        'google-map' => ['google-map', 'manage_google'],
    ];

    public function page(Request $request, string $page): View
    {
        abort_unless(isset(self::PAGES[$page]), 404);
        [$view, $permission] = self::PAGES[$page];
        abort_unless($request->user()->can($permission), 403);

        return view('admin.setup.'.$view);
    }

    public function features(): View
    {
        return view('admin.setup.features', ['groups' => self::FEATURES]);
    }

    public function toggleFeature(Request $request): JsonResponse
    {
        $keys = collect(self::FEATURES)->flatMap(fn ($g) => array_keys($g))->all();
        $data = $request->validate(['field' => ['required', Rule::in($keys)], 'value' => ['required', 'boolean']]);
        BusinessSetting::put($data['field'], $request->boolean('value') ? '1' : '0');

        return response()->json(['success' => true, 'message' => __('Settings updated successfully')]);
    }

    public function testEmail(Request $request): RedirectResponse
    {
        $data = $request->validate(['email' => ['required', 'email']]);
        try {
            Mail::raw(__('This is a test email from :site. Your SMTP settings work.', ['site' => get_setting('site_name', 'Jihan Mart')]),
                fn ($m) => $m->to($data['email'])->subject(__('SMTP test email')));
        } catch (Throwable $e) {
            return back()->with('error', __('Email could not be sent: :msg', ['msg' => $e->getMessage()]));
        }

        return back()->with('success', config('mail.default') === 'log'
            ? __('Mailer is "log": the email was written to the log file, not sent.')
            : __('Test email sent to :email', ['email' => $data['email']]));
    }

    // ---------------- Languages (English + Bangla, translations in lang/*.json) ----------------

    public const LANGUAGES = ['en' => 'English', 'bn' => 'বাংলা (Bangla)'];

    public function languages(): View
    {
        return view('admin.setup.languages', [
            'languages' => self::LANGUAGES,
            'counts' => ['bn' => count($this->translations('bn'))],
        ]);
    }

    public function translations(string $code): array
    {
        $path = lang_path($code.'.json');

        return File::exists($path) ? (json_decode(File::get($path), true) ?: []) : [];
    }

    public function translationEditor(Request $request, string $code): View
    {
        abort_unless($code === 'bn', 404); // English is the source language
        $all = $this->translations($code);
        $search = trim((string) $request->input('search'));
        if ($search !== '') {
            $all = array_filter($all, fn ($v, $k) => stripos($k, $search) !== false || stripos($v, $search) !== false, ARRAY_FILTER_USE_BOTH);
        }
        ksort($all);
        // 100 rows per page keeps each save well under PHP's max_input_vars
        $page = max(1, $request->integer('page', 1));
        $rows = new LengthAwarePaginator(array_slice($all, ($page - 1) * 100, 100, true), count($all), 100, $page, ['path' => $request->url(), 'query' => $request->query()]);

        return view('admin.setup.translations', ['code' => $code, 'rows' => $rows, 'search' => $search]);
    }

    public function saveTranslations(Request $request, string $code): RedirectResponse
    {
        abort_unless($code === 'bn', 404);
        $data = $request->validate(['keys' => ['array'], 'keys.*' => ['nullable', 'string', 'max:1000'], 'values' => ['array'], 'values.*' => ['nullable', 'string', 'max:2000']]);
        $all = $this->translations($code);
        foreach ($data['keys'] ?? [] as $i => $key) {
            if ($key === null || $key === '') {
                continue;
            }
            $value = $data['values'][$i] ?? '';
            if ($value === '') {
                unset($all[$key]); // empty value falls back to English
            } else {
                $all[$key] = $value;
            }
        }
        ksort($all);
        File::put(lang_path($code.'.json'), json_encode($all, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).PHP_EOL);

        return back()->with('success', __('Translations updated for :lang', ['lang' => self::LANGUAGES[$code]]));
    }

    // ---------------- Vat & TAX ----------------

    public function taxes(Request $request): View
    {
        return view('admin.setup.taxes', [
            'taxes' => Tax::query()->withCount(['products' => fn ($q) => $q->where('tax', '>', 0)])->orderBy('id')->get(),
            'editing' => $request->filled('edit') ? Tax::query()->find($request->integer('edit')) : null,
        ]);
    }

    public function saveTax(Request $request, ?Tax $tax = null): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:100']]);
        $tax ??= new Tax(['status' => true]);
        $tax->fill($data)->save();

        return redirect()->route('admin.taxes.index')->with('success', __('Tax has been saved successfully'));
    }

    public function destroyTax(Tax $tax): JsonResponse
    {
        $tax->delete();

        return response()->json(['success' => true, 'message' => __('Tax has been deleted successfully')]);
    }
}
