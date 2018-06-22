<?php

namespace App\Console\Commands;

use Aliyun\AliyunClient;
use Aliyun\Request\AddDomainRecord;
use Aliyun\Request\DeleteDomainRecord;
use Aliyun\Request\DescribeDomainRecords;
use App\Models\Cdn\Domain;
use App\Models\Cdn\Host;
use App\Models\Cdn\Province;
use App\Models\Cdn\SpCommand;
use App\Respositories\Aliyun\DnsRepository;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CdnHandleSpCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cdn:handleSpcommand';

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

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        //获取需要处理的指令
        $model = SpCommand::where('handle_status', '=', '1')
            //->where('effectiveDate', '<=', date('Y-m-d H:i:s'))
            ->where('objectType', '<>', '999')
            ->where('rule', '<>', '999');
        $commands = $model->get();

        foreach ($commands as $command) {

            Log::info("CDN#处置管理指令" . json_encode($command));
            if ($command->commandType == 1) {
                $cpCommand = SpCommand::where('sn', '=', $command->sn)->where('commandType', '=', 0)->first();
                if ($cpCommand) {
                    $command->type = $cpCommand->type;
                    $command->objectType = $cpCommand->objectType;
                    $command->object = $cpCommand->object;
                    $command->effectiveScope = $cpCommand->effectiveScope;
                    $command->rule = $cpCommand->rule;

                } else {
                    Log::info('CDN#处置管理指令失败 未找到相应的管控指令 ' . $command->sn);
                }
            }

            $this->endDomain = 'cdn-' . $command->handle_group;
            $this->cdnDomain = 'g-' . $command->handle_group;

            //根据既定规则进入不通的处理程序
            if ($command->rule == 2) {
                //停止加速 与cdn节点无关
                try {
                    $this->rule2($command);
                    $command->handle_status = 2;
                    $command->save();
                    Log::info("CDN#处置管理指令成功");
                } catch (\Exception $exception) {
                    //异常处理
                    Log::info('CDN#处置管理指令失败 ' . $command->sn . '|' . $exception->getMessage());
                    $command->handle_status = 3;
                    $command->save();
                }
            } else {

                $hosts = $this->getHosts($command->effectiveScope, $command->handle_group);
                try {
                    foreach ($hosts as $host) {
                        Log::info("CDN#处置主机" . json_encode($host));
                        try {
                            $this->handleCommand($host, $command);
                        } catch (\Exception $exception) {
                            //记录异常日志，以便之后排查
                            Log::info('CDN#处置管理指令异常 ' . $command->sn . '|' . $exception->getMessage());
                        }
                    }
                    $command->handle_status = 2;
                    $command->save();
                    Log::info("CDN#处置管理指令成功");
                } catch (\Exception $exception) {
                    //异常处理
                    Log::info('CDN#处置管理指令失败 ' . $command->sn . '|' . $exception->getMessage());
                    $command->handle_status = 3;
                    $command->save();
                }

            }

        }

    }

    /**
     * 省份节点配置
     * @var array
     */
    private $provinceToNode = [
        1=>[
            '安徽'=>7,
            '浙江'=>7,
            '福建'=>7,
            '江苏'=>10,
            '山东'=>10,
            '上海'=>10,
            '广东'=>4,
            '广西'=>4,
            '海南'=>4,
            '四川'=>5,
            '云南'=>5,
            '贵州'=>5,
            '西藏'=>5,
            '重庆'=>5,
            '湖北'=>5,
            '湖南'=>5,
            '河南'=>5,
            '江西'=>5,
            '辽宁'=>6,
            '吉林'=>6,
            '黑龙江'=>6,
            '北京'=>2,
            '天津'=>2,
            '河北'=>2,
            '山西'=>2,
            '内蒙古'=>2,
            '宁夏'=>2,
            '新疆'=>2,
            '青海'=>2,
            '陕西'=>2,
            '甘肃'=>2

        ]
    ];

    //必要配置
    private $mainDomain = 'cdncn.com';
    private $endDomain = '';
    private $cdnDomain = '';

    /**
     * 获取指定的主机列表
     * @param $province
     * @param $handle_group
     * @return array
     */
    private function getHosts($province, $handle_group)
    {
        //统一省份名称
        $province = str_replace('省', '', $province);
        $province = str_replace('市', '', $province);

        //获取全部服务器
        $hosts = Host::where('enable', '=', 1)->where('group_id', '=', $handle_group)->get();
        if ($hosts->count() <= 0) {
            echo '主机数量不能为0';
            return [];
        }

        if ($province == '全国') {
            return $hosts;
        }

        if( isset( $this->provinceToNode[$handle_group] ) ){
            if( isset( $this->provinceToNode[$handle_group][$province] ) ){
                $host_id = $this->provinceToNode[$handle_group][$province];
                $host = Host::find( $host_id );
                if( $host ){
                    return [$host];
                }else{
                    return [];
                }
            }else{
                return [];
            }
        }

        //获取地区
        $province = Province::where('text', 'like', "%$province%")->first();
        if (!$province) {
            return [];
        }

        $host = $this->findHostByProvince($province, $hosts);
        return [$host];

    }

    /**
     * 根据省份获取主机
     * @param $province
     * @param $hosts
     *
     */
    private function findHostByProvince($province, $hosts)
    {

        foreach ($hosts as $host) {
            if ($host->province == $province->text) {
                return $host;
            }
        }

        $minHost = null;
        $minDistance = null;
        $provincePos = explode(',', $province->position);
        foreach ($hosts as $host) {
            $hostPos = explode(',', $host->position);
            $distance = $this->distanceBetween($provincePos[0], $provincePos[1], $hostPos[0], $hostPos[1]);
            if ($minDistance == null || $minDistance > $distance) {
                $minHost = $host;
                $minDistance = $distance;
            }
        }


        return $minHost;
    }

    /**
     * 计算两个坐标之间的距离(米)
     * @param float $fP1Lat 起点(纬度)
     * @param float $fP1Lon 起点(经度)
     * @param float $fP2Lat 终点(纬度)
     * @param float $fP2Lon 终点(经度)
     * @return int
     */
    function distanceBetween($fP1Lat, $fP1Lon, $fP2Lat, $fP2Lon)
    {
        $fEARTH_RADIUS = 6378137;
        //角度换算成弧度
        $fRadLon1 = deg2rad($fP1Lon);
        $fRadLon2 = deg2rad($fP2Lon);
        $fRadLat1 = deg2rad($fP1Lat);
        $fRadLat2 = deg2rad($fP2Lat);
        //计算经纬度的差值
        $fD1 = abs($fRadLat1 - $fRadLat2);
        $fD2 = abs($fRadLon1 - $fRadLon2);
        //距离计算
        $fP = pow(sin($fD1 / 2), 2) +
            cos($fRadLat1) * cos($fRadLat2) * pow(sin($fD2 / 2), 2);
        return intval($fEARTH_RADIUS * 2 * asin(sqrt($fP)) + 0.5);
    }


    /**
     * 根据规则分发指令处理过程
     * @param $host
     * @param $command
     */
    private function handleCommand($host, $command)
    {
        $rule = $command->rule;
        if ($rule == 1) {
            //拒绝访问
            $this->rule1($host, $command);
        } elseif ($rule == 3) {
            //清除缓存
            $this->rule3($host, $command);
        }


    }

    private $operator = [
        '电信' => 'telecom',
        '联通' => 'unicom',
        '移动' => 'mobile',
        '教育网' => 'edu',
    ];

    private function createLinkByProvince($province, $host)
    {

        if (!isset($this->operator[$host->operator])) {
            throw new \Exception('未找到运营商配置');
        }

        return 'cn_' . $this->operator[$host->operator] . '_' . $province->name;
    }

    /**
     * 规则2处理过过程
     * @param $command
     * @throws \Exception
     */
    private function rule2($command)
    {
        $mainDomain = 'waechina.com';

        if ($command->objectType != 1) {
            //非域名
            //不处理
            //throw new \Exception('非域名对象，不处理停止加速');

            $tmp = explode('/', $command->object);
            $object = $tmp[0];
        } else {
            $object = $command->object;
        }

        $object = str_replace('www.', '', $object);
        $domain = Domain::where('domain', 'like', "%{$object}")->first();
        if (!$domain) {
            throw new \Exception('Domain数据没有找到');

        }
        $rr = 'cn' . $domain->id . '.cname';
        $cname = $this->cdnDomain . '.' . $this->endDomain . '.' . $this->mainDomain;

        if ($command->commandType == 0) {
            //监管指令类型

            $province = $command->effectiveScope;
            $province = str_replace('省', '', $province);
            $province = str_replace('市', '', $province);

            if ($province == '全国') {
                //查找记录
                $request = new DescribeDomainRecords();
                $request->setDomainName($mainDomain)
                    ->setPageNumber(1)
                    ->setPageSize(500)
                    ->setRRKeyWord($rr);
                $rs = AliyunClient::execute($request);

                //删除原记录
                if (isset($rs['DomainRecords'])) {
                    foreach ($rs['DomainRecords']['Record'] as $record) {

                        $request = new DeleteDomainRecord();
                        $request->setRecordId($record['RecordId']);
                        $rs = AliyunClient::execute($request);
                    }
                }

                //添加新记录
                $request = new AddDomainRecord();
                $request->setDomainName($mainDomain)
                    ->setRR($rr)
                    ->setType('A')
                    ->setValue($domain->ip);
                $rs = AliyunClient::execute($request);
            } else {

                //获取地区
                $province = Province::where('text', 'like', "%$province%")->first();
                if (!$province) {
                    throw new \Exception('地区数据没有找到');
                }


                $list = ['电信', '移动', '联通', '教育网'];
                foreach ($list as $item) {
                    $line = 'cn_' . $this->operator[$item] . '_' . $province->name;
                    //添加新记录
                    $request = new AddDomainRecord();
                    $request->setDomainName($mainDomain)
                        ->setRR($rr)
                        ->setType('A')
                        ->setValue($domain->ip)
                        ->setLine($line);
                    $rs = AliyunClient::execute($request);
                }
            }

        } elseif ($command->commandType == 1) {
            //恢复指令类型

            //查找记录
            $request = new DescribeDomainRecords();
            $request->setDomainName($mainDomain)
                ->setPageNumber(1)
                ->setPageSize(500)
                ->setRRKeyWord($rr);
            $rs = AliyunClient::execute($request);

            //删除原记录
            if (isset($rs['DomainRecords'])) {
                foreach ($rs['DomainRecords']['Record'] as $record) {

                    $request = new DeleteDomainRecord();
                    $request->setRecordId($record['RecordId']);
                    $rs = AliyunClient::execute($request);
                }
            }

            //添加新记录
            $request = new AddDomainRecord();
            $request->setDomainName($mainDomain)
                ->setRR($rr)
                ->setType('CNAME')
                ->setValue($cname);
            $rs = AliyunClient::execute($request);

        } else {
            throw new \Exception('未知指令类型sn:' . $command->sn);
        }


    }

    /**
     * 规则1处理过程
     * @param $host
     * @param $command
     * @return bool
     * @throws \Exception
     */
    private function rule1($host, $command)
    {
        $sessionId = $this->getSessionId($host);
        $sourceUrl = str_replace('.', "\\.", $command->object);
        if ($command->objectType == 1) {
            //域名
            $sourceUrl = ".*{$sourceUrl}.*";
        } elseif ($command->objectType == 2) {
            if (stripos($command->object, '*') !== false) {
                //带星号
                $sourceUrl = str_replace('*', ".*", $sourceUrl);
                $sourceUrl = ".*{$sourceUrl}.*";
            } else {
                //精确不带星号
                $sourceUrl = ".*{$sourceUrl}.*";
            }
        } else {
            //不处理
            return true;
        }

        if ($command->commandType == 0) {
            //监管指令类型
            $api = "http://{$host->ip}/fikker/webcache.fik?type=rewrite&cmd=add&SessionID={$sessionId}"
                . "&SourceUrl=" . urlencode($sourceUrl)
                . "&DestinationUrl=" . urlencode("http://127.0.0.1/error")
                . "&Icase=1"
                . "&Flag=Return"
                . "&Note=sn#{$command->sn}";
            $response = $this->api($api);
            if ($response->Return == 'True') {
                return true;
            } else {
                throw new \Exception('处理失败sn:' . $command->sn);
            }
        } elseif ($command->commandType == 1) {
            //恢复指令类型
            $rewriteList = $this->getRewriteList($host);
            $rewrite = $this->findRewrite($rewriteList, $sourceUrl);
            $api = "http://{$host->ip}/fikker/webcache.fik?type=rewrite&cmd=del&SessionID={$sessionId}"
                . "&RewriteID={$rewrite->RewriteID}";
            $response = $this->api($api);
            if ($response->Return == 'True') {
                return true;
            } else {
                throw new \Exception('删除转向失败');
            }
        } else {
            throw new \Exception('未知指令类型');
        }

    }

    /**
     * 查询cdn的rewrite记录
     * @param $rewriteList
     * @param $url
     * @return bool
     */
    private function findRewrite($rewriteList, $url)
    {
        foreach ($rewriteList->Lists as $rewrite) {
            if ($rewrite->SourceUrl == $url) {
                return $rewrite;
            }
        }
        return false;
    }

    /**
     * 获取指定主机的cdn rewrite记录
     * @param $host
     * @return mixed
     * @throws \Exception
     */
    private function getRewriteList($host)
    {
        $sessionId = $this->getSessionId($host);

        $api = "http://{$host->ip}/fikker/webcache.fik?type=rewrite&cmd=list&SessionID={$sessionId}";
        $response = $this->api($api);
        if ($response->Return == 'True') {
            return $response;
        } else {
            throw new \Exception('获取RewriteList失败');
        }
    }

    /**
     * 规则3处理过程
     * @param $host
     * @param $command
     * @return bool
     * @throws \Exception
     */
    private function rule3($host, $command)
    {
        $sessionId = $this->getSessionId($host);
        $sourceUrl = str_replace('.', "\\.", $command->object);
        $Icase = 1;
        $Rules = 1;
        if ($command->objectType == 1) {
            //域名
            $sourceUrl = "http://{$sourceUrl}.*";
        } elseif ($command->objectType == 2) {
            if (stripos($command->object, '*') !== false) {
                //带星号
                $sourceUrl = str_replace('*', ".*", $sourceUrl);
                $sourceUrl = "http://{$sourceUrl}.*";
            } else {
                //精确不带星号
                $sourceUrl = "http://{$sourceUrl}.*";
            }
        } else {
            //不处理
            return true;
        }

        if ($command->commandType == 0) {
            $api = "http://{$host->ip}/cdn-server/webcache.fik?type=thiscache&cmd=cleancache&SessionID={$sessionId}"
                . "&WithCluster=0"
                . "&Icase=" . $Icase
                . "&Rules=" . $Rules
                . "&Url=" . urlencode($sourceUrl);

            $response = $this->api($api);
            if ($response->Return == 'True') {
                return true;
            } else {
                throw new \Exception('CDN节点处理失败');
            }
        }
    }

    /**
     * 获取指定主机的session记录
     * @param $host
     * @return mixed
     * @throws \Exception
     */
    private function getSessionId($host)
    {
        if (isset($this->sessionId[$host->id]) && $this->sessionId[$host->id] != '') {
            return $this->sessionId[$host->id];
        }

        if ($this->cdnLogin($host)) {
            return $this->sessionId[$host->id];
        } else {
            throw new \Exception('未登陆');
        }
    }

    private $sessionId = [];
    private $user = 'admin';
    private $pass = 'cdnnet.com';
    private $loginResponse = null;

    /**
     * 登录cdn主机
     * @param $host
     * @return bool
     */
    private function cdnLogin($host)
    {
        $api = "http://{$host->ip}/cdn-server/webcache.fik?type=sign&cmd=in&Username={$this->user}&Password={$this->pass}";
        $response = $this->api($api);
        if ($response->Return == 'True') {
            $this->sessionId[$host->id] = $response->SessionID;
            $this->loginResponse = $response;
            return true;
        } else {
            $this->sessionId[$host->id] = null;
            $this->loginResponse = null;
            return false;
        }
    }

    /**
     * 调用api并返回数据
     * @param $api
     * @return mixed
     */
    private function api($api)
    {
        Log::info("CDN#send " . $api);
        $response = file_get_contents($api);
        Log::info("CDN#recive $response");
        return json_decode($response);
    }
}
