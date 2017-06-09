#!/alidata/server/php-5.5.7/bin/php -q
<?php
   $path = '/alidata/www/weiphp/Application/Common/Cron/';
    $name = date('ymd').'.json';
    if(file_exists($path.$name)){
        $data = file_get_contents($path.$name);
        $messagelist = explode('@@', $data);
        $status = true;
        foreach ($messagelist as $key => $value) {
            if(!empty($value)){
                $message = json_decode($value,TRUE );
                if($message['type'] == 'tag'){
                    $url = $message['url'];
                    $param = $message['conent'];
                }elseif($message['type'] == 'openid'){
                   $url = $message['url'];
                     $param = $message['conent'];
                }
                $res = post_data ( $url, $param );
                if ($res ['errcode'] != 0) {
                       $status = false;
                } 
            }                    
        }
        if($status){
            //全部成功,删除文件
            unlink($path.$name);
        }
    }       
   // 以GET方式获取数据，替代file_get_contents
function get_data($url, $timeout = 5){
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
    $file_contents = curl_exec($ch);
    curl_close($ch);
    return $file_contents;
}
// 以POST方式提交数据
function post_data($url, $param, $is_file = false, $return_array = true) {
	set_time_limit ( 0 );
	if (! $is_file && is_array ( $param )) {
		$param = JSON ( $param );
	}
	if ($is_file) {
		$header [] = "content-type: multipart/form-data; charset=UTF-8";
	} else {
		$header [] = "content-type: application/json; charset=UTF-8";
	}
	$ch = curl_init ();
	if (class_exists ( '/CURLFile' )) { // php5.5跟php5.6中的CURLOPT_SAFE_UPLOAD的默认值不同
		curl_setopt ( $ch, CURLOPT_SAFE_UPLOAD, true );
	} else {
		if (defined ( 'CURLOPT_SAFE_UPLOAD' )) {
			curl_setopt ( $ch, CURLOPT_SAFE_UPLOAD, false );
		}
	}
	curl_setopt ( $ch, CURLOPT_URL, $url );
	curl_setopt ( $ch, CURLOPT_CUSTOMREQUEST, "POST" );
	curl_setopt ( $ch, CURLOPT_SSL_VERIFYPEER, FALSE );
	curl_setopt ( $ch, CURLOPT_SSL_VERIFYHOST, FALSE );
	curl_setopt ( $ch, CURLOPT_HTTPHEADER, $header );
	curl_setopt ( $ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)' );
	curl_setopt ( $ch, CURLOPT_FOLLOWLOCATION, 1 );
	curl_setopt ( $ch, CURLOPT_AUTOREFERER, 1 );
	curl_setopt ( $ch, CURLOPT_POSTFIELDS, $param );
	curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, true );
	$res = curl_exec ( $ch );
	$flat = curl_errno ( $ch );
	if ($flat) {
		$data = curl_error ( $ch );
		
	}
	
	curl_close ( $ch );
	
	$return_array && $res = json_decode ( $res, true );
	
	return $res;
}
?>

