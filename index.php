<?php

//~ BOOTSTRAP Start
$service_dir = "/nannodit/{{service_name}}";
$log_dir = $service_dir.'/log';
$config_dir = $service_dir.'/config';
$views_dir = $service_dir.'/views';

require $service_dir . "/vendor/autoload.php";

//~ Service

$service_config_array = (new \abcvyz\lib\config(
    $config_dir.'/service_config.yaml',
    []
))->asArray();


$otp_config_array = $service_config_array['otp'];
        


//~ ~ Log 
$log_config_params_array = array(
    'name' => '',
    'timezone' => 'Asia/Tashkent',
    'captureSystemErrors'=> true,
    'instance' => microtime(),
    'extension' => '.log',
    'path' => $log_dir.'/main'
);
$log_config_array = (new \abcvyz\lib\config(
    $config_dir.'/log_v21_config.yaml',
    $log_config_params_array
))->asArray();

$log = new \abcvyz\lib\logger_v21($log_config_array);

//~ ~ Mustache
$mustache = new Mustache_Engine;

//~ BOOTSTRAP End

//~ FUNCTIONS Start
$call_otp_api = function ($url,$timout){
    //~ create a guzzle client
    $client = new \GuzzleHttp\Client([
        'timeout'  => $timout,
        'headers' => [
            'Accept' => 'application/json',
        ]
    ]);

    //~ ~ make a get request to api
    $request = $client->request("GET",$url);
    $response = $request->getBody()->getContents();
    $response_json = json_decode($response,true);
    return $response_json;
};

$send_otp = function ($msisdn,$language,$campaign_group) 
use (
    $log,
    $call_otp_api,
    $mustache,
    $otp_config_array
){
    $otp_send_url_template = $otp_config_array['url'].'?'.$otp_config_array['send'];

    $otp_send_url = $mustache->render(
            $otp_send_url_template,
            array(
                'msisdn'=>$msisdn,
                'language'=>$language,
                'mod'=>$campaign_group
            )
        );
        $log->debug("Will call otp send url",[
            'otp_send_url'=>$otp_send_url
        ]);

        $otp_send_response_array = $call_otp_api(
            $otp_send_url,
            $otp_config_array['timeout']
        );
        $log->debug("Received response from otp send",[
            'otp_send_response'=>$otp_send_response_array
        ]);        
        
        $otp_send_result_array = $otp_send_response_array['result'];
        $log->debug("Result of otp send",[
            'otp_send_result_array'=>$otp_send_result_array
        ]); 
        if (! $otp_send_result_array['isSuccess'])
            throw new exception ("Otp send was not success");
        
        $log->debug("Otp send was success");

        return $otp_send_result_array;
};

$validate_otp = function ($msisdn,$language,$rid,$pin,$campaign_group) 
use (
    $log,
    $call_otp_api,
    $mustache,
    $otp_config_array
){
    $otp_validate_url_template = $otp_config_array['url'].'?'.$otp_config_array['validate'];

    $otp_validate_url = $mustache->render(
        $otp_validate_url_template,
        array(
            'msisdn'=>$msisdn,
            'language'=>$language,
            'rid'=>$rid,
            'pin'=>$pin,
            'mod'=>$campaign_group,
        )
    );
    $log->debug("Will call otp validate url",[
        'otp_validate_url'=>$otp_validate_url
    ]);

    $otp_validate_response_array = $call_otp_api(
        $otp_validate_url,
        $otp_config_array['timeout']
    );
    $log->debug("Received response from otp validate",[
        'otp_validate_response'=>$otp_validate_response_array
    ]);        
    
    $otp_validate_result_array = $otp_validate_response_array['result'];
    $log->info("Result of otp validate",[
        'otp_validate_result_array'=>$otp_validate_result_array
    ]); 

    return $otp_validate_result_array;
};

//~ FUNCTIONS End

//~ INITIALIZE Start
$headers_array = getallheaders();

$log->info ("Serving to ",[
    '_get'=>$_GET,
    '_post'=>$_POST,
    '_headers'=>$headers_array
]);

//~ Languages
$language_array = (new \abcvyz\lib\config(
    $config_dir.'/languages.yaml',
    $log_config_params_array
))->asArray();

