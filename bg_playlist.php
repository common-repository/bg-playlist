<?php
/* 
    Plugin Name: Bg Playlist 
    Description: The plugin creates the WP playlist using links to audio files in the posts.
    Version: 1.5.6
    Author: VBog
    Author URI: https://bogaiskov.ru 
	License:     GPL2
	Text Domain: bg-playlist
	Domain Path: /languages
*/

/*  Copyright 2018-2023  Vadim Bogaiskov  (email: vadim.bogaiskov@gmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/*****************************************************************************************
	Блок загрузки плагина
	
******************************************************************************************/

// Запрет прямого запуска скрипта
if ( !defined('ABSPATH') ) {
	die( 'Sorry, you are not allowed to access this page directly.' ); 
}

define('BG_PLAYLIST_VERSION', '1.5.6');

$upload_dir = wp_upload_dir();
define( 'BG_PLAYLIST_URI', plugin_dir_path( __FILE__ ) );
define( 'BG_PLAYLIST_PATH', str_replace ( ABSPATH , '' , BG_PLAYLIST_URI ) );
define( 'BG_PLAYLIST_STORAGE', 'bg_playlist' );
define( 'BG_PLAYLIST_STORAGE_URL', $upload_dir['baseurl'] ."/". BG_PLAYLIST_STORAGE );
define( 'BG_PLAYLIST_STORAGE_URI', $upload_dir['basedir'] ."/". BG_PLAYLIST_STORAGE );
define( 'BG_PLAYLIST_STORAGE_PATH', str_replace ( ABSPATH , '' , BG_PLAYLIST_STORAGE_URI  ) );

if (!file_exists(BG_PLAYLIST_STORAGE_URI)) @mkdir( BG_PLAYLIST_STORAGE_URI );

