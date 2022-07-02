<?php

namespace Modules\Jumia\Console;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Modules\Jumia\Utils\JumiaUtil;
use App\Business;
use DB;

class JumiaSyncProducts extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'pos:JumiaSyncProducts {business_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Syncs products from pos to Jumia app';
    
  protected $jumiaUtil;
    /**
     * Create a new command instance.
     *
     * @param JumiaUtil $JumiaUtil
     * @return void
     */
    public function __construct(JumiaUtil $jumiaUtil)
    {
        parent::__construct();

        $this->jumiaUtil = $jumiaUtil;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $business_id = $this->argument('business_id');

        ini_set('memory_limit', '-1');
        ini_set('max_execution_time', 0);

     /*   try {
            $business = Business::findOrFail($business_id);
            $user_id = $business->owner_id;
            $sync_type = 'all';

            //Set timezone to business timezone
            $timezone = $business->time_zone;
            config(['app.timezone' => $timezone]);
            date_default_timezone_set($timezone);

            DB::beginTransaction();

            //Set offset 1 and limit 0 to bypass pagination
            $offset = 1;
            $limit = 0;

            $all_products = $this->jumiaUtil->syncProducts($business_id, $user_id, $sync_type, $limit, $offset);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());
        }*/
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['business_id', InputArgument::REQUIRED, 'ID of the business'],
        ];
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    // protected function getOptions()
    // {
    //     return [
    //         ['example', null, InputOption::VALUE_OPTIONAL, 'An example option.', null],
    //     ];
    // }
}
