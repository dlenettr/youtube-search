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
require_once ($conf_file);

if ( $yt_config['thumbs_active'] ) {
	$thumbs_active = <<< HTML
	setTimeout( function(){
		//$("html, body").animate( { scrollTop: 0 }, '500', function() {
			var thumb = data.thumbs;
			$("#video_results").animate({ height: "0px"}).hide('fast', function(){
				$("#video_result").html( '<div><img src="' + thumb[video_id]['t1'] + '" rel="click" /><p><span>' + thumb[video_id]['s1'] + '</p></span></div><div><img src="' + thumb[video_id]['t2'] + '" rel="click" /><p><span>' + thumb[video_id]['s2'] + '</p></span></div><div><img src="' + thumb[video_id]['t3'] + '" rel="click" /><p><span>' + thumb[video_id]['s3'] + '</p></span></div><div class="closebtn"><input type="button" class="btn btn-sm btn-black" value="Kapat" onclick="javascript:$(\'#video_result\').slideUp();" /></div>' ).show().animate({ height: "140px"});
			});
		//});
	}, 1500);
HTML;

	if ( $yt_config['thumbs_download'] ) {
		$thumbs_process = <<< HTML
						ShowLoading('Resim İndiriliyor...');
						$.post("engine/ajax/youtube.php", { action: 'downthumb', source: img_src }, function(tdata) {
							$("input[id='xf_{$yt_config['video_thmbx']}" + count + "']").val( tdata );
						}).done( function() {
							HideLoading();
							img.removeAttr('rel');
							if ( img.parents().get(1).className != "ok" ) {
								img.parent("div").wrap("<div class=\"ok\"></div>");
								img.parent().append("<div>" + count + "<div>");
								count++;
							}
							if ( count == 4 ) {
								$("#video_result").slideUp(500, function() {
									$("#video_result").remove();
									$("#related_news").html('');
								});
								count = 1;
							}
						});
HTML;

		$thumb_process = <<< HTML
			$("img[rel='click']").on('click', "#video_result", function() {
				var img = $(this);
				console.log( img );
				var img_src = img.attr("src");
				ShowLoading('Resim İndiriliyor...');
				$.post("engine/ajax/youtube.php", { action: 'downthumb', source: img_src }, function(tdata) {
					$("input[id='xf_{$yt_config['video_thmbx']}" + count + "']").val( tdata );
				}).done( function() {
					HideLoading();
					img.removeAttr('rel');
					if ( img.parents().get(1).className != "ok" ) {
						img.parent().wrap("<div class=\"ok\"></div>");
						img.parent().append("<div>" + count + "<div>");
						count++;
					}
					if ( count == 4 ) {
						$("#video_result").slideUp(500, function() {
							$("#video_result").remove();
							$("#related_news").html('');
						});
						count = 1;
					}
				});
			});
HTML;

	} else {
		$thumbs_process = <<< HTML
						ShowLoading('Resim Bilgisi Ekleniyor...');
						$("input[id='xf_{$yt_config['video_thmbx']}" + count + "']").val( img_src );
						img.removeAttr('rel');
						if ( img.parents().get(1).className != "ok" ) {
							img.parent().wrap("<div class=\"ok\"></div>");
							img.parent().append("<div>" + count + "<div>");
							count++;
						}
						if ( count == 4 ) {
							$("#video_result").slideUp(500, function() {
								$("#video_result").remove();
								$("#related_news").html('');
							});
							count = 1;
						}
						HideLoading();
HTML;
		$thumb_process = <<< HTML
			$("img[rel='click']").on('click', "#video_result", function() {
				var img = $(this);
				console.log( img );
				var img_src = img.attr("src");
				ShowLoading('Resim Bilgisi Ekleniyor...');
				$("input[id='xf_{$yt_config['video_thmbx']}" + count + "']").val( tdata );
				img.removeAttr('rel');
				if ( img.parents().get(1).className != "ok" ) {
					img.parent().wrap("<div class=\"ok\"></div>");
					img.parent().append("<div>" + count + "<div>");
					count++;
				}
				if ( count == 4 ) {
					$("#video_result").slideUp(500, function() {
						$("#video_result").remove();
						$("#related_news").html('');
					});
					count = 1;
				}
				HideLoading();
			});
HTML;

	}

} else {
	$thumbs_active = "";
	$thumbs_process = "";
}