//~ INITIALIZE End

//~ START

//~ language set
//NOTE : Obtain the user browser language
$language = substr($headers_array['Accept-Language'], 0, 2);
//NOTE : Default language to be uz if absent in the browser
if (empty($language)) $language = $service_config_array['default_language'];
//NOTE : Overwrite language if specified in query params
$language = $_GET['language'] ?? $language;

if($_GET['page'] ?? false){
    $pages_array = ["all the pages available in the site"]; // eg oferta, prizyory, faq
    $page = $_GET['page'];
    $language = $_GET['lang'] ?? 'ru';

    // ensure page name is a page
    if(!in_array($page,$pages_array)){
        header('location: /');
    }
    else{
        echo $mustache->render(
            file_get_contents($views_dir.'/'.$page.'_'.$language.'.mustache'),
            $language_array[$language]
        );
    }
    exit;
    //~ EXIT
}

$log->debug("Will use language : $language");

//~ Detect form submission
if (!empty($_GET['msisdn']) && !empty($_GET['language'])){
    $log->info ("It's a form submission");

    if (empty($_GET['pin']) && empty($_GET['rid'])){
        $log->info("It's a otp send request, calling api send otp");

        //~ ~ Validate inputs
        if (preg_match($service_config_array['operator_number_regex'],$_GET['msisdn']) != 1) {
            echo json_encode(['isSuccess'=>false,'message'=>"Number format no match"]);
            exit;
        }

        $language = $_GET['language'];
        $msisdn = $_GET['msisdn'];
        $message = $language_array[$language]['otp']['send']['server_error'];

        //~ ~ Call otp api for otp send
        try{
            $otp_send_result_array = $send_otp(
                $msisdn,
                $language,
                $campaign_group=null
            );
        } catch(Exception $e){
            $log->error("Error during otp send api call",[
                'e'=>(string) $e,
            ]);
            echo json_encode(['isSuccess'=>false,'message'=>$message]);
            exit;
            //~ EXIT
        }
        $log->info("Obtained result of otp send",[
            'otp_send_result_array'=>$otp_send_result_array
        ]); 

        //~ ~ Emit result json
        if (($otp_send_result_array['isSuccess'] ?? false )) {
            $message = $language_array[$language]['otp']['send']['success'];
            echo json_encode([
                'success'=>true,
                'message'=>$message,
                'msisdn'=>$_GET['msisdn'],
                "rid"=>$otp_send_result_array['rid']
            ]);
            exit;
        }
        else {
            $log->error("Otp send api call isSuccess was not true",[
                'otp_send_result_array'=>$otp_send_result_array,
            ]);
            echo json_encode(['isSuccess'=>false,'message'=>$message]);
        }
        exit;
    }

    $log->info("It's a otp validation request");
    #....
    //~ ~ Validate inputs
    if (preg_match($service_config_array['operator_number_regex'],($_GET['msisdn'] ?? '')) != 1) {
        $log->error("Portal api send a misformat msisdn",[
            '_get'=>$_GET,
        ]);
        echo json_encode([
            'isSuccess'=>false,
            'message'=>$language_array[$language]['otp']['validate']['failure_msisdn'],
        ]);
        exit;
    }
    elseif (preg_match($service_config_array['otp_pin_regex'],($_GET['pin'] ?? '')) != 1) {
        $log->error("Portal api send a malformed pin",[
            '_get'=>$_GET,
        ]);
        echo json_encode([
            'isSuccess'=>false,
            'message'=>$language_array[$language]['otp']['validate']['failure_format'],
        ]);
        exit;
    }
    elseif (preg_match($service_config_array['otp_rid_regex'],($_GET['rid'] ?? '')) != 1) {
        $log->error("Portal api send a malformed rid",[
            '_get'=>$_GET,
        ]);
        echo json_encode([
            'isSuccess'=>false,
            'message'=>$language_array[$language]['otp']['validate']['failure_format'],
        ]);
        exit;
    }

    $log->debug("Input is validated, calling otp api validation");

    $msisdn = $_GET['msisdn'];
    $language = $_GET['language'];
    $rid = $_GET['rid'];
    $pin = $_GET['pin'];

    //~ ~ Call otp api for validation
    try{
        $otp_validate_result_array = $validate_otp(
            $msisdn,
            $language,
            $rid,
            $pin,
            $campaign_group = null
        );
    } 
    catch (Exception $e)
    {
        $log->error("Error during otp validate api call",[
            'e'=>(string) $e,
        ]);
        echo json_encode([
            'isSuccess'=>false,
            'message'=>$language_array[$language]['otp']['validate']['server_error'],
        ]);
        exit;
        //~ EXIT
    }
    $log->info("Obtained result of otp validate",[
        'otp_send_result_array'=>$otp_validate_result_array
    ]);     
    //~ ~ Emit result json
    if (($otp_validate_result_array['isSuccess'] ?? false )) {
        echo json_encode([
            'success'=>true,
            'message'=>$language_array[$language]['otp']['validate']['success'],
        ]);
    }
    else {
        echo json_encode([
            'isSuccess'=>false,
            'message'=>$language_array[$language]['otp']['validate']['failure_pin'],
        ]);
    }
    exit;
}

