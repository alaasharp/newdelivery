<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

// Import locale messages files from YAML,
// to import them to onesky service
class ImportLocale extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'locale:import';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import locale from yml';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        foreach (['en', 'ru'] as $lang) {
            $contents = file_get_contents(base_path() . '/resources/lang/' . $lang . '/messages.yml');
            $data = yaml_parse($contents);
            $final = [
                "<?php",
                "return ",
                var_export($data, true),
                ';'
            ];
            file_put_contents(base_path() . '/resources/lang/' . $lang . '/messages.php', implode("\n", $final));
        }
    }
}
