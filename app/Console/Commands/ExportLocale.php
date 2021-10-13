<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

// Export locale messages files as YAML,
// to export them to onesky service
class ExportLocale extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'locale:export';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export locale to yml';

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
            $data = include(base_path() . '/resources/lang/' . $lang . '/messages.php');
            $yml = yaml_emit($data);
            file_put_contents(base_path() . '/resources/lang/' . $lang . '/messages.yml', $yml);
        }
    }
}
