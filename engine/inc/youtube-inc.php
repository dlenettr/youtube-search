<?php
/*
=====================================================
 MWS Youtube Search v1.1 - by MaRZoCHi
-----------------------------------------------------
 Site: http://dle.net.tr/
-----------------------------------------------------
 Copyright (c) 2015
-----------------------------------------------------
 Lisans: GPL License
=====================================================
*/

if( !defined( 'DATALIFEENGINE' ) OR !defined( 'LOGGED_IN' ) ) { die( "Hacking attempt!" ); }
if( $member_id['user_group'] != 1 ) { msg( "error", $lang['index_denied'], $lang['index_denied'] ); }

$conf_file  = ENGINE_DIR ."/data/youtube.conf.php";
require_once ENGINE_DIR . "/data/config.php";
require_once ENGINE_DIR . "/classes/mysql.php";
require_once ENGINE_DIR . "/data/dbconfig.php";
require_once ($conf_file);

$MNAME = "youtube-inc";

function en_serialize( $value ) { return str_replace( '"', "'", serialize( $value ) ); }
function de_serialize( $value ) { return unserialize( str_replace("'", '"', $value ) ); }
function showRow( $title = "", $description = "", $field = "", $id = false, $hide = "1") { echo "<tr><td class=\"col-xs-10 col-sm-6 col-md-7\"><h6>{$title}</h6><span class=\"note large\">{$description}</span></td><td class=\"col-xs-2 col-md-5 settingstd{$_cl}\">{$field}</td></tr>"; }
function makeDropDown($options, $name, $selected, $id = false, $hide = "1") {
	$id = ( $id == false ) ? "" : " id=\"" . $id . "\"";
	$style = ( $hide == "0" ) ? " style=\"display:none\"": "";
	$output = "<select{$id}{$style} class=\"uniform\" name=\"{$name}\">\r\n";
	foreach ( $options as $value => $description ) {
		$output .= "<option value=\"{$value}\"";
		if ( $selected == $value ) { $output .= " selected "; }
		$output .= ">{$description}</option>\n";
	}
	$output .= "</select>";
	return $output;
}
function makeButton( $name, $selected ) {
	$checked = ( $selected == "1" ) ? " checked=\"checked\"" : "";
	return "<input class=\"iButton-icons-tab\" type=\"checkbox\"{$checked} name=\"{$name}\" id=\"{$name}\" value=\"1\" />";
}

