<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 06/02/15
 * Time: 11:28
 */
namespace Id4v\Controllers;

use Id4v\Models\Vhost;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;


class FrontController {


    private static $vhosts=array();
    private static $app = null;

    /**
     * Basic Controllers Functions
     */

    //Execute Code before every requests
    public function preExecute(Request $request,Application $app){

        $params=$app["parameters"];
        $vhostsPattern=$params->get("basepath");
        $vhostsPattern.="*.conf";

        $ignoredVhosts=$params->get("ignore");

        $vhosts=array();

        $files=glob($vhostsPattern);
        foreach($files as $file){
            $goNext=false;
            foreach($ignoredVhosts as $ignored):
                if(stripos($file,$ignored)!==false)
                    $goNext=true;
            endforeach;
            if($goNext)
                continue;
            $config=file_get_contents($file);
            $vhost=new Vhost($file);
            $vhost->fromConfig($config);

            $vhosts[$vhost->getId()]=$vhost;
        }

        asort($vhosts);
        self::$app = $app;
        self::$vhosts=$vhosts;

        $app["twig"]->addGlobal("success",$app["session"]->getFlashBag()->get("success"));
        $app["twig"]->addGlobal("errors",$app["session"]->getFlashBag()->get("errors"));
        $app["twig"]->addGlobal("warnings",$app["session"]->getFlashBag()->get("warnings"));
        $app["twig"]->addExtension(new \Twig_Extension_Debug());

    }

    //Render Template
    public function render($file,$params=null){
        return self::$app["twig"]->render($file,$params);
    }


    /**
     * Actions
     */

    /**
     * Index, list all Vhosts
     * @param Request $request
     * @param Application $app
     * @return mixed
     */
    public function index(Request $request, Application $app){
        return $this->render("index.html.twig",array("vhosts"=>self::$vhosts));
    }

    public function checkStatus(Request $request){
        $url=$request->get("url");

        $handle = curl_init($url);
        curl_setopt($handle,  CURLOPT_RETURNTRANSFER, TRUE);

        /* Get the HTML or whatever is linked in $url. */
        $response = curl_exec($handle);

        /* Check for 404 (file not found). */
        $httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);

        curl_close($handle);

        return $httpCode;
    }

    public function create(Request $request, Application $app){
        $vhost=new Vhost();
        $vhost->setPath("/Users/david/Web/PhpstormProjects");
        if($request->isMethod("POST")){
            $params=$app["parameters"];
            foreach($request->get("vhost") as $property=>$value){
                $vhost->setModified($property,$value);
            }
            $vhost->save($params->get("basepath"));
        }
        return $this->render("new.html.twig",array("vhost"=>$vhost));
    }

    /**
     * Edit a Vhost
     * @param Request $request
     * @param Application $app
     */
    public function edit(Request $request,Application $app){
        /** @var Vhost $vhost */
        $vhost=self::$vhosts[$request->get("hash")];

        if($request->isMethod("POST")){
            foreach($request->get("vhost") as $property=>$value){
                $vhost->setModified($property,$value);
            }
            $vhost->save();
            $app["session"]->getFlashBag()->set("success","Vhost modifié, n'oubliez pas de relancer apache");
            return $app->redirect("/");
        }
        return $this->render("edit.html.twig",array("vhost"=>$vhost));
    }

    /**
     * Add the Vhost to the ignore list in parameters.yml
     * @param Request $request
     * @param Application $app
     * @return string|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function ignore(Request $request,Application $app){

        $params=$app["parameters"];

        $ignoredFiles=$params->get("ignore");
        $hash=$request->get("hash");
        /** @var Vhost $vhost */
        $vhost=self::$vhosts[$hash];
        $ignoredFiles[]=str_replace($params->get("basepath"),"",$vhost->getConfPath());
        $params->set("ignore",$ignoredFiles);
        if($params->save()){
            $app["session"]->getFlashBag()->set("success","Vhost Ignoré avec succès");
            return $app->redirect("/",302);
        }
        return "Error";
    }

    /**
     * Change the basepath in the parameters.yml
     * @param Request $request
     * @param Application $app
     * @return mixed
     */
    public function settings(Request $request,Application $app){

        $params=$app["parameters"];

        if($request->isMethod("POST")){
            $params->set("basepath",$request->get("basepath"));
            $params->save();
        }

        return $this->render("settings.html.twig",array("basepath"=>$params->get("basepath")));
    }

    public function delete(Request $request, Application $app){
        /** @var Vhost $vhost */
        $vhost=self::$vhosts[$request->get("hash")];
        if(unlink($vhost->getConfPath()))
            $app["session"]->getFlashBag()->set("success","Vhost supprimé");
        else
            $app["session"]->getFlashBag()->set("errors","Erreur lors de la suppression");

        return $app->redirect("/");
    }

}