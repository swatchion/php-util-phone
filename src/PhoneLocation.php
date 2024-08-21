<?php

namespace Swatchion\Phone;

class PhoneLocation
{
    private $datFile;
    private $fileHandle;
    private $version;
    private $firstPhoneRecordOffset;
    private $phoneRecordCount;
    
    public function __construct($datFile = null)
    {
        if ($datFile === null) {
            $datFile = __DIR__ . '/../data/phone.dat';
        }
        $this->datFile = $datFile;
        $this->fileHandle = fopen($this->datFile, 'rb');
        
        if ($this->fileHandle === false) {
            throw new \RuntimeException("Unable to open dat file: {$this->datFile}");
        }
        
        // Read header
        $header = fread($this->fileHandle, 8);
        $head = unpack('Vversion/Voffset', $header);
        $this->version = $head['version'];
        $this->firstPhoneRecordOffset = $head['offset'];
        
        // Calculate record count
        fseek($this->fileHandle, 0, SEEK_END);
        $fileSize = ftell($this->fileHandle);
        $this->phoneRecordCount = ($fileSize - $this->firstPhoneRecordOffset) / 9;
    }
    
    public function __destruct()
    {
        if (is_resource($this->fileHandle)) {
            fclose($this->fileHandle);
        }
    }
    
    public function find($phoneNum)
    {
        $phoneNum = (string)$phoneNum;
        if (strlen($phoneNum) < 7 || strlen($phoneNum) > 11) {
            return null;
        }
        
        $intPhone = (int)substr($phoneNum, 0, 7);
        $left = 0;
        $right = $this->phoneRecordCount - 1;
        
        while ($left <= $right) {
            $middle = (int)(($left + $right) / 2);
            $currentOffset = $this->firstPhoneRecordOffset + $middle * 9;
            
            fseek($this->fileHandle, $currentOffset);
            $buffer = fread($this->fileHandle, 9);
            
            if (strlen($buffer) < 9) {
                return null;
            }
            
            $curPhone = unpack('V', substr($buffer, 0, 4))[1];
            
            if ($curPhone > $intPhone) {
                $right = $middle - 1;
            } elseif ($curPhone < $intPhone) {
                $left = $middle + 1;
            } else {
                $recordOffset = unpack('V', substr($buffer, 4, 4))[1];
                $phoneType = ord($buffer[8]);
                $recordContent = $this->getRecordContent($recordOffset);
                return $this->formatPhoneContent($phoneNum, $recordContent, $phoneType);
            }
        }
        
        return null;
    }
    
    private function getRecordContent($offset)
    {
        fseek($this->fileHandle, $offset);
        $content = '';
        while (($char = fgetc($this->fileHandle)) !== false && $char !== "\0") {
            $content .= $char;
        }
        return $content;
    }
    
    private function formatPhoneContent($phoneNum, $recordContent, $phoneType)
    {
        list($province, $city, $zipCode, $areaCode) = explode('|', $recordContent);
        return [
            'phone' => $phoneNum,
            'province' => $province,
            'city' => $city,
            'zip_code' => $zipCode,
            'area_code' => $areaCode,
            'phone_type' => $this->getPhoneNoType($phoneType)
        ];
    }
    
    private function getPhoneNoType($no)
    {
        $types = [
            1 => '移动', 2 => '联通', 3 => '电信',
            4 => '电信虚拟运营商', 5 => '联通虚拟运营商', 6 => '移动虚拟运营商',
            7 => '广电', 8 => '广电虚拟运营商'
        ];
        return $types[$no] ?? '未知';
    }
}