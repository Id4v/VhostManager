<?php

require_once __DIR__."/../vendor/autoload.php";

use Id4v\Controllers\FrontController;
use Id4v\Models\Parameters;

$app=new Silex\Application();

$app["debug"]=true;

//Register Services
$app->register(new Silex\Provider\TwigServiceProvider(), array(
  'twig.path' => __DIR__.'/../views',
));
$app->register(new Silex\Provider\SessionServiceProvider());
$app->register(new DerAlex\Silex\YamlConfigServiceProvider(__DIR__."/../config/routes.yml"));
$app->register(new Silex\Provider\SessionServiceProvider());


//Creating routes
foreach($app["config"]["routes"] as $name=>$route){
    $routeObj=$app->match($route["url"],$route["controller"]."::".$route["action"]);
    if(isset($route["default"])):
        foreach($route["default"] as $varName=>$varDefault){
            $routeObj->value($varName,$varDefault);
        }
    endif;
    $routeObj->before($route["controller"]."::preExecute");
    $routeObj->bind($name);
    if(isset($route["methods"]))
        $routeObj->method($route["methods"]);
    else
        $routeObj->method("GET");
}

//Loading parameters
$app["parameters"]=new Parameters(__DIR__."/../config/parameters.yml");

//Configure Twig
$app["twig.loader.filesystem"]->addPath(__DIR__."/../src/Id4v/views/","Id4v");



$app->run();

