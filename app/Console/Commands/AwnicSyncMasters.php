<?php

namespace App\Console\Commands;

use App\Insurers\Awnic\AwnicAdapter;
use App\Insurers\InsurerManager;
use App\Models\InsurerCode;
use Illuminate\Console\Command;

class AwnicSyncMasters extends Command
{
    protected $signature = 'awnic:sync-masters {--type=* : Master types to sync (default: everything listed by masterList)} {--dump : Print the raw masterList response}';

    protected $description = 'Download AWNIC master data (nationality, emirates, vehicle makes, ...) into insurer_codes.';

    public function handle(InsurerManager $insurers): int
    {
        /** @var AwnicAdapter $awnic */
        $awnic = $insurers->get('awnic');
        $types = $this->option('type');
        if (! $types) {
            $list = $awnic->post('masterList', '/motor/masterList', ['requestType' => 'ALL']);
            if ($this->option('dump')) {
                $this->line(json_encode($list, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            }
            $types = $this->typesFrom($list['masterList'] ?? []);
        }
        foreach ($types as $type) {
            $rows = $awnic->masterData($type);
            $n = 0;
            foreach ($this->rows($rows) as [$code, $label]) {
                InsurerCode::updateOrCreate(['insurer' => 'awnic', 'type' => strtolower($type), 'code' => $code, 'parent' => null], ['label' => $label]);
                $n++;
            }
            $this->line(sprintf('%-28s %d rows', $type, $n));
        }
        $this->info('Done. Map AWNIC codes to DIN codes by setting insurer_codes.meta = {"din": "<code>"} (nationality, emirate, plateCode, bank).');

        return self::SUCCESS;
    }

    /** The response shape is not documented yet — accept the common shapes. */
    protected function typesFrom(array $list): array
    {
        $types = [];
        foreach ($list as $k => $v) {
            $types[] = is_string($k) ? $k : ($v['masterType'] ?? $v['code'] ?? $v);
        }

        return array_values(array_filter($types, 'is_string'));
    }

    protected function rows(array $data): iterable
    {
        $items = $data['values'] ?? $data['list'] ?? $data;
        foreach ($items as $k => $v) {
            if (is_array($v)) {
                $code = $v['code'] ?? $v['value'] ?? $v['id'] ?? null;
                $label = $v['desc'] ?? $v['description'] ?? $v['name'] ?? $v['label'] ?? $code;
            } else {
                [$code, $label] = [is_string($k) ? $k : $v, $v];
            }
            if ($code !== null) {
                yield [(string) $code, (string) $label];
            }
        }
    }
}