define('BG_HTTP_HOST',((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://").$_SERVER['HTTP_HOST']);

/*****************************************************************************************
	Настройки плагина

******************************************************************************************/
include_once('inc/options.php');
$bg_playlist_option = bg_playlist_get_option();

function bg_playlist_uninstall () {
	delete_option('bg_playlist_options1');
	delete_option('bg_playlist_options2');
}
register_uninstall_hook(__FILE__, 'bg_playlist_uninstall');
/*****************************************************************************************
	Загрузка интернационализации
	
******************************************************************************************/
function bg_playlist_load_textdomain() {
  load_plugin_textdomain( 'bg-playlist', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' ); 
}
add_action( 'plugins_loaded', 'bg_playlist_load_textdomain' );

/*****************************************************************************************
	Регистрируем JS-скрипт
	
******************************************************************************************/
if (!is_admin()) {
	function bg_playlist_enqueue_frontend_scripts(){
		global $bg_playlist_option;

		wp_enqueue_script( 'bg_playlist_proc', plugins_url( 'js/player.js', __FILE__ ), false, BG_PLAYLIST_VERSION, true );
		wp_localize_script( 'bg_playlist_proc', 'bg_playlist', 
			array( 
				'nonce' 		=> wp_create_nonce('bg-playlist-nonce'),
				'header'		=> !empty($bg_playlist_option['show_header']),
				'download'		=> !empty($bg_playlist_option['show_download']),
				'play_pause'	=> !empty($bg_playlist_option['show_play_pause']),
				'noloop'		=> !empty($bg_playlist_option['noloop']),
				'get_duration'	=> !empty($bg_playlist_option['get_duration']),
				'step'			=> !empty($bg_playlist_option['step'])?$bg_playlist_option['step']:'30',
				'title_play'	=> __('Play', 'bg-playlist'),
				'title_pause'	=> __('Pause', 'bg-playlist'),
				'title_next'	=> __('Forward', 'bg-playlist'),
				'title_prev'	=> __('Back', 'bg-playlist'),
				'url_to_clipboard' => __('Copy M3U-playlist URL to clipboard', 'bg-playlist'),
				'already_copied'=> __('Playlist URL copied into clipboard', 'bg-playlist'),
			) 
		);
	}
	add_action( 'wp_enqueue_scripts', 'bg_playlist_enqueue_frontend_scripts' );

} else {	
	
	function bg_playlist_enqueue_admin_scripts(){
		global $bg_playlist_option;

		wp_enqueue_script( 'bg_playlist_admin', plugins_url( 'js/player_admin.js', __FILE__ ), false, BG_PLAYLIST_VERSION, true );
		wp_localize_script( 'bg_playlist_admin', 'bg_playlist', 
			array( 
				'audioclass'	=> $bg_playlist_option['audioclass'],

				'title' 		=> __('Insert audiolink', 'bg-playlist'),
				'legend1' 		=> __('Audiolink atributes', 'bg-playlist'),
				'legend2' 		=> __('Image atributes', 'bg-playlist'),

				'l_class'		=> __('class', 'bg-playlist'),
				'l_href'		=> __('URL', 'bg-playlist'),
				'l_title'		=> __('Title', 'bg-playlist'),
				'l_alt'			=> __('Discription', 'bg-playlist'),
				'l_data-artist'	=> __('Artist', 'bg-playlist'),
				'l_data-album'	=> __('Album', 'bg-playlist'),
				'l_data-length'	=> __('Duration, sec.', 'bg-playlist'),
				'l_text'		=> __('Link text', 'bg-playlist'),

				'l_src'			=> __('URL', 'bg-playlist'),
				'l_width'		=> __('Width', 'bg-playlist'),
				'l_height'		=> __('Height', 'bg-playlist'),

				'insert'		=> __('Insert', 'bg-playlist'),
				'cancel'		=> __('Cancel', 'bg-playlist'),
				
				'btn_audiolink'	=> __('Insert audiolink','bg-playlist'),
				'btn_audiodisk'	=> __('Create playlist: select the text and click this button','bg-playlist'),
				'btn_playlist'	=> __('Insert playlist','bg-playlist'),

				'ttl_playlist'	=> __('Playlist URL','bg-playlist'),
			) 
		);
	}
	add_action( 'admin_enqueue_scripts', 'bg_playlist_enqueue_admin_scripts' );
}

/*****************************************************************************************
	Подключаем таблицу стилей

******************************************************************************************/
if (!is_admin()) {
	function bg_playlist_enqueue_frontend_styles () {
		global $bg_playlist_option;
		
		wp_enqueue_style( "bg_playlist_styles", plugins_url( "css/player.css", __FILE__ ), array() , BG_PLAYLIST_VERSION  );
		if ($bg_playlist_option['skin'])
			wp_enqueue_style( "bg_playlist_skin", $bg_playlist_option['skin'], array() , BG_PLAYLIST_VERSION  );
	}
	add_action( 'wp_enqueue_scripts' , 'bg_playlist_enqueue_frontend_styles' );
}

/*****************************************************************************************
	Регистрируем фильтр на контент поста	
	Если в тексте поста нет шорткода [audiodisk], 
	то все ссылки wpaudio - в плейлист	
******************************************************************************************/
function bg_post_playlist($content) {
	if (strpos($content, '[audiodisk') === false ){
		$content = bg_insert_player($content);
	}
	return $content;
}	
if (!empty($bg_playlist_option['autoplaylist'])) add_filter( 'the_content', 'bg_post_playlist' );

/*****************************************************************************************
	Регистрируем шорт-код [audiodisk]...[/audiodisk]
	Всё содержимое внутри тего шорткода заменяется на проигрыватель 
	из содержащихся внутри ссылок wpaudio
******************************************************************************************/
function bg_audiodisk_sortcode( $atts, $content = null ) {
	extract( shortcode_atts( array(
		'src' => ''
	), $atts ) );
	
	$playlist = false;
	if ($src) {
		if (!preg_match('#https?:\/\/#', $src)) {	// Относительный путь
			if ($src[0] != '/') $src = '/'.$src;
			$src = site_url($src);
		}	
		$ext = substr(strrchr($src, '.'), 1);
		if ( strtolower($ext) == 'm3u') {
			$playlist = bg_m3u_parse ($src);
			if (is_array($playlist)) {
				$playlist = bg_playlist_player ($playlist);
			}
		} elseif ( strtolower($ext) == 'pls') {
			$playlist = bg_pls_parse ($src);
			if (is_array($playlist)) {
				$playlist = bg_playlist_player ($playlist);
			}
		} else $playlist = $src." - ".__('This playlist format is not supported.','bg-playlist'). PHP_EOL ; 
	} 
	if ($content) {
		$content = str_replace('[audiodisk]', '', $content);
		$content = str_replace('[/audiodisk]', '', $content);
		$content = bg_insert_player($content);
		$content = do_shortcode($content);
	}

	return $playlist.$content;
}
add_shortcode( 'audiodisk', 'bg_audiodisk_sortcode' );

/*****************************************************************************************
	Преобразуем плейлист формата m3u в массив
	
******************************************************************************************/
function bg_m3u_parse ($src) {

	$filename = str_replace (site_url().'/', ABSPATH, $src);
	$content = @file_get_contents ($filename);
	$content = bg_playlist_removeBOM($content);
	if (!$content || (substr($content, 0, 7) != '#EXTM3U')) return $src." - ".__('Playlist not found or corrupt file.','bg-playlist'). PHP_EOL ; 

	preg_match_all('/(?:(?P<tag>#EXTINF:)(?P<length>\s*\-?\d+))\s*(?:(?P<text>,[^\r\n]+)\r?\n(?P<url>[^\s]+))/', $content, $match );

	$result = array();
	$count = count( $match[0] );
	$index = 0;

	for( $i =0; $i < $count; $i++ ){

		if( !empty($match['tag'][$i]) && !empty($match['url'][$i])){

			$item = trim($match['length'][$i]);
			if (!$item) $item = -1;
			$result[$index]['length'] = (int) $item;

			$item = trim($match['url'][$i]);
			if (!preg_match('#https?:\/\/#', $item)) {	// Относительный путь
				if ($item[0] != '/') {					// относительно плейлиста 
					$path = str_replace(basename($src), "", $src);
					$item = $path.$item;
				} else {								// относительно корня сайта
					$item = BG_HTTP_HOST.$item;
				}
			}
			$result[$index]['url'] = $item;
			
			$item = trim(substr($match['text'][$i], 1));
			if (strchr($item, '-') === false) {
				$result[$index]['title'] = bg_wp_kses($item);
			} else {
				list($artist, $title) = explode('-', $item, 2);
				$result[$index]['artist'] = bg_wp_kses($artist);
				$result[$index]['title'] = bg_wp_kses($title);
			}
			if (empty ($result[$index]['title']) || $result[$index]['title']=='-') 
				$result[$index]['title'] = basename($result[$index]['url']);
		}
		$index++;
	}
	if (empty($result)) return $src." - ".__('Playlist is empty or corrupt file.','bg-playlist'). PHP_EOL ;

	return ($result);
}

/*****************************************************************************************
	Преобразуем плейлист формата pls в массив
	
******************************************************************************************/
function bg_pls_parse ($src) {

	$filename = str_replace (site_url(), ABSPATH, $src);
	$content = @file_get_contents ($filename);
	$content = bg_playlist_removeBOM($content);
	if (!$content || (substr($content, 0, 10) != '[playlist]')) return $src." - ".__('Playlist not found or corrupt file.','bg-playlist'). PHP_EOL ;

	preg_match_all('/(?:File(?P<num>\d+)=)\s*(?:(?P<text>[^\r\n]+))/i', $content, $match );

	$result = array();
	$count = count( $match[0] );
	$index = 0;

	for( $i =0; $i < $count; $i++ ){

		$item = trim($match['text'][$i]);
		if( !empty($match['num'][$i]) && $item){

			$result[$index]['num'] = (int) trim($match['num'][$i]);

			if (!preg_match('#https?:\/\/#', $item)) {	// Относительный путь
				if ($item[0] != '/') {					// относительно плейлиста 
					$path = str_replace(basename($src), "", $src);
					$item = $path.$item;
				} else {								// относительно корня сайта
					$item = BG_HTTP_HOST.$item;
				}
			}
			$result[$index]['url'] = $item;
			
			preg_match('/(?:Title'.$result[$index]['num'].'=)\s*(?:(?P<text>[^\r\n]+))/i', $content, $mt );
			$item = $mt['text'];
			if (strchr($item, '-') === false) {
				$result[$index]['title'] = bg_wp_kses($item);
			} else {
				list($artist, $title) = explode('-', $item, 2);
				$result[$index]['artist'] = bg_wp_kses($artist);
				$result[$index]['title'] = bg_wp_kses($title);
			}
			if (empty ($result[$index]['title']) || $result[$index]['title']=='-') 
				$result[$index]['title'] = basename($result[$index]['url']);
			
			preg_match('/(?:Length'.$result[$index]['num'].'=)\s*(?:(?P<text>[^\r\n]+))/i', $content, $mt );
			$item = (int) trim($mt['text']);
			if (!$item) $item = -1;
			$result[$index]['length'] = (int) $item;
		}
			
		$index++;
	}
	if (empty($result)) return $src." - ".__('Playlist is empty or corrupt file.','bg-playlist'). PHP_EOL ; 

	return ($result);
}

/*****************************************************************************************
	Находим в тексте поста ссылки на аудио файлы
	и вставляем вместо них плейлист 
******************************************************************************************/
function bg_insert_player($content, $playlist_number = 0) {
	global $bg_playlist_option;
	

	if ( preg_match_all( '#<(?P<tag>a)[^<]*?(?:>[\s\S]*?<\/(?P=tag)>|\s*\/>)#', $content, $matches, PREG_OFFSET_CAPTURE ) ) {
		global $wp_query;
		$post_id = $wp_query->post->ID;
		$post_title = $wp_query->post->post_title;
		$single_audio_meta = get_post_meta( $post_id, 'single_audio', true );
		$playlist = array();
		$num_tracks = 0;
		foreach ( $matches[0] as $match ) {
			unset($song);
			if (!$bg_playlist_option['audioclass'] ||
				preg_match( '#class\s*=\s*([\'\"])[^\'\"]*'.$bg_playlist_option['audioclass'].'[^\'\"]*(\1)#ui', $match[0] )) {
				if (!isset($offset)) $offset = $match[1];
				
				preg_match( '#href\s*=\s*([\'\"])([^(\1)]*(mp3|m4a|ogg|wav)\s*)(\1)#ui', $match[0], $mt );
				$url = trim($mt[2]);
				if (!empty ($url)) {
					if ($url[0] == '/') $url = BG_HTTP_HOST.$url;
					$song['url'] = $url;

					preg_match( '#(?:<a[^>]*>)([\s\S]*?)(?:<\/a>)#', $match[0], $mt );
					if (!empty ($mt[1])) {
						$text = $mt[1];
						preg_match( '#(?:<img)([^>]*)(?:>)#', $text, $imatch );
						if (!empty ($imatch[1])) {
							preg_match( '#src\s*=\s*([\'\"])([^\'\"]*)(\1)#ui', $imatch[1], $imt );
							$src = $imt[2];
							if (!empty ($src)) {
								if ($src[0] == '/') $src = BG_HTTP_HOST.$src;
								$image['src'] = $src;

								preg_match( '#width\s*=\s*([\'\"])([^\'\"]*)(\1)#ui', $imatch[1], $imt );
								if (!empty ($imt[2])) $image['width'] = (int) $imt[2];

								preg_match( '#height\s*=\s*([\'\"])([^\'\"]*)(\1)#ui', $imatch[1], $imt );
								if (!empty ($imt[2])) $image['height'] = (int) $imt[2];
								
								$song['image'] = $image;
							}
						}
						if ($text[0] != '#') $song['caption'] = bg_wp_kses($text);
					}
					preg_match( '#data\-length\s*=\s*([\'\"])([^\'\"]*)(\1)#ui', $match[0], $mt );
					if (!empty ($mt[2])) $song['length'] = (int) $mt[2];
					
					preg_match( '#data\-artist\s*=\s*([\'\"])([^\'\"]*)(\1)#ui', $match[0], $mt );
					if (!empty ($mt[2])) $song['artist'] = bg_wp_kses($mt[2]);
					
					preg_match( '#data\-album\s*=\s*([\'\"])([^\'\"]*)(\1)#ui', $match[0], $mt );
					if (!empty ($mt[2])) $song['album'] = bg_wp_kses($mt[2]);
					else $song['album'] = $post_title;
					
					preg_match( '#title\s*=\s*([\'\"])([^\'\"]*)(\1)#ui', $match[0], $mt );
					if (!empty ($mt[2]))  $song['title'] = bg_wp_kses($mt[2]);
					if (empty ($song['title'])){
						if (!empty ($song['caption']))$song['title'] = $song['caption'];
						else $song['title'] = basename($song['url']);
					}
					
					preg_match( '#alt\s*=\s*([\'\"])([^\'\"]*)(\1)#ui', $match[0], $mt );
					if (!empty ($mt[2])) $song['description'] = bg_wp_kses($mt[2]);
					
					if ($single_audio_meta) 
						$content = str_replace ($match[0], bg_playlist_player (array($song)), $content);
					else 
						$playlist[] = $song;
					$num_tracks++;
					$finish = $match[1]+strlen($match[0]);
				}
			}
		}
		
		if (!empty ($playlist) ) {
			$content = substr($content, 0, $offset). bg_playlist_player ($playlist). substr($content, $finish);
		}
	}
	
	return $content; 
}

/*****************************************************************************************
	Формирует код вывода на экран проигрывателя плейлиста
	(на основе wp_playlist_shortcode)
	
******************************************************************************************/
function bg_playlist_player ($playlist) {
    global $content_width, $bg_playlist_option;
	
	if (empty($playlist)) return "";
	
	$option = $bg_playlist_option;
	
	$outer = 22; 			// default padding and border of wrapper
    $default_width = 640;
    $theme_width = empty( $content_width ) ? $default_width : ( $content_width - $outer );
	
    $data = array(
        'type' => 'audio',
        // don't pass strings to JSON, will be truthy in JS
        'tracklist' => isset($option['show_list']),			// Наличие списка треков
        'tracknumbers' => isset($option['show_numbers'])&&(count($playlist)>1),	// Нумерация списка треков
        'images' => isset($option['show_image']),			// Наличие миниатюры у трека (задается в $track['image'])
        'artists' => isset($option['show_artist']),			// Выводить имя артиста (задается в $track['artist'])
    );
 
    $tracks = array();
    foreach ( $playlist as $song ) {
        $url = $song['url'];
		$title = isset($song['title'])?html_entity_decode ($song['title']):"";
		$caption = isset($song['caption'])?html_entity_decode ($song['caption']):"";
		$description = isset($song['description'])?html_entity_decode ($song['description']):"";
        $ftype = wp_check_filetype( $url, wp_get_mime_types() );
        $track = array(
            'src' => $url,					// URL трека
            'type' => $ftype['type'],		// Тип файла из списка допустимых
            'title' => $title,				// Назание трека
            'caption' => $caption,			// Подпись
            'description' => $description,	// Описание
       );
 
		$length = isset ($song['length'])?bg_playlist_sectotime($song['length']):"";
		$artist = (isset($option['show_artist'])&&isset($song['artist']))?html_entity_decode ($song['artist']):"";
		$album = (isset($option['show_album'])&&isset($song['album']))?html_entity_decode ($song['album']):"";
        $track['meta'] = array(
			'length_formatted' => $length,	// Продолжительность трека [length_formatted]
 			'artist'=> $artist,				// Имя артиста
			'album'=> $album,				// Альбом
		);	
		if (isset($song['image'])) {
		// array('src', 'width', 'height');
			$track['image'] = $song['image'];
			$track['thumb'] = $song['image'];
		}
		$tracks[] = $track;
    }
    $data['tracks'] = $tracks;		// Данные о треках плейлиста
	
	if (isset($option['show_m3u_playlist'])) $m3u = bg_m3u_generate ($playlist);
	else $m3u = false;
	
	ob_start();
	// Включаем проигрыватель аудио со светлой/темной темой
	do_action( 'wp_playlist_scripts', 'audio', $option['style'] );

	// Формируем код проигрывателя на экране
?>	
<!-- Playlist START -->
<div class="wp-playlist wp-audio-playlist wp-playlist-<?php echo $option['style']; ?>">
	<div class="wp-playlist-current-item"></div>
    <audio controls="controls" preload="<?php echo $option['preload']; ?>" width="<?php echo (int) $theme_width; ?>"></audio>
    <div class="wp-playlist-next"></div>
    <div class="wp-playlist-prev"></div>
    <script type="application/json" class="wp-playlist-script"><?php echo wp_json_encode( $data ); ?></script>
</div>
<?php if ($m3u):  ?>
<div class="bg_download_m3u"><a href="<?php echo BG_PLAYLIST_STORAGE_URL."/".$m3u[0]; ?>" download="<?php echo $m3u[1]; ?>.m3u"><?php  _e("Download M3U playlist","bg-playlist"); ?></a></div>
<?php endif;  ?>
<!-- Playlist END -->
<?php
	return ob_get_clean();

}
/*****************************************************************************************
	Создадим плейлист формата m3u
	
******************************************************************************************/
function bg_m3u_create ($playlist, $stream=true) {
	if (empty($playlist)) return "";
	
	$content = "#EXTM3U". PHP_EOL . PHP_EOL;
    foreach ( $playlist as $song ) {
		$url = $song['url'];
		$name = basename($url,".mp3");
		$filename = rawurlencode(basename($url));
		$url = str_replace(basename($url), $filename, $url);
		
		$content .= "#EXTINF:".
					(empty($song['length'])?"-1":$song['length']).",".
					(empty($song['artist'])?"":(strip_tags(html_entity_decode ($song['artist']))." - ")).
					(empty($song['title'])?$name:strip_tags(html_entity_decode ($song['title']))).PHP_EOL;
		if ($stream) $content .= $url.PHP_EOL;
		else $content .= $filename.PHP_EOL;
		$content .= PHP_EOL;
	}
	return $content;
}
/*****************************************************************************************
	Запишем плейлист формата m3u в файл
	
******************************************************************************************/
function bg_m3u_generate ($playlist) {
	global $post;
	static $bg_playlist_number = array();
	
//	return false;	// Заглушка 
	$m3u = bg_m3u_create ($playlist);
	if (empty($m3u)) return false;
	
	if (empty($bg_playlist_number[$post->ID]))$bg_playlist_number[$post->ID] = 0;
	$post_modified_date = get_the_modified_date( 'U' );
	$file = BG_PLAYLIST_STORAGE_URI."/".$post->ID.($bg_playlist_number[$post->ID]?"_".($bg_playlist_number[$post->ID]+1):"").".m3u";
	// Если файл не существует или время модификации поста больше времени создания файла,
	// создадим файл плейлиста
	if (!file_exists ($file) || $post_modified_date > filectime($file)) {
		$fp = fopen($file, "w"); 
		fwrite($fp, $m3u);
		fclose($fp);
	}
	$file_title = esc_html( $post->post_title ).($bg_playlist_number[$post->ID]?" ".($bg_playlist_number[$post->ID]+1):"");
	$bg_playlist_number[$post->ID]++;
	return array(basename($file), $file_title);
}
/*****************************************************************************************
	Переводит секунды в часы, минуты, секунды

******************************************************************************************/
function bg_playlist_sectotime ($seconds) {
	if ((int)$seconds < 0) return "";
	$minutes = floor($seconds / 60);		// Считаем минуты
	$hours = 0;	//floor($minutes / 60); 	// Считаем количество полных часов
//	$minutes = $minutes - ($hours * 60);	// Считаем количество оставшихся минут
	$seconds = $seconds - ($minutes - ($hours * 60))*60;// Считаем количество оставшихся секунд
	return  ($hours?($hours.":"):"").sprintf("%02d:%02d", $minutes, $seconds);
}

/*****************************************************************************************
	Обрезает пробельные символы по границам текста 
	и удаляет неразрешенные теги 

******************************************************************************************/
function bg_wp_kses ($html) {
	$allowed_html = array(
		'em' => array(),
		'strong' => array(),
		'i' => array(),
		'b' => array(),
		's' => array(),
		'del' => array(),
		'sup' => array(),
		'sub' => array(),
		'small' => array(),
		'span' => array(
			'class' => true,
			'style' => true,
		)
	); 
	return wp_kses(trim($html), $allowed_html);
}

/*****************************************************************************************

						КНОПКИ В WISIWYG РЕДАКТОРЕ TinyMCE

******************************************************************************************/
if (is_admin()) {
/*****************************************************************************************
	Кнопка, вставляющая тег <a class="wpaudio" ...>...</a>

******************************************************************************************/
	function bg_playlist_insert_links($plugin_array){ 
		$plugin_array['bg_playlist_insert_links'] = plugins_url( 'mce/insert_links/insert_links.js', __FILE__ );
		return $plugin_array;
	}

/*****************************************************************************************
	Кнопка, вставляющая шорткод [audiodisk]...[/audiodisk]

******************************************************************************************/
	function bg_playlist_insert_audiodisk($plugin_array){ 
		$plugin_array['bg_playlist_insert_audiodisk'] = plugins_url( 'mce/audiodisk/audiodisk.js', __FILE__ );
		return $plugin_array;
	}

/*****************************************************************************************
	Кнопка, вставляющая шорткод [audiodisk src="..."]

******************************************************************************************/
	function bg_playlist_insert_playlist($plugin_array){ 
		$plugin_array['bg_playlist_insert_playlist'] = plugins_url( 'mce/playlist/playlist.js', __FILE__ );
		return $plugin_array;
	}

/*****************************************************************************************
	Добавляем кнопки в список кнопок

******************************************************************************************/
	function bg_playlist_add_buttons($buttons){
		array_push($buttons, "|", 
			"bg_playlist_insert_links", 
			"bg_playlist_insert_audiodisk",
			"bg_playlist_insert_playlist"
		);
		return $buttons;
	}

/*****************************************************************************************
	Подключаем кнопки в WISIWYG редакторе

******************************************************************************************/
	function bg_playlist_custom_buttons(){
		// проверяем права доступа
		if ( ! current_user_can('edit_posts') && ! current_user_can('edit_pages') )
			return;
		// только в режиме WYSIWYG (Rich Editor Mode)
		if( get_user_option('rich_editing') == 'true'){
			add_filter('mce_external_plugins', 'bg_playlist_insert_links');
			add_filter('mce_external_plugins', 'bg_playlist_insert_audiodisk');
			add_filter('mce_external_plugins', 'bg_playlist_insert_playlist');
			add_filter('mce_buttons', 'bg_playlist_add_buttons');
		}
	}
	add_action('init', 'bg_playlist_custom_buttons');

/*****************************************************************************************
	Добавляем кнопку Quicktags, вставляющую шорт код [audiodisk]...[/audiodisk]
	 в текстовый редактор WP
******************************************************************************************/
	function bg_audiodisk_custom_quicktags() {
		if ( wp_script_is( 'quicktags' ) ) {
		?>
		<script type="text/javascript">
		QTags.addButton( 'playlist_audio_disk', 'Audiodisk', '[audiodisk]', '[/audiodisk]', '0', '<?php  _e("Create playlist: select the text and click this button","bg-playlist"); ?>', 1111 ); 
		</script>
		<?php
		}
	}
	add_action( 'admin_print_footer_scripts', 'bg_audiodisk_custom_quicktags' );
}


/*****************************************************************************************
	Находим в тексте поста ссылки на аудио файлы
	определяем продолжительност и вставляем 
	атрибут data-length в тег <a> 
******************************************************************************************/
if (!is_admin()) require_once ABSPATH . 'wp-admin/includes/media.php';
function bg_insert_duration($content) {
	$content = preg_replace_callback('/<a([^>]*?)>/is', 
	function ($match) {
		global $bg_playlist_option;
		$site_url = get_site_url()."/";
		$length = "";
		if ((!$bg_playlist_option['audioclass'] ||
			preg_match( '#class\s*=\s*([\'\"])[^\'\"]*'.$bg_playlist_option['audioclass'].'[^\'\"]*(\1)#ui', $match[1] )) &&
			!preg_match( '#data-length#ui', $match[1] )) {
			
			preg_match( '#href\s*=\s*([\'\"])([^(\1)]*(mp3|m4a|ogg|wav)\s*)(\1)#ui', $match[1], $mt );
			$url = trim($mt[2]);
			if (!empty ($url)) {
				if ($url[0] == '/') $url = BG_HTTP_HOST.$url;	// преобразуем относительный путь к абсолютному
				// Проверяем с этого ли сайта файл
				if (!strncasecmp ( $url , $site_url , strlen($site_url) )) {
					// Меняем URL на полный путь
					$file = ABSPATH.str_replace($site_url, "", $url ); 
					// Получаем метаданные из файла
					$metadata = wp_read_audio_metadata( $file );
					if (!empty($metadata) && !empty($metadata['length']))
						$length = ' data-length="'.$metadata['length'].'"';
				}
			}
		}
		return '<a'.$match[1].$length.'>';	
	}, $content);
	
	return $content; 
}
if (is_admin())	{
	// Функция вставляет продолжительность трека при сохранении поста
	function bg_playlist_save( $post_id) {
		if ( !wp_is_post_revision( $post_id ) ){
			$post = get_post($post_id);
			// удаляем этот хук, чтобы он не создавал бесконечного цикла
			remove_action('save_post', 'bg_playlist_save');

			// обновляем пост, тогда снова вызовется хук save_post
			$content = bg_insert_duration($post->post_content);
			$res = wp_update_post(  wp_slash( array( 'ID' => $post->ID, 'post_content' => $content ) ) );

			// снова вешаем хук
			add_action('save_post', 'bg_playlist_save');
			
			return $res;
		}
	}
	add_action( 'save_post', 'bg_playlist_save' );
}
	
/*!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!

									СЛУЖЕБНЫЕ ФУНКЦИИ

!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!*/

/*****************************************************************************************
	Регистрируем шорт-код [bg_check_playlist]
	Выводит на экран все посты, в которых может быть потерян текст 
	при их автоматической обработке
******************************************************************************************/
function bg_check_playlist_sortcode( $atts ) {
	
	global $post;

	$tmp_post = $post;	// записываем $post во временную переменную $tmp_post
	
	$args = array( 'posts_per_page' => -1 );
	$text = "<ol>";
	$myposts = get_posts( $args );
	foreach( $myposts as $post ){

		setup_postdata($post);

		$content = $post->post_content;
		if (strpos($content, '[audiodisk') === false ) {
			$warning = bg_check_wpaudio($content);
			if ($warning) {
				$text .= '<li>ID='. $post->ID .'<br><a href="'. get_the_permalink() .'">'. get_the_permalink() .'</a><br>'. get_the_title() .'<br>'. $warning .'</a></li>';
			}
		}
	} 
	$text .= "</ol>";

	$post = $tmp_post;// возвращаем былое значение $post
	
	return $text;
}
add_shortcode( 'bg_check_playlist', 'bg_check_playlist_sortcode' );
	
/*****************************************************************************************
	Находим в тексте поста ссылки на аудио файлы с классом wpaudio
	и проверяет нет ли между ними кокого-нибудь текста 
******************************************************************************************/
function bg_check_wpaudio($content) {
	global $bg_playlist_option;

	$warning = false;
	$li = 0;
	if ( preg_match_all( '#<(?P<tag>a)[^<]*?(?:>[\s\S]*?<\/(?P=tag)>|\s*\/>)#', $content, $matches, PREG_OFFSET_CAPTURE ) ) {
		foreach ( $matches[0] as $match ) {
			if (preg_match( '#class\s*=\s*([\'\"])'.$bg_playlist_option['audioclass'].'(\1)#ui', $match[0] )) {
				if (!isset($offset)) $offset = $match[1];
				preg_match( '#>([^<]*)<#', $match[0], $mt );
				preg_match( '#href\s*=\s*([\'\"])([^\'\"]*)(\1)#ui', $match[0], $mt );
				
				if (isset($finish)) {
					$li = $li + substr_count($content, 'li>', $finish, $match[1]-$finish);
					$wrn = trim (html_entity_decode (strip_tags(substr($content, $finish, $match[1]-$finish))));
					if ($wrn) $warning .= htmlspecialchars($wrn)."<font color='red'><b> || </b></font>";
				} 
				$finish = $match[1]+strlen($match[0]);
			}
		}
	}
	
	$quote =($warning?("<p> <b>".__('The page is need for revision.','bg-playlist')." </b>".__('Text can be lost:','bg-playlist')."<br>".$warning."</p>"):""); //Страница ожидает доработки. Будет потерян текст:
	$quote .=($li?("<p> <b>".__('The page is need for revision.','bg-playlist')." </b><font color='red'><b>".$li.__(' li-tags inside tracks','bg-playlist')."</b></font><br></p>"):""); //Страница ожидает доработки. Теги li внутри треков

	return $quote;
}

/********************************************************
	Удалить BOM из строки
	@param string $str - исходная строка
	@return string $str - строка без BOM
********************************************************/
function bg_playlist_removeBOM($str="") {
    if(substr($str, 0, 3) == pack('CCC', 0xef, 0xbb, 0xbf)) {
        $str = substr($str, 3);
    }
    return $str;
}