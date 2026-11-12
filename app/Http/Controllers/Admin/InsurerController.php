<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Insurers\InsurerManager;
use App\Models\Insurer;
use App\Support\Audit;
use App\Support\InsurerHealth;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Insurance providers: status, health from api_logs, on/off switch and connection test. */
class InsurerController extends Controller
{
    public function index()
    {
        return view('admin.insurers', ['insurers' => InsurerHealth::all(), 'website' => Insurer::query()->ordered()->get()]);
    }

    /** Website logo strip: Live / Coming soon and display order. Does not switch quoting on or off. */
    public function display(Request $request, string $code)
    {
        $insurer = Insurer::where('code', $code)->firstOrFail();
        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(Insurer::STATUSES))],
            'sort' => ['required', 'integer', 'min:0', 'max:999'],
        ]);
        $before = $insurer->only(['status', 'sort']);
        $insurer->update($data);
        Audit::log('insurer.website_display', $insurer, $before, $insurer->only(['status', 'sort']));

        $note = $insurer->isLive() && ! array_key_exists($code, config('insurers.providers', []))
            ? ' Note: it has no online connection yet, so it is not counted in "Quotes from N insurers".'
            : '';

        return back()->with('toast', "{$insurer->name}: ".Insurer::STATUSES[$insurer->status]." · position {$insurer->sort}.{$note}");
    }

    public function toggle(Request $request, string $code, InsurerManager $manager)
    {
        abort_unless(array_key_exists($code, config('insurers.providers', [])), 404);
        $on = $request->boolean('enabled');
        if ($on && ! $manager->configured($code)) {
            return back()->with('toast', config("insurers.providers.{$code}.name").' has no credentials in .env — it cannot be enabled here.');
        }

        $insurer = Insurer::firstOrNew(['code' => $code], ['name' => config("insurers.providers.{$code}.name"), 'environment' => 'uat', 'logo' => "images/insurers/{$code}.png"]);
        $before = $insurer->exists ? (bool) $insurer->enabled : true;
        $insurer->enabled = $on;
        $insurer->save();
        Audit::log('insurer.'.($on ? 'enabled' : 'disabled'), $insurer, ['enabled' => $before], ['enabled' => $on]);

        return back()->with('toast', $insurer->name.($on ? ' enabled — it joins new quote requests.' : ' disabled — new quote requests skip it.'));
    }

    public function test(string $code, InsurerManager $manager)
    {
        abort_unless(array_key_exists($code, config('insurers.providers', [])), 404);
        $res = $manager->get($code)->ping();
        Audit::log('insurer.test_connection', Insurer::where('code', $code)->first(), after: ['ok' => $res['ok']]);

        return back()->with('toast', ($res['ok'] ? '✔ ' : '✖ ').config("insurers.providers.{$code}.name").': '.$res['message']);
    }
}