$log->info("It is not a form request, processing as land");

$page_template = file_get_contents($views_dir.'/home.mustache');

//~ Detect header enrichment
$msisdn = $headers_array[$service_config_array['header_enrichment_key']] ?? null;
if (! empty($msisdn)){
    $log->info("Header has msisdn ", [
        'msisdn' => $msisdn
    ]);
}

//~ Detect Campaign
$campaign_group="groupC"; //Which is the default campaign.
foreach ($_GET as $query_key=>$query_value){
    if (isset($service_config_array['campaign'][$query_key])){
        $log->debug("Campaign key found in query params",[
            'query_key'=>$query_key,
        ]);
        if (isset($service_config_array['campaign'][$query_key][$query_value]))
        {
            $campaign_group = $service_config_array['campaign'][$query_key][$query_value];
            $log->info("Campaign key match a group value",[
                'campaign_group'=>$campaign_group,
            ]);
            break;
        }
    }
};

//~ Campaign actions
if ( $campaign_group=="groupA" ){
    $log->info("GroupA action will be taken");
    try{
        $otp_send_result_array = $send_otp(
            $msisdn,
            $language,
            $campaign_group
        );
    } catch(Exception $e){
        $log->error("Error during otp send api call",[
            'e'=>(string) $e,
        ]);
        echo $mustache->render(
            file_get_contents($views_dir.'/home.mustache'),
            $language_array[$language]
        );
        exit;
        //~ EXIT
    }
    $log->info("Obtained result of otp send, will sleep some",[
        'otp_send_result_array'=>$otp_send_result_array
    ]); 

    sleep($otp_config_array['pause']);

    try{
        $otp_validate_result_array = $validate_otp(
            $msisdn,
            $language,
            $campaign_group,
            $otp_send_result_array['rid'],
            $otp_send_result_array['pin']
        );
    } catch(Exception $e){
        $log->error("Error during otp validate api call",[
            'e'=>(string) $e,
        ]);
        echo $mustache->render(
            file_get_contents($views_dir.'/home.mustache'),
            $language_array[$language]
        );
        exit;
        //~ EXIT
    }
    $log->info("Obtained result of otp validate, adding succes popup into content",[
        'otp_validate_result_array'=>$otp_validate_result_array
    ]); 

    //TODO: Should we add page content here to display campaign welcome.
    echo $mustache->render(
        $page_template,
        array_merge($language_array[$language],['msisdn'=>$msisdn])
    );
    exit;
}
/* CURRENTLY NOT IMPLEMENTED BECAUSE NOT USED
elseif ($campaign_group=="groupB" )
{
    $log->info("GroupB action will be taken");


}
*/
else
{
    $log->info("GroupC action will be taken");
    //NOTE : For groupB and groupC we will fill the msisdn if we know already
    echo $mustache->render(
        $page_template,
        array_merge($language_array[$language],['msisdn'=>$msisdn])
    );
    exit;
    //~ EXIT
}



$log->error("I should not be here.");

//~ END

?>