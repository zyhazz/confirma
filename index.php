<?php
require_once 'simple_html_dom.php';

$mainURL = "https://intranet.maracanau.ifce.edu.br/old/public/";

$cardapioURL = "https://intranet.maracanau.ifce.edu.br/old/public/ifce/ra/refeicao/filtrar";

$confirmarURL = "https://intranet.maracanau.ifce.edu.br/old/public/ifce/ra/refeicao/pedido";

$date = new DateTime(null, new DateTimeZone('America/Fortaleza'));

function req($url, $header = null, $post_data = null){
	$curl = curl_init();
	// debug
	//curl_setopt($curl, CURLOPT_PROXY, "127.0.0.1:8888");

	curl_setopt($curl, CURLOPT_URL, $url);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_HEADER, false);
	
	if(!is_null($header)){
		curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
	}
	if(!is_null($post_data)){
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
	}
	
	$tmp = "/tmp/cookie.txt";
	curl_setopt($curl, CURLOPT_COOKIEFILE, $tmp );
	curl_setopt($curl, CURLOPT_COOKIEJAR, $tmp );
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
	$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
	$data = curl_exec($curl);
	curl_close($curl);
	return $data;
}
?>


<html>

<head>
    <meta
	content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no"
	name="viewport">
    <meta charset="utf-8">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link href="main.css" rel="stylesheet" />
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>
    <script src="itemslide.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-mousewheel/3.1.12/jquery.mousewheel.min.js"></script>
    <script src="sliding.js"></script>
</head>
<body>

<center>
    <div id="bg">
<?php

if(isset($_GET['id']) || isset($_GET['identificacao'])){
	$identificacao = isset($_GET['id'])?$_GET['id']:$_GET['identificacao'];
	if($identificacao == "matricula"){
		header("Location: http://confirma.tk");
		die();
	}
	file_put_contents("logs/log.txt", $identificacao . "|" .date_format($date, 'Y-m-d H:i:s'). "|" .$_SERVER['REMOTE_ADDR'] . PHP_EOL, FILE_APPEND);

	$data = req($mainURL);

	$html = new simple_html_dom();
	$html->load($data);

	$token = $html->find('input[name=_token]', 0)->value;


	for($i = 0; $i < 5; $i++){

		$header = array('X-CSRF-TOKEN: ' . $token);
		$post_data = array(
		    "data" => date_format($date, 'Y-m-d')
		);

		echo date_format($date, 'Y-m-d')." ";

		$json = req($cardapioURL, $header, $post_data);
		
		$json = substr($json, 1, -1);
		$json = str_replace("\\", "", $json);
		
		$json = json_decode("{\"data\":$json}");

		if(isset($json->data)){
			$post_data = array(
			    "data" => date_format($date, 'Y-m-d'),
			    "_token" => $token,
			    "refeicao" => $json->data[0]->id,
				"identificacao" => $identificacao
			);

			$data = req($confirmarURL, $header, $post_data);

			$html->load($data);
			if(isset($html->find("span[id=aviso-msg]",0)->innertext)){
				echo trim($html->find("span[id=aviso-msg]",0)->innertext);
			}
		}else{
			echo "dia sem cardapio, pulando";
		}
		echo "<br>";

		$date->add(new DateInterval('P1D'));//period 1 day
		
	}
}else{
	?>
	<h2>Use por sua conta e risco</h2>
	<form>
	  Matricula:<br>
	  <input type="number" name="identificacao">
	  <input type="submit" value="Confirmar próximos 5 dias">
	</form>
	<?php
}

?>
<a class="github-button" href="https://github.com/zyhazz/confirma" data-size="large" aria-label="Star zyhazz/confirma on GitHub">Github</a>
</div>
<script async defer src="https://buttons.github.io/buttons.js"></script>
<body>
<html>
