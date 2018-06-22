<?php
/**
 * 修改解析记录
 *
 * 根据传入参数修改解析记录。
 *
 * @link https://help.aliyun.com/document_detail/dns/api-reference/record-related/UpdateDomainRecord.html?spm=5176.docdns/api-reference/record-related/DeleteDomainRecord.6.154.tnTS5W
 * 
 * @author Flc <2016-02-01 14:53:20>
 * @link http://flc.ren 
 */
namespace Aliyun\Request;

class UpdateDomainRecord
{

    /**
     * API请求参数
     * @var array
     */
    protected $params = array(
        'Action' => 'UpdateDomainRecord',
    ); 

    /**
     * 设置解析记录的ID，此参数在添加解析时会返回，在获取域名解析列表时会返回
     * @param string $value 域名名称
     */
    public function setRecordId($value)
    {
        $this->params['RecordId'] = $value;
        return $this;
    }

    /**
     * 设置主机记录，如果要解析@.exmaple.com，主机记录要填写"@”，而不是空
     * @param strign $value 
     */
    public function setRR($value)
    {
        $this->params['RR'] = $value;
        return $this;
    }

    /**
     * 设置解析记录类型，参见解析记录类型格式
     * @link https://help.aliyun.com/document_detail/dns/api-reference/enum-type/record-format.html?spm=5176.docdns/api-reference/record-related/AddDomainRecord.2.4.AGd2lH 
     * @param string $value 
     */
    public function setType($value)
    {
        $this->params['Type'] = $value;
        return $this;
    }

    /**
     * 设置记录值
     * @param string $value 
     */
    public function setValue($value)
    {
        $this->params['Value'] = $value;
        return $this;
    }

    /**
     * 设置生存时间，默认为600秒（10分钟），参见TTL定义说明
     * @param [type] $value [description]
     */
    public function setTTL($value)
    {
        $this->params['TTL'] = $value;
        return $this;
    }

    /**
     * MX记录的优先级，取值范围[1,10]，记录类型为MX记录时，此参数必须
     * @param [type] $value [description]
     */
    public function setPriority($value)
    {
        $this->params['Priority'] = $value;
        return $this;
    }

    /**
     * 解析线路，默认为default。参见解析线路枚举
     * @param [type] $value [description]
     */
    public function setLine($value)
    {
        $this->params['Line'] = $value;
        return $this;
    }

    /**
     * 返回所有参数
     * @return [type] [description]
     */
    public function getParams()
    {
        return $this->params;
    }
}