if ( $_REQUEST['action'] == "save" ) {
	if ( $member_id['user_group'] != 1 ) { msg( "error", $lang['opt_denied'], $lang['opt_denied'] ); }
	if ( $_REQUEST['user_hash'] == "" or $_REQUEST['user_hash'] != $dle_login_hash ) { die( "Hacking attempt! User not found" ); }

	$save_con = $_POST['save'];
	$save_con['player_active'] = intval($save_con['player_active']);
	$save_con['thumbs_active'] = intval($save_con['thumbs_active']);
	$save_con['thumbs_download'] = intval($save_con['thumbs_download']);
	$save_con['use_sstory'] = intval($save_con['use_sstory']);
	$save_con['use_title'] = intval($save_con['use_title']);

	$find = array(); $replace = array();
	$find[] = "'\r'"; $replace[] = "";
	$find[] = "'\n'"; $replace[] = "";

	$save_con = $save_con + $yt_config;
	$handler = fopen( ENGINE_DIR . '/data/youtube.conf.php', "w" );

	fwrite( $handler, "<?PHP \n\n//MWS Youtube Search\n\n\$yt_config = array (\n" );
	foreach ( $save_con as $name => $value ) {
		$value = ( is_array( $value ) ) ? implode(",", $value ) : $value;
		$value = trim(strip_tags(stripslashes( $value )));
		$value = htmlspecialchars( $value, ENT_QUOTES, $config['charset']);
		$value = preg_replace( $find, $replace, $value );
		$name = trim(strip_tags(stripslashes( $name )));
		$name = htmlspecialchars( $name, ENT_QUOTES, $config['charset'] );
		$name = preg_replace( $find, $replace, $name );
		$value = str_replace( "$", "&#036;", $value );
		$value = str_replace( "{", "&#123;", $value );
		$value = str_replace( "}", "&#125;", $value );
		$value = str_replace( ".", "", $value );
		//$value = str_replace( '/', "", $value );
		$value = str_replace( chr(92), "", $value );
		$value = str_replace( chr(0), "", $value );
		$value = str_replace( '(', "", $value );
		$value = str_replace( ')', "", $value );
		$value = str_ireplace( "base64_decode", "base64_dec&#111;de", $value );
		$name = str_replace( "$", "&#036;", $name );
		$name = str_replace( "{", "&#123;", $name );
		$name = str_replace( "}", "&#125;", $name );
		$name = str_replace( ".", "", $name );
		$name = str_replace( '/', "", $name );
		$name = str_replace( chr(92), "", $name );
		$name = str_replace( chr(0), "", $name );
		$name = str_replace( '(', "", $name );
		$name = str_replace( ')', "", $name );
		$name = str_ireplace( "base64_decode", "base64_dec&#111;de", $name );
		fwrite( $handler, "'{$name}' => '{$value}',\n" );
	}
	fwrite( $handler, ");\n\n?>" );
	fclose( $handler );

	msg( "info", $lang['opt_sysok'], $lang['opt_sysok_1'], "{$PHP_SELF}?mod=youtube-inc" );

} else {

	echoheader( "<i class=\"icon-film\"></i> Youtube Search v1.1", "Youtube'dan video bilgileri çekebilirsiniz" );

	foreach( xfieldsload() as $xarr) {
		$xfields[$xarr[0]] = $xarr[1] . "\t(". $xarr[0] . ")";
	}

	echo <<< HTML
<form action="{$PHP_SELF}?mod=youtube-inc&action=save" name="conf" id="conf" method="post">
<div class="box">
	<div class="box-header">
		<div class="title">Sistem Ayarları</div>
		<ul class="box-toolbar">
			<li class="toolbar-link">
			</li>
		</ul>
	</div>
	<div class="box-content">
	<table class="table table-normal">
HTML;

	showRow(
		"Video Önizlemeleri",
		"Video önizlemelerini indirmeyi veya linklerini kullanmayı düşünüyorsanız bu ayarı aktifleştirin",
		makeDropDown( array( "1" => "Önizlemeleri Kullan", "0" => "Önizlemeleri Kullanma" ),"save[thumbs_active]", $yt_config['thumbs_active'], "thumbs_active" ) . "&nbsp;&nbsp;" . makeDropDown( array( "1" => "Sunucuya İndir", "0" => "Linkleri Kullan" ),"save[thumbs_download]", $yt_config['thumbs_download'], "thumbs_download", $yt_config['thumbs_active'] )
	);

	showRow(
		"Video Önizlemelerinin İndirileceği Dizin", "<font color='red'>Buraya girdiğinizle aynı isimle <b>uploads/</b> klasörü içinde klasör oluşturmalısınız. ( CHMOD 777 olmalı ve <b>/</b> ile bitmelidir. )</font>",
		"<input name=\"save[video_thmbdir]\" value=\"{$yt_config['video_thmbdir']}\" size=\"20\" type=\"text\" style=\"text-align: center;\" />",
		"video_thmbdir", $yt_config['thumbs_download']
	);

	showRow(
		"Video'yu izlemek için Player'ı aktifleştir",
		"Videolar listelendiğinde aradığınız videonun hangisi olduğunu bulmak için player yardımıyla izleyebilirsiniz.",
		makeDropDown( array( "1" => "Evet", "0" => "Hayır" ), "save[player_active]", $yt_config['player_active'], "player_active" )
	);

	showRow(
		"Video Player Boyutları",
		"Player için belirleyebileceğiniz genişlik ve yükseklik. <b>Bu sadece admin panelinde videoyu izlerken kullanılacaktır. Siteye eklediğinizdeki boyutlar ile ilgisi yoktur.</b>",
		"<input name=\"save[player_width]\" value=\"{$yt_config['player_width']}\" size=\"3\" type=\"text\" style=\"text-align: center;\" />&nbsp;x&nbsp;<input name=\"save[player_height]\" value=\"{$yt_config['player_height']}\" size=\"3\" type=\"text\" style=\"text-align: center;\" />&nbsp;&nbsp;",
		"player_dimen", $yt_config['player_active']
	);

	showRow(
		"Başlık Alanı", "Video başlığını makale başlığı olarak kullan",
		makeButton( "save[use_title]", $yt_config['use_title'] )
	);
	showRow(
		"Açıklama Alanı", "Video açıklamasını makale açıklaması olarak kullan",
		makeButton( "save[use_sstory]", $yt_config['use_sstory'] )
	);

	showRow(
		"Video Servisi", "Bu alana Youtube değeri girilecek, eğer başka servislerden de video ekliyorsanız bu şekilde ayırt edebilirsiniz.",
		makeDropDown( $xfields, "save[video_ser]", $yt_config['video_ser'] )
	);

	showRow(
		"Video ID", "Sadece video ID'si v=(ID)",
		makeDropDown( $xfields, "save[video_id]", $yt_config['video_id'] )
	);

	showRow(
		"Video URL", "Video'nun tam linki",
		makeDropDown( $xfields, "save[video_url]", $yt_config['video_url'] )
	);

	showRow(
		"Video URL (Mobil)", "Eğer varsa mobil uyumlu video linki (Düşük kalite). Mobil tema için bu alanı kullanabilirsiniz.",
		makeDropDown( $xfields, "save[video_murl]", $yt_config['video_murl'] )
	);

	showRow(
		"Video Sahibi", "Video'yu yükleyen bilgisi (Yazı olarak)",
		makeDropDown( $xfields, "save[video_upl]", $yt_config['video_upl'] )
	);

	showRow(
		"Video Önizlemesi", "Video'nun ana önizlemesi",
		makeDropDown( $xfields, "save[video_thmb]", $yt_config['video_thmb'] )
	);

	showRow(
		"Video Önizlemeleri", "Her video için ana önizlemesinden başka 3 adet baştan, ortadan ve sondan alınmış önizleme bulunur.<br />Her biri için bir ilave alan oluşturun.Sırayla thumb1, thumb2, thumb3 bu alanlar için buraya sadece <b>thumb</b> değerini girin",
		"<input name=\"save[video_thmbx]\" value=\"{$yt_config['video_thmbx']}\" size=\"20\" type=\"text\" style=\"text-align: center;\" />",
		"video_thmbx", $yt_config['thumbs_active']
	);

echo <<<HTML
	</table></div></div>
	<div style="margin-bottom:30px;">
		<input type="hidden" name="user_hash" value="{$dle_login_hash}" />
		<input type="submit" class="btn btn-green" value="{$lang['user_save']}">
	</div>
</form>
HTML;


}
echofooter();
?>