if ( $yt_config['player_active'] ) {
	$d_width = $yt_config['player_width'] + 40;
	$d_height = $yt_config['player_height'] + 110;

	$player_active = <<< HTML
					$("#video_results ul li div span:first-child").click(function() {
						var selected = $(this).parent("div").parent("li");
						var url = selected.attr("url");
						var vid = selected.attr("id");
						var vtitle = selected.find("h3").html();
						$("body").append("<div id=\"pplayer\" align=\"center\" title=\"" + vtitle + "\"><iframe width=\"{$yt_config['player_width']}\" height=\"{$yt_config['player_height']}\" src=\"http://www.youtube.com/embed/" + vid + "?vq=hd720&rel=0\" frameborder=\"0\" allowfullscreen></iframe></div>");
						$("#pplayer").dialog({ height: {$d_height}, width: {$d_width}, modal: true, buttons: { Ok: function() { $(this).dialog( "close" ); $("#pplayer").hide().remove(); } } });
					});
HTML;
} else {
	$player_active = <<< HTML
					$("#video_results ul li div span:first-child").hide();
HTML;
}

$_use_title = ( $yt_config['use_title'] ) ? "$('#title').val( video_title );" : "";
$_use_sstory = ( $yt_config['use_sstory'] ) ? "$('#short_story').html( video_desc );" : "";

$video_fields = <<< HTML
								$("input[id='xf_{$yt_config['video_ser']}']").val( "Youtube" );
								$("input[id='xf_{$yt_config['video_id']}']").val( video_id );
								$("input[id='xf_{$yt_config['video_url']}']").val( video_url );
								$("input[id='xf_{$yt_config['video_murl']}']").val( video_murl );
								$("input[id='xf_{$yt_config['video_upl']}']").val( video_author );
								$("input[id='xf_{$yt_config['video_dur']}']").val( video_duration );
								$("input[id='xf_{$yt_config['video_thmb']}']").val( video_thumb );
								{$_use_sstory}
								{$_use_title}
HTML;

$_use_title = ( $yt_config['use_title'] ) ? "$('#title').val( ntitle );" : "";
$_use_sstory = ( $yt_config['use_sstory'] ) ? "$('#short_story').html( data.video.desc );" : "";

$video_field = <<< HTML
				{$_use_sstory}
				{$_use_title}
				$("input[id='xf_{$yt_config['video_url']}']").val( data.video.link );
				$("input[id='xf_{$yt_config['video_murl']}']").val( data.video.mlink );
				$("input[id='xf_{$yt_config['video_upl']}']").val( data.video.author );
				$("input[id='xf_{$yt_config['video_dur']}']").val( data.video.length );
				$("input[id='xf_{$yt_config['video_id']}']").val( data.video.id );
				$("input[id='xf_{$yt_config['video_ser']}']").val( "Youtube" );
HTML;

echo <<< HTML
<style>
#video_result { height: 10px; width: 100%; background: #FFF; border:1px solid #E2E2E2; padding: 5px; margin: 5px 0; }
#video_result div { float: left; margin: 5px; }
#video_result div img { border: 2px solid #e2e2e2; opacity: 0.6; border-radius: 4px; }
#video_result div img:hover { border-color: #444; cursor: pointer; opacity: 1.0; transition: 0.4s; }
#video_result div p { background-color: #222; border-radius: 5px; color: #fff; font-size: 10px; padding: 4px; text-align: center; margin-top: 2px; }
#video_result .ok { background-color: #000; opacity: 1.0; z-index: 299; height: 125px; border-radius: 5px; margin: 0 5px 0px 5px;  }
#video_result .ok div img:hover { cursor: default; transition: 0.4s; }
#video_result .ok div img { z-index: 300; }
#video_result .ok div div { color: #fff; font-size: 67px; z-index: 9999; font-family: 'Ubuntu', sans-serif; margin-top: -120px; margin-left: 27px; text-align: center; }
#video_results { width: 100%; height: auto; background: #FFF; border: 1px solid #E2E2E2; padding: 0px; margin: 5px 0; }
#video_results ul { list-style: none; margin: 0px; padding: 0px; height: auto; }
#video_results ul li { color: #555; padding: 5px; height: 100px; border: 1px solid #e2e2e2; width: 49.5%; margin: 0.2%; float: left; overflow: hidden; }
#video_results ul li:hover { background: #717171; color: #fff; cursor: default; transition: 0.4s; }
#video_results ul li:hover > h3 { color: #eee; }
#video_results ul li:hover > .desc { color: #ddd; }
#video_results ul li img { height: 80px; float: left; margin: 5px; border: 1px solid #111; transition: 0.3s; }
#video_results ul li img:hover { transform: scale(1.2); transition: 0.3s; }
#video_results ul li h3 { margin: 2px 0px 2px 0px; font-size: 14px; white-space: nowrap; width: 62%; overflow: hidden; transition: .5s; }
#video_results ul li div { width: 16px; float: right; height: 60px; margin: 2px; text-align: center; }
#video_results ul li div span { float: left; background: #189918; padding: 2px 6px; color: #fff; border-radius: 3px; cursor: pointer; border: 1px solid #eee; }
#video_results ul li div span:first-child { background: #CC181E; }
#video_results ul li div span:last-child { margin-top: 2px; }
#video_results ul li .desc { font-size: 9px; }
#video_results ul li .author { font-size: 9px; height: 10px; }
</style>
<script type="text/javascript">

if ( jQuery && !jQuery.fn.live ) {
	jQuery.fn.live = function( evt, func ) {
		$('body').on( evt, this.selector, func );
	}
}

function youtube() {
	var title  = $("#title").val();
	var result = $("#result_num").val();
	if ( title != "" ) {
		if ( title.search("youtube.com") > 0 ) {

			ShowLoading('Video bilgileri okunuyor...');
			var count = 1;
			{$thumb_process}
			$.post("engine/ajax/youtube.php", { action: 'getinfo', url: title }, function(data) {
				$("#video_results").hide();
				var ntitle = data.video.title;
				{$video_xfield}
				$("#related_news").html('<div id="video_result" style="display: none;"></div>').fadeIn(500);
				$("#video_result").html( '<div><img src="' + data.video.t1 + '" rel="click" /><p><span>' + data.video.s1 + '</p></span></div><div><img src="' + data.video.t2 + '" rel="click" /><p><span>' + data.video.s2 + '</p></span></div><div><img src="' + data.video.t3 + '" rel="click" /><p><span>' + data.video.s3 + '</p></span></div><div class="closebtn"><input type="button" class="btn btn-mini" value="Kapat" onclick="javascript:$(\'#video_result\').slideUp();" /></div>' ).show().animate({ height: "140px"});
			},'json').done( HideLoading );


		} else {
			var _height = result * 53 + 10;
			$.post("engine/ajax/youtube.php", { action: 'search', query: title, result: result }, function(data) {
				if (data.results ) {
					$("#video_result").hide();
					$("#related_news").html('<div id="video_result" style="display: none;"></div>' + data.results).fadeIn('slow', function() {
						$("#video_results ul").animate({height: _height + "px"});
					});
					{$player_active}
					$("#video_results ul li div span:last-child").click(function() {
						var selected = $(this).parent("div").parent("li");
						$("#video_results").slideUp( 500, function() {
							//var scroll = $(document).height();
							//$("html, body").animate( { scrollTop: scroll }, '1500', function() {
								var video_desc = selected.find("span.desc").text();
								var video_title = selected.find("h3").text();
								var video_url = selected.attr("url");
								var video_murl = selected.attr("murl");
								var video_author = selected.attr("author");
								var video_duration = selected.attr("duration");
								var video_thumb = selected.find('img').attr("src");
								var video_id  = selected.attr("vid");
								{$video_fields}
								{$thumbs_active}
							//});
						});
					});
					var count = 1;
					$("#video_result div img[rel='click']").live('click', function() {
						var img = $(this);
						var img_src = img.attr("src");
						{$thumbs_process}
					});
				} else {
					DLEalert(data.error, "Bilgilendirme");
				}
			},'json').done( HideLoading );
		}
	} else {
		DLEalert("Arama yapmadan önce başlık giriniz!", "Bilgilendirme");
	}
}
</script>
HTML;

?>