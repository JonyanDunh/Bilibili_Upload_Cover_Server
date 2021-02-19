<?php
function main_handler($event, $context) {
	$event = json_decode(json_encode($event), true);
	$context = json_decode(json_encode($context), true);
	unset($_GET);
	unset($_POST);
	$_GET = $event['queryString'];
	$_POSTbody = explode("&",$event['body']);
	foreach ($_POSTbody as $postvalues) {
		$pos = strpos($postvalues,"=");
		$_POST[urldecode(substr($postvalues,0,$pos))]=urldecode(substr($postvalues,$pos+1));
	}
	$path = '/tmp/';

	/*$key=crypt(md5(date("Y-m-d H")),md5("chenrui_bilibili_nmsl"));
	$get_key=$_GET["key"];
	if(strcmp($key,$get_key)!=0)
	{
		$errors=array("此页面已过期,请刷新!Key:".$get_key);
		return array(
            "isBase64Encoded" => false,
            "statusCode"=> 200,
            "headers"=> array(
                "Content-Type"=>"application/json"
                ),
            "body"=> decodeUnicode(json_encode($errors,true))
        );
	}*/


	$bvid = $_GET["bvid"];
	$sessdata = $_GET["sessdata"];
    $bili_jct = $_GET["bili_jct"];
	$result=array("Author"=>"哔哩哔哩@Jonyan_Dunh","Tips"=>"如果这个网站帮到了您,欢迎三连+关注~");
	$get_login_info_result=json_decode(get_login_info($sessdata),true);
	if($get_login_info_result["code"]!=0)
	{
		array_push($result,"您还未登录或Cookie有误，请再次检查!");
		return array(
            "isBase64Encoded" => false,
            "statusCode"=> 200,
            "headers"=> array(
                "Content-Type"=>"application/json"
                ),
            "body"=> decodeUnicode(json_encode($result,true))
            );
	}
	$jonyan=false;
	array_push($result,"欢迎您! ".$get_login_info_result["data"]["uname"]);
	if(strcmp($get_login_info_result["data"]["uname"],"Jonyan_Dunh")==0)
		{
			$jonyan=true;
		}
    if(!check_love($sessdata,$jonyan)&&$jonyan==false)
    {	
		$result=$result+array("是否关注UP@Jonyan_Dunh"=>"未关注,请前往关注再来使用!");
        return array(
            "isBase64Encoded" => false,
            "statusCode"=> 200,
            "headers"=> array(
                "Content-Type"=>"application/json"
                ),
            "body"=> decodeUnicode(json_encode($result,true))
            );
	}
	$result=$result+array("是否关注UP@Jonyan_Dunh"=>"已关注,可以使用!");
    $base64_image = $_POST['img_base64'];
	$img_path = get_base64_img($base64_image, $path);
	if (!$img_path) {
		$result=$result+array("图片处理结果"=>"图片处理失败!请检查图片是否有有误!");
		return array(
				    "isBase64Encoded" => false,
				    "statusCode"=> 200,
				    "headers"=> array(
				        "Content-Type"=>"application/json"
				        ),
				    "body"=> decodeUnicode(json_encode($result,true)),
				    );
	}
    ;
	$result=$result+array("图片处理结果"=>"图片处理成功!");
    $image_url;
    $update_img_result=json_decode(update_img($sessdata, $bili_jct, $img_path),true);
    if($update_img_result["code"]==0)
    $image_url=$update_img_result["data"]["image_url"];
    else
    {
		$result=$result+array("图床上传结果"=>$update_img_result["message"]);
        return array(
            "isBase64Encoded" => false,
            "statusCode"=> 200,
            "headers"=> array(
                "Content-Type"=>"application/json"
                ),
            "body"=> decodeUnicode(json_encode($result,true)),
            );
    }
	$result=$result+array("图床上传结果"=>"上传成功!");
	$info=get_info($sessdata, $bvid,$image_url);
    $update_cover_result=json_decode(update_cover($sessdata,$bili_jct,$info),200);
    if($update_cover_result["code"]!=0)
    {
		$result=$result+array("封面上传结果"=>"上传失败,请检查各数据是否准确!");
        return array(
            "isBase64Encoded" => false,
            "statusCode"=> 200,
            "headers"=> array(
                "Content-Type"=>"application/json"
                ),
            "body"=> decodeUnicode(json_encode($result,true)),
            );
    }
	$result=$result+array("封面上传结果"=>"上传成功,请去投稿页面查看!如果不能过审,那就帮不了你了~");
	return array(
        "isBase64Encoded" => false,
        "statusCode"=> 200,
        "headers"=> array(
            "Content-Type"=>"application/json"
            ),
        "body"=> decodeUnicode(json_encode($result,true)),
        );
}
function get_login_info($sessdata)
{

	$curl = curl_init();

curl_setopt_array($curl, array(
  CURLOPT_URL => 'http://api.bilibili.com/x/web-interface/nav',
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => '',
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 0,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => 'GET',
  CURLOPT_HTTPHEADER => array(
    'Cookie: SESSDATA='.$sessdata
  ),
));
$response = curl_exec($curl);
curl_close($curl);
return $response;
}
function get_base64_img($base64, $path) {
	if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $base64, $result)) {
		$type = $result[2];
		$new_file = $path . time() . ".{$type}";
		if (file_put_contents($new_file, base64_decode(str_replace($result[1], '', $base64)))) {
			return $new_file;
		} else {
			return  false;
		}
	}
}
function update_img($sessdata, $bili_jct, $img_path) {
	$curl = curl_init();
	curl_setopt_array($curl, array(
		        CURLOPT_URL => 'https://api.vc.bilibili.com/api/v1/drawImage/upload',
		        CURLOPT_RETURNTRANSFER => true,
		        CURLOPT_ENCODING => '',
		        CURLOPT_MAXREDIRS => 10,
		        CURLOPT_TIMEOUT => 0,
		        CURLOPT_FOLLOWLOCATION => true,
		        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		        CURLOPT_CUSTOMREQUEST => 'POST',
		        CURLOPT_POSTFIELDS => array('file_up' => new CURLFILE($img_path), 'biz' => 'draw', 'category' => 'daily', 'build' => '0', 'mobi_app' => 'web'),
		        CURLOPT_HTTPHEADER => array(
		            'Cookie: SESSDATA=' . $sessdata . '; bili_jct=' . $bili_jct . '; '
		        ),
		    ));
	$response = curl_exec($curl);
	curl_close($curl);
	return $response;
}
function get_info($sessdata, $bvid,$image_url) {
	$curl = curl_init();
	curl_setopt_array($curl, array(
	      CURLOPT_URL => 'https://member.bilibili.com/x/web/archive/view?bvid='.$bvid,
	      CURLOPT_RETURNTRANSFER => true,
	      CURLOPT_ENCODING => '',
	      CURLOPT_MAXREDIRS => 10,
	      CURLOPT_TIMEOUT => 0,
	      CURLOPT_FOLLOWLOCATION => true,
	      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	      CURLOPT_CUSTOMREQUEST => 'GET',
	      CURLOPT_HTTPHEADER => array(
	        'Cookie: SESSDATA='.$sessdata.';'
	      ),
	    ));
	$response = curl_exec($curl);
	curl_close($curl);
	echo $response;
	$info_json=json_decode($response,true)["data"];
	$info = array(
	        "copyright"=> $info_json["archive"]["copyright"],
	        "videos"=> $info_json["videos"],
	        "no_reprint"=>$info_json["archive"]["no_reprint"],
	        "interactive"=> $info_json["archive"]["interactive"],
	        "tid"=> $info_json["archive"]["tid"],
	        "cover"=> $image_url,
	        "title"=> $info_json["archive"]["title"],
	        "tag"=> $info_json["archive"]["tag"],
	        "desc_format_id"=> $info_json["archive"]["desc_format_id"],
	        "desc"=> $info_json["archive"]["desc"],
	        "dynamic"=> $info_json["archive"]["dynamic"],
	        "aid"=> $info_json["archive"]["aid"]
	    );
	return decodeUnicode(json_encode($info,true));
}
function decodeUnicode($str) {
	return preg_replace_callback('/\\\\u([0-9a-f]{4})/i',
	        create_function(
	            '$matches',
	            'return mb_convert_encoding(pack("H*", $matches[1]), "UTF-8", "UCS-2BE");'
	        ),
	        $str);
}
function update_cover($sessdata,$bili_jct,$info) {
	$curl = curl_init();
	curl_setopt_array($curl, array(
	  CURLOPT_URL => 'https://member.bilibili.com/x/vu/web/edit?csrf='.$bili_jct,
	  CURLOPT_RETURNTRANSFER => true,
	  CURLOPT_ENCODING => '',
	  CURLOPT_MAXREDIRS => 10,
	  CURLOPT_TIMEOUT => 0,
	  CURLOPT_FOLLOWLOCATION => true,
	  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	  CURLOPT_CUSTOMREQUEST => 'POST',
	  CURLOPT_POSTFIELDS =>$info,
	  CURLOPT_HTTPHEADER => array(
	    'Cookie: SESSDATA='.$sessdata.';',
	    'Content-Type: application/json'
	  ),
	));
	$response = curl_exec($curl);
	curl_close($curl);
	return $response;
}
function check_love($sessdata)
{
$curl = curl_init();

curl_setopt_array($curl, array(
  CURLOPT_URL => 'http://api.bilibili.com/x/relation?fid=96876893',
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => '',
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 0,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => 'GET',
  CURLOPT_HTTPHEADER => array(
    'Cookie: SESSDATA='.$sessdata
  ),
));

$response = curl_exec($curl);

curl_close($curl);
$love_staus=json_decode($response,true)["data"]["attribute"];
if($love_staus==0){
    return false;}
elseif($love_staus==2||$love_staus==6){
    return true;
}
}
?>