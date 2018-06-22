<?php

namespace App\Admin\Extensions;

use Encore\Admin\Grid\Exporters\AbstractExporter;
use Illuminate\Support\Arr;
use Maatwebsite\Excel\Facades\Excel;

class ExcelExpoter extends AbstractExporter
{
    private $fileName = '合同汇总表';

    private $fieldKeys = ['id'];//['id', 'contract.number', 'customer.name', 'customer.abbreviation', 'customer.contact']

    public function __construct( $fieldKeys = null, $fileName = null, Grid $grid = null)
    {
        if ($fileName) {
            $this->fileName = $fileName;
        }
        if( $fieldKeys ){
            $this->fieldKeys = $fieldKeys;
        }
        parent::__construct( $grid );
    }

    /**
     * 导出excel
     */
    public function export()
    {
        Excel::create($this->fileName, function($excel) {

            $excel->sheet($this->fileName, function($sheet) {
                // 这段逻辑是从表格数据中取出需要导出的字段
                $rows = collect($this->getData())->map(function ($item) {
                    foreach( $item as $key=>$value){
                        if( is_array($value) ){
                            foreach( $value as $v_key=>$v_value ){
                                $item["{$key}.{$v_key}"] = $v_value;
                            }
                        }

                    }
                    $fields = $this->getFieldAry();
                    $row = [];
                    foreach( $fields as $field ){
                        if( isset( $item[$field] ) ){
                            $row[$field] = $item[$field];
                        }else{
                            $row[$field] = null;
                        }
                    }
                    $this->handleValue( $row, $item );
                    return $row;
                });
                //表格加入表头
                $rows->prepend($this->getFieldTitle());
                //将表格加入excel
                $sheet->rows($rows);

            });

        })->export('xls');
    }

    private function handleValue( &$row, $data )
    {
            foreach( $this->fieldKeys as $key=>$value ){
                if( is_array( $value ) && $value[1] instanceof \Closure){
                    //dd('xxx');
                    $row[$value[0]] = $value[1]( $data, $value[0] );
                }
            }
    }

    //获取表头
    private function getFieldTitle()
    {
        $ary = [];
        foreach( $this->fieldKeys as $key=>$value ){
            if( is_array( $value ) ){
                $ary[$value[0]] = $key;
            }else{
                $ary[$value] = $key;
            }
        }
        return $ary;
    }

    //获取字段数组
    private function getFieldAry()
    {
        $ary = [];
        foreach( $this->fieldKeys as $key=>$value ){
            if( is_array( $value ) ){
                $ary[] = $value[0];
            }else{
                $ary[] = $value;
            }
        }
        return $ary;
    }
}