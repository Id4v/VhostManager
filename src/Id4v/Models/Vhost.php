<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 06/02/15
 * Time: 23:09
 */

namespace Id4v\Models;

class Vhost {

    private static $protectedHosts=array(
        "phpmyadmin"
    );

    private static $basicConfSf2 = <<<EOF
<VirtualHost *:80>
    ServerName %url%
    ServerAlias www.%url%

    DirectoryIndex app.php

    DocumentRoot %path%/web
    <Directory %path%/web>
        # enable the .htaccess rewrites
        AllowOverride All
        Order allow,deny
        Allow from All
    </Directory>
</VirtualHost>
EOF;

    private static $basicConfSf1 = <<<EOF
<VirtualHost *:80>
  ServerName %url%
  ServerAlias www.%url%

  DocumentRoot %path%/web"
  DirectoryIndex index.php
  <Directory "%path%/web">
    AllowOverride All
    Allow from All
  </Directory>

  Alias /sf %path%/lib/vendor/symfony/data/web/sf
  <Directory "%path%/lib/vendor/symfony/data/web/sf">
    AllowOverride All
    Allow from All
  </Directory>
</VirtualHost>
EOF;

    private static $basicConf = <<<EOF
<VirtualHost *:80>
    ServerName %url%
    ServerAlias www.%url%

    DirectoryIndex index.php

    DocumentRoot %path%
    <Directory %path%>
        # enable the .htaccess rewrites
        AllowOverride All
        Order allow,deny
        Allow from All
    </Directory>
</VirtualHost>
EOF;




    private $name;
    private $path;
    private $url;
    private $confPath;
    private $exists;
    protected $modified;
    private $isNew;
    private $type;

    function __toString()
    {
        return $this->name."";
    }


    function __construct($file=null)
    {

        if($file==null)
        {
            $this->isNew=true;
            return;
        }

        $this->isNew=false;
        $this->confPath=$file;

        //On détermine le nom, sur le nom du fichier de conf
        $name=explode("/",$file);
        $name=$name[count($name)-1];
        $name=preg_replace("/\.conf/","",$name);
        $name=ucfirst($name);
        $this->name=$name;
        $this->modified=array();
    }

    public function isProtected(){
        foreach(self::$protectedHosts as $host):
            if(stripos($this->name,$host)!==false)
            {
                return true;
            }
        endforeach;
        return false;
    }

    public function getId(){
        return base64_encode($this->confPath);
    }


    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return mixed
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @param mixed $path
     */
    public function setPath($path)
    {
        $this->path = $path;
    }

    /**
     * @return mixed
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @param mixed $url
     */
    public function setUrl($url)
    {
        $this->url = $url;
    }

    /**
     * @return mixed
     */
    public function getConfPath()
    {
        return $this->confPath;
    }

    /**
     * @param mixed $confPath
     */
    public function setConfPath($confPath)
    {
        $this->confPath = $confPath;
    }

    public function exists(){
        if($this->exists==null)
            $this->exists = file_exists($this->path);
        return $this->exists;
    }


    public function fromConfig($config){
        if(preg_match("/DocumentRoot (.*)/",$config,$paths))
            $this->setPath($paths[1]);

        if(preg_match("/ServerName (.*)/",$config,$urls))
            $this->setUrl($urls[1]);


    }

    public function setModified($key,$value){
        $this->modified[$key]=$value;
    }

    public function save($basepath=null){

        if($this->isNew){
            $this->type="";
            $this->type=$this->modified["type"];
            $this->confPath=$basepath.$this->modified["name"].".conf";
            $confModel="basicConf".$this->type;
            $config=self::$$confModel;

            foreach($this->modified as $property=>$value){
                $config=str_replace("%".$property."%",$value,$config);
            }

        }else{
            $config=file_get_contents($this->confPath);
            foreach($this->modified as $property=>$value){
                $config=str_replace($this->$property,$value,$config);
            }
        }

        $fp=fopen($this->confPath, "w");
        fwrite($fp,$config);
        fclose($fp);
    }


}