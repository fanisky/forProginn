<?php
/**
 * 同步cdn域名模块
 * 通过阿里云的dns接口，同步到阿里云dns服务上
 */

namespace App\Console\Commands;

use Aliyun\AliyunClient;
use Aliyun\Request\AddDomainRecord;
use Aliyun\Request\DeleteDomainRecord;
use Aliyun\Request\DescribeDomainRecords;
use App\Models\Cdn\Host;
use App\Models\Cdn\Privince;
use App\Models\Cdn\Province;
use Illuminate\Console\Command;

class CdnSyncDns extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cdn:syncDns {group_id}';

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

    private $group_id = 0;

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $group_id = $this->argument('group_id', 0 );
        $this->group_id = $group_id;
        $this->endDomain = 'cdn-' . $group_id;
        $this->cdnDomain = 'g-' . $group_id;

        //获取全部服务器
        $hosts = Host::where('enable', '=', 1)->where('group_id', '=', $group_id)->get();
        if( $hosts->count() <=0 ){
            echo '主机数量不能为0';
            return ;
        }

        //获取所有地区
        $provinces = Province::all();

        //删除所有线路
        $this->clearAll($this->endDomain);

        //创建A记录
        foreach( $hosts as $host ){
            $this->addA( $host );
        }

        //创建各省cname记录
        foreach( $provinces as $province ){
            $this->addLinkCnameV2( $province, $hosts );
        }

        //创建默认线路
        $rr = $this->createCnameARr();
        $value = $this->createHostARr( $hosts[0] ) . '.' . $this->mainDomain;
        //dd( $rr, $value );
        $this->addRecode($rr, 'CNAME', $value);

    }

    /**
     * 运营商配置
     * @var array
     */
    private $operator=[
        '电信'=>'telecom',
        '联通'=>'unicom',
        '移动'=>'mobile',
        '教育网'=>'edu',
    ];

    /**
     * 省份配置
     * @var array
     */
    private $province=[
        '浙江'=>'zhejiang',
        '内蒙古'=>'neimenggu',
        '江苏'=>'jiangsu',
        '江西'=>'jiangxi',
        '湖南'=>'hunan',
        '黑龙江'=>'heilongjiang',
    ];

    //必要的配置
    private $mainDomain = 'cdncn.com';
    private $endDomain = '';
    private $cdnDomain = '';


    /**
     *
     * 根据省份找到节点主机
     * @param $province
     * @param $hosts
     * @return null
     */
    private function findHostByProvince( $province, $hosts )
    {
        foreach( $hosts as $host ){
            if( $host->province == $province->text ){
                return $host;
            }
        }

        $minHost = null;
        $minDistance = null;
        $provincePos = explode(',', $province->position);
        foreach( $hosts as $host ){

            $hostPos = explode(',', $host->position );
            $distance = $this->distanceBetween($provincePos[0], $provincePos[1], $hostPos[0], $hostPos[1]);
//            dd( $distance );
            if( $minDistance == null || $minDistance > $distance ){
                $minHost = $host;
                $minDistance = $distance;
            }
        }


        return $minHost;
    }

    /**
     * 创建cname记录
     * @param $province
     * @param $hosts
     */
    private function addLinkCnameV2( $province, $hosts )
    {
        $rr = $this->createCnameARr();
        $host = $this->findHostByProvince( $province, $hosts );
        $value = $this->createHostARr( $host ) . '.' . $this->mainDomain;
        $line = $this->createLinkByProvince( $province, $host );

        $this->addRecode($rr, 'CNAME', $value, $line);
    }

    /**
     * 根据省份创建cname记录
     * @param $province
     * @param $host
     * @return string
     * @throws \Exception
     */
    private function createLinkByProvince( $province, $host )
    {

        if( !isset( $this->operator[$host->operator] ) )
        {
            throw new \Exception('未找到运营商配置');
        }

        return 'cn_' . $this->operator[$host->operator] . '_' . $province->name;
    }

    /**
     * 删除指定的记录
     * @param $rr
     */
    private function clearAll( $rr )
    {
        $records = $this->findRecord( $rr );
        foreach( $records as $record ){
            $this->removeRecode( $record['RecordId'] );
        }

    }


    /**
     * 根据主机名返回rr字符串
     * @param $host
     * @return string
     */
    private function createHostARr( $host )
    {
        $rr = $host->hostname . '-' . $host->id . '-0.' . $this->endDomain;
        return $rr;
    }


    /**
     * create poll name
     * @return string
     */
    private function createPollARr( )
    {
        $rr = 'poll.'.$this->endDomain;
        return $rr;
    }


    /**
     * 返回cdn主域名的RR
     * @return string
     */
    private function createCnameARr()
    {
        return $this->cdnDomain . '.' . $this->endDomain;
    }

    /**
     * 废弃
     */
    private function addPollCname( )
    {
        $rr = $this->createCnameARr();
        $value = $this->createPollARr( ) . '.' . $this->mainDomain;

        $records = $this->findRecord( $rr );
        foreach( $records as $record){
            //先删除
//            dd($record);
            if( $record['Value'] == $value )
                $this->removeRecode( $record['RecordId'] );
        }

        $this->addRecode($rr, 'CNAME', $value);

    }

    /**
     * 返回link记录字符串
     * @param $host
     * @return string
     * @throws \Exception
     */
    private function createLine( $host )
    {
        if( !isset( $this->operator[$host->operator] ) )
        {
            throw new \Exception('未找到运营商配置');
        }

        if( !isset( $this->province[$host->province] ) )
        {
            throw new \Exception('未找到地区配置');
        }

        return 'cn_' . $this->operator[$host->operator] . '_' . $this->province[$host->province];
    }

    /**
     * 废弃
     * @param $host
     */
    private function addLinkCname( $host )
    {
        $rr = $this->createCnameARr();
        $value = $this->createHostARr( $host ) . '.' . $this->mainDomain;
//        dd( $host->toArray() );
        $line = $this->createLine( $host );
//        dd( $line );

        $records = $this->findRecord( $rr );
//        dd($records);
        foreach( $records as $record){
            //先删除
//            dd($record);
            if( $record['Value'] == $value )
                $this->removeRecode( $record['RecordId'] );
        }

        $this->addRecode($rr, 'CNAME', $value, $line);
    }

    /**
     * 废弃
     * @param $host
     */
    private function addPollA( $host )
    {
        $rr = $this->createPollARr( );

        $records = $this->findRecord( $rr );
        foreach( $records as $record){
            //先删除
            //dd($record);
            if( $record['Value'] == $host->ip )
                $this->removeRecode( $record['RecordId'] );
        }
        $this->addRecode($rr, 'A', $host->ip);
    }

    /**
     * 创建a记录
     * @param $host
     */
    private function addA( $host )
    {
        $rr = $this->createHostARr( $host );

        $records = $this->findRecord( $rr );
        foreach( $records as $record){
            //先删除
            $this->removeRecode( $record['RecordId'] );
        }
        $this->addRecode($rr, 'A', $host->ip);
    }


    /**
     * 根据条件创建dns记录
     * @param $rr
     * @param $type
     * @param $value
     * @param string $line
     */
    private function addRecode( $rr, $type, $value, $line='' )
    {
        $request = new AddDomainRecord();
        $request->setDomainName($this->mainDomain)
            ->setRR($rr)
            ->setType($type)
            ->setValue($value)
        ;
        if( $line != '' ){
            $request->setLine($line);
        }

        $rs =  AliyunClient::execute($request);
    }

    /**
     * 删除dns记录
     * @param $rr
     * @return bool
     */
    private function removeRecode( $rr )
    {
        $request = new DeleteDomainRecord();
        $request->setRecordId($rr);
        $rs =  AliyunClient::execute($request);
        return true;
    }

    /**
     * 查找dns记录
     * @param $rr
     * @return array
     */
    private function findRecord( $rr )
    {
        //查找记录
        $request = new DescribeDomainRecords();
        $request->setDomainName($this->mainDomain)
            ->setPageNumber(1)
            ->setPageSize(500)
            ->setRRKeyWord($rr)
        ;
        $rs =  AliyunClient::execute($request);
        return isset( $rs['DomainRecords'] ) ? $rs['DomainRecords']['Record'] : [];
    }

    /**
     * 计算两个坐标之间的距离(米)
     * @param float $fP1Lat 起点(纬度)
     * @param float $fP1Lon 起点(经度)
     * @param float $fP2Lat 终点(纬度)
     * @param float $fP2Lon 终点(经度)
     * @return int
     */
    function distanceBetween($fP1Lat, $fP1Lon, $fP2Lat, $fP2Lon){
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
        $fP = pow(sin($fD1/2), 2) +
            cos($fRadLat1) * cos($fRadLat2) * pow(sin($fD2/2), 2);
        return intval($fEARTH_RADIUS * 2 * asin(sqrt($fP)) + 0.5);
    }

}
