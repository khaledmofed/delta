<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Insurers\InsurerManager;
use App\Models\Insurer;
use App\Support\Audit;
use App\Support\InsurerHealth;
use Illuminate\Http\Request;

/** Insurance providers: status, health from api_logs, on/off switch and connection test. */
class InsurerController extends Controller
{
    public function index()
    {
        return view('admin.insurers', ['insurers' => InsurerHealth::all()]);
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
