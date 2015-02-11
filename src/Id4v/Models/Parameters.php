<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 07/02/15
 * Time: 17:49
 */

namespace Id4v\Models;

use Symfony\Component\Yaml\Yaml;


class Parameters {

    private $params=array();
    private $filePath;

    function __construct($filePath)
    {
        $this->filePath=$filePath;
        $this->params=Yaml::parse($filePath);
    }

    public function get($paramKey){
        return $this->params[$paramKey];
    }

    public function set($paramKey,$value){
        $this->params[$paramKey]=$value;
    }

    function save(){
        $fp=fopen($this->filePath,"r+");
        if($fp){
            fwrite($fp,Yaml::dump($this->params));
        }else{
            return false;
        }
        fclose($fp);
        return true;
    }

}