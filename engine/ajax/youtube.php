<?php
/*
=====================================================
 MWS Youtube Search v1.2 - by MaRZoCHi
-----------------------------------------------------
 Site: http://dle.net.tr/
-----------------------------------------------------
 Copyright (c) 2015
-----------------------------------------------------
 Lisans: GPL License
=====================================================
*/

@error_reporting ( E_ALL ^ E_WARNING ^ E_NOTICE );
@ini_set ( 'display_errors', true );
@ini_set ( 'html_errors', false );
@ini_set ( 'error_reporting', E_ALL ^ E_WARNING ^ E_NOTICE );

define( 'DATALIFEENGINE', true );
define( 'ROOT_DIR', substr( dirname(  __FILE__ ), 0, -12 ) );
define( 'ENGINE_DIR', ROOT_DIR . '/engine' );
define( 'UPLOAD_DIR', ROOT_DIR . "/uploads/" );

include ENGINE_DIR . '/data/config.php';
include ENGINE_DIR . '/data/youtube.conf.php';

date_default_timezone_set ( $config['date_adjust'] );

$_TIME = time();

if ( $config['http_home_url'] == "" ) {
	$config['http_home_url'] = explode( "engine/ajax/youtube.php", $_SERVER['PHP_SELF'] );
	$config['http_home_url'] = reset( $config['http_home_url'] );
	$config['http_home_url'] = "http://" . $_SERVER['HTTP_HOST'] . $config['http_home_url'];
}

require_once ENGINE_DIR . '/classes/mysql.php';
require_once ENGINE_DIR . '/data/dbconfig.php';
require_once ENGINE_DIR . '/modules/functions.php';
require_once ENGINE_DIR . '/classes/templates.class.php';
require_once ENGINE_DIR . '/modules/sitelogin.php';

$_IP = get_ip();
dle_session();

$yt_config['write_db'] = "0";

if ( ! $is_logged ) die( "Hacking attempt!" );

function getURLContent( $url ) {
	if ( function_exists('curl_exec') ) {
		$ch = curl_init( $url );
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_ENCODING, "");
		curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 6.2; en-US; rv:1.8.1.15) Gecko/2008111317 Firefox/3.0.4");
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 120);
		curl_setopt($ch, CURLOPT_TIMEOUT, 120);
		curl_setopt($ch, CURLOPT_MAXREDIRS, 1);
		$output  = curl_exec( $ch );
		curl_close( $ch );
	} else echo "curl error";
	return $output;
}

function objectToArray( $d ) {
	if (is_object($d)) { $d = get_object_vars($d); }
	if (is_array($d)) { return array_map(__FUNCTION__, $d); }
	else { return $d; }
}

function download_img( $url ) {
	global $db, $config, $member_id, $_TIME, $yt_config;

	if ( function_exists('curl_exec') ) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 120);
		curl_setopt($ch, CURLOPT_TIMEOUT, 120);
		curl_setopt($ch, CURLOPT_MAXREDIRS, 1);
		$output = curl_exec($ch);
		curl_close($ch);
	} else {
		$output = file_get_contents($url);
	}
	$save = $yt_config['video_thmbdir'] . md5( $url ) . ".jpg";
	$fp = fopen( UPLOAD_DIR . $save, "w") ; fwrite($fp, $output) ; fclose($fp); unset( $fp, $output );

	if ( $yt_config['write_db'] ) {
		$id = $db->super_query("SELECT MAX(id) as max FROM " . PREFIX . "_post"); $id['max']++; $db->free();
		$id_max = $id['max'];
		$_save = str_replace("posts/", "", $save);
		$db->query("INSERT INTO " . PREFIX . "_images (images, news_id, author, date) VALUES ('{$_save}', '{$id_max}', '{$member_id['name']}', '{$_TIME}')");
		// resize & watermark
		unset( $id, $id_max, $_save );
	}

	return $config['http_home_url'] . "uploads/" . $save . "\n";
}


if ( $_POST['action'] == "search" ) {
	if ( isset( $_POST['query'] ) && isset( $_POST['result'] ) ) {
		$query = urlencode( $db->safesql( trim( $_POST['query'] ) ) );
		$set_maxresult = strval( $db->safesql( $_POST['result'] ) );
		$url = "https://www.googleapis.com/youtube/v3/search?q=" . $query . "&key=AIzaSyAfY8bbWm_8ZgV0HNaa51NDzxg6nmhyLI4&part=snippet&maxResults={$set_maxresult}&fields=items(id%2Csnippet)";
		$json = getURLContent( $url );
		$result = objectToArray( json_decode( $json ) );
		$results = array();
		$results['results'] = "<div id=\"video_results\"><ul>";
		$results['error'] = "";
		if ( $result ) {
			foreach( $result['items'] as $item ) {
				$video = array(
					'title'		=> $item['snippet']['title'],
					'link'		=> "https://www.youtube.com/watch?v=" . $item['id']['videoId'],
					//'mlink'		=> $item['link']['3']['href'],
					'date'		=> $item['snippet']['publishedAt'],
					'thumb'		=> $item['snippet']['thumbnails']['high']['url'],
					//'t1'		=> $item['media$group']['media$thumbnail']['1']['url'],
					//'t2'		=> $item['media$group']['media$thumbnail']['2']['url'],
					//'t3'		=> $item['media$group']['media$thumbnail']['3']['url'],
					//'s1'		=> $item['media$group']['media$thumbnail']['1']['time'],
					//'s2'		=> $item['media$group']['media$thumbnail']['2']['time'],
					//'s3'		=> $item['media$group']['media$thumbnail']['3']['time'],
					//'length'	=> $item['media$group']['yt$duration']['seconds'],
					'author'	=> $item['snippet']['channelTitle'],
					'desc'		=> $item['snippet']['description'],
					'id'		=> $item['id']['videoId'],
				);
				$video['link'] = str_replace( "&feature=youtube_gdata_player", "", $video['link'] );
				$video['title'] = str_replace( "\\", "", strip_tags( $video['title'] ) );
				$video['title'] = substr( $video['title'], 0, 90 );
					$_temp = explode(".", $video['s1']);
				$video['s1'] = $_temp[0];
					$_temp = explode(".", $video['s2']);
				$video['s2'] = $_temp[0];
					$_temp = explode(".", $video['s3']);
				$video['s3'] = $_temp[0];
					$_temp = explode( "T", $video['date'] );
				$video['date'] = $_temp[0]; unset( $_temp );
				//$video['desc'] = preg_replace( "/\<.*\>/", "", $video['desc'] );
				preg_match( "/v=([A-Za-z0-9-_]+)\&*/i", $video['link'], $_vid );
				$video['id'] = $_vid[1];
				$results['thumbs'][ $video['id'] ] = array( "img" => $video['thumb'], "t1" => $video['t1'], "s1" => $video['s1'], "t2" => $video['t2'], "s2" => $video['s2'], "t3" => $video['t3'], "s3" => $video['s3'] );
				$results['results'] .= <<< HTML

		<li url="{$video['link']}" murl="{$video['mlink']}" author="{$video['author']}" vid="{$video['id']}" duration="{$video['length']}">
			<img src="{$video['thumb']}" alt="" id="img-{$video['id']}" />
			<div><span>?</span><span>+</span></div>
			<h3>{$video['title']}</h3>
			<span class="desc">{$video['desc']}</span>
			<span class="author"><b>{$video['author']}</b> - {$video['date']}</span>
		</li>
HTML;
			}
		}
		$results['results'] .= "</ul></div>";
		echo json_encode( $results );
	} else json_encode( array( "error" => "Aranacak kelime veya sonuç sayısı belirtilmedi." ) );


} else if ( $_POST['action'] == "getinfo" ) {
	if ( isset( $_POST['url'] ) ) {

		$videoid = $db->safesql( trim( $_POST['url'] ) );
		preg_match( "/v=([A-Za-z0-9-_]+)\&*/i", $videoid, $_vid );
		$videoid = $_vid[1];
		$url = "http://gdata.youtube.com/feeds/api/videos/{$videoid}?v=2&prettyprint=true&alt=json" ;
		$json = getURLContent( $url );
		$result = objectToArray( json_decode( $json ) );
		$item = $result['entry'];
		$_tid = count( $item['media$group']['media$thumbnail'] );
		$video = array(
			'title'		=> $item['media$group']['media$title']['$t'],
			'link'		=> $item['media$group']['media$player']['url'],
			'mlink'		=> $item['link']['3']['href'],
			'date'		=> $item['published']['$t'],
			't1'		=> $item['media$group']['media$thumbnail'][$_tid-3]['url'],
			't2'		=> $item['media$group']['media$thumbnail'][$_tid-2]['url'],
			't3'		=> $item['media$group']['media$thumbnail'][$_tid-1]['url'],
			's1'		=> $item['media$group']['media$thumbnail'][$_tid-3]['time'],
			's2'		=> $item['media$group']['media$thumbnail'][$_tid-2]['time'],
			's3'		=> $item['media$group']['media$thumbnail'][$_tid-1]['time'],
			'length'	=> $item['media$group']['yt$duration']['seconds'],
			'author'	=> $item['author']['0']['name']['$t'],
			'desc'		=> $item['media$group']['media$description']['$t'],
			'id'		=> $item['media$group']['yt$videoid']['$t'],
		);
		$video['link'] = str_replace( "&feature=youtube_gdata_player", "", $video['link'] );
		$video['title'] = str_replace( "\\", "", strip_tags( $video['title'] ) );
		$video['title'] = substr( $video['title'], 0, 90 );
			$_temp = explode(".", $video['s1']);
		$video['s1'] = $_temp[0];
			$_temp = explode(".", $video['s2']);
		$video['s2'] = $_temp[0];
			$_temp = explode(".", $video['s3']);
		$video['s3'] = $_temp[0];
			$_temp = explode( "T", $video['date'] );
		$video['date'] = $_temp[0]; unset( $_temp );
		$results = array();
		$results['video'] = $video;
		echo json_encode( $results );
	} else json_encode( array( "error" => "Video adresi belirtilmemiş." ) );


} else if ( $_POST['action'] == "downthumb" ) {
	if ( isset( $_POST['source'] ) ) {
		echo download_img( $_POST['source'] );
	} else json_encode( array( "error" => "Thumbnail adresi belirtilmemiş." ) );
}

?>
