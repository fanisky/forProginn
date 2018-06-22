<?php
/**
 * 将cdn管控指令从管局同步到本地
 */

namespace App\Console\Commands;

use App\Models\Cdn\SpCommand;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CdnSpCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cdn:spcommand';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    private $enterprises=[
       //此处为公司配置数据，已经删除
    ];

    /**
     *  同步程序
     */
    public function handle()
    {
        //
        foreach( $this->enterprises as $group_id=>$enterprise ) {
            $ip = $enterprise[0];
            $enterpriseId = $enterprise[1];

            Log::info("获取{$enterprise['2']}管控指令 企业id：" . $enterpriseId);
            echo "获取{$enterprise['2']}管控指令 企业id：{$enterpriseId}\r\n";


            try {
                $client = new \SoapClient("soap接口地址，已删除", array("encoding" => "UTF-8"));
                $datas = $client->__soapCall("spcommand",
                    array("enterpriseId" => $enterpriseId));
            } catch (SoapFault $e) {
                throw $e;
            };
            $datas = simplexml_load_string($datas);

            foreach ($datas->command as $command) {

                $command = (array)$command;
                $command['handle_status'] = 1;
                $command['handle_group'] = $group_id;
                //var_dump( $command );

                try {
                    $spCommand = new SpCommand();
                    $spCommand->sn = $command['sn'];

                    /*
                     *
                     * 涉及公司数据结构，已做删除
                     *
                     *
                     */


                    $spCommand->save();
                    Log::info("新增{$command['sn']}成功\r\n");
                    echo "新增{$command['sn']}成功\r\n";
                } catch (\Exception $exception) {
                    Log::info("新增{$command['sn']}失败 " . $exception->getMessage());
                    echo "新增{$command['sn']}失败\r\n";
                }
            }

        }
    }



}
