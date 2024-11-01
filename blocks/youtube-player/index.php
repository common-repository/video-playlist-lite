<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * YVYVideoPlayer starts here. Manager sets mode, adds required wp hooks and loads required object of structure
 *
 * Manager controls and access to all modules and classes of YVYVideoPlayer.
 *
 * @package YVYVideoPlayer
 * @since   1.0
 */
class AAT_YVPY_LITE {

	/**
	 * Constructor loads API functions, defines paths and adds required wp actions
	 *
	 * @since  1.0
	 */
	public function __construct() 
	{
		add_action( 'init', array( $this, 'init' ), 9 );

		add_action( 'wp_ajax_YVYAjaxRequest', array( $this, 'ajax_request' ) );
	}

	/**
	 * Callback function for WP init action hook. Sets YVYVideoPlayer mode and loads required objects.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @return void
	 */
	public function init() 
	{
		do_action( 'YVYVideoPlayer_before_init' );
		
		global $wpdb;
		$this->db = $wpdb;

		$getApiKey = get_option('youtenberg_yt_api_key');
		$this->apiKey = $getApiKey != '' ? $getApiKey : '';

		if( is_admin() ){
			$this->editor_assets();
		}
		else{
			$this->frontend_assets();
		}

		do_action( 'YVYVideoPlayer_after_init' );
	}

	public function frontend_assets()
	{
		wp_enqueue_style(
			'youtube_videos-blocks/youtube-player-view-style',
			plugins_url( 'youtube-player.view.css', __FILE__ ),
			array( 'wp-edit-blocks' )
		);

		wp_enqueue_script(
			'youtube_videos-blocks/youtube-player-editor-script',
			plugins_url( 'youtube-player.view.js', __FILE__ ),
			array( 'wp-components' )
	  	);
	}
	
	public function editor_assets()
	{
		wp_enqueue_script(
			'youtube_videos-blocks/youtube-player-editor-script',
			plugins_url( 'youtube-player.build.js', __FILE__ ),
			array( 'wp-blocks', 'wp-element', 'wp-editor' )
	  	);

		wp_enqueue_style(
			'youtube_videos-blocks/youtube-player-editor-style',
			plugins_url( 'youtube-player.editor.css', __FILE__ ),
			array( 'wp-edit-blocks' )
		);

		wp_enqueue_style(
			'youtube_videos-blocks/youtube-player-view-style',
			plugins_url( 'youtube-player.view.css', __FILE__ ),
			array( 'wp-edit-blocks' )
		);
		
		wp_localize_script( 'youtube_videos-blocks/youtube-player-editor-script', 'YVY', array(
			'ajax_url' => apply_filters( 'YVYVideoPlayer_ref', admin_url('admin-ajax.php?action=YVYAjaxRequest') ),
			'buy_url' => 'aHR0cHM6Ly9jb2RlY2FueW9uLm5ldC9pdGVtL3lvdXRlbmJlcmctZ3V0ZW5iZXJnLXlvdXR1YmUtcGxheWVyLXdpdGgtcGxheWxpc3QvMjMxOTA0MjQ/cmVmPUFBLVRlYW0=',
			'api_key' => $this->apiKey != '' ? 'true' : ''
		) );

		wp_enqueue_script( 'jquery-ui-core' );
		wp_enqueue_script( 'jquery-ui-sortable' );
	}

	function get_youtube_video_ID($youtube_video_url) 
	{
	  	$pattern = 
	        '%^# Match any youtube URL
	        (?:https?://)?  # Optional scheme. Either http or https
	        (?:www\.)?      # Optional www subdomain
	        (?:             # Group host alternatives
	          youtu\.be/    # Either youtu.be,
	        | youtube\.com  # or youtube.com
	          (?:           # Group path alternatives
	            /embed/     # Either /embed/
	          | /v/         # or /v/
	          | /&v=/       # or ?feature=youtu.be&v=NXwxHU2Q0bo
	          | /watch\?v=  # or /watch\?v=
	          )             # End path alternatives.
	        )               # End host alternatives.
	        ([\w-]{10,12})  # Allow 10-12 for 11 char youtube id.
	        $%x'
	        ;
        $result = preg_match($pattern, $youtube_video_url, $matches);
        if ($result) {
            return $matches[1];
        }
	  
	  // if no match return false.
	  return false;
	}

	function get_youtube_playlist_ID($youtube_video_url) 
	{
		// Playlist id is 12 or more characters in length
		$playlist_pattern = '~(?:http|https|)(?::\/\/|)(?:www.|)(?:youtu\.be\/|youtube\.com(?:\/embed\/|\/v\/|\/watch\?v=|\/ytscreeningroom\?v=|\/feeds\/api\/videos\/|\/user\S*[^\w\-\s]|\S*[^\w\-\s]))([\w\-]{12,})[a-z0-9;:@#?&%=+\/\$_.-]*~i';

		return ( preg_replace($playlist_pattern, '$1',  $_REQUEST['url']) );
	}

	public function get_video_by_playlist_id( $playlistID='' )
	{
		$url = 'https://www.googleapis.com/youtube/v3/playlistItems?playlistId=' . ( $playlistID ) . '&key=' . ( $this->apiKey ) . '&fields=items(snippet/resourceId/videoId,etag,snippet/channelTitle,snippet/title,snippet/publishedAt,snippet/channelId,snippet/thumbnails/medium/url)&part=snippet&maxResults=50';
		//die( var_dump( "<pre>", $url  , "<pre>" ) . PHP_EOL .  __FILE__ . ":" . __LINE__  ); 
		$response = wp_remote_get( esc_url_raw( $url ) );
		$api_response = json_decode( wp_remote_retrieve_body( $response ), true );

		//die( var_dump( "<pre>", $api_response  , "<pre>" ) . PHP_EOL .  __FILE__ . ":" . __LINE__  ); 

		if( !isset($api_response['items']) || ( isset($api_response['items']) && count($api_response['items']) == 0 ) ){
			return false;
		}

		$base = array();
		foreach ($api_response['items'] as $video) {

			$video['id'] = $video['snippet']['resourceId']['videoId'];
			unset($video['snippet']['resourceId']);
			$base[] = $video;
		}
		return $base;
	}

	public function get_video_by_id( $videoID='' )
	{

		if( is_array($videoID) ){
			$videoID = implode(",", $videoID);
		}
		//$url = 'https://www.googleapis.com/youtube/v3/videos?id=' . ( $videoID ) . '&key=' . ( AAT_YVY_URL_API_KEY ) . '&fields=*&part=snippet';
		$url = 'https://www.googleapis.com/youtube/v3/videos?id=' . ( $videoID ) . '&key=' . ( $this->apiKey ) . '&fields=items(id,etag,snippet/channelTitle,snippet/title,snippet/publishedAt,snippet/channelId,snippet/thumbnails/medium/url)&part=snippet';

		//die( var_dump( "<pre>", $url  , "<pre>" ) . PHP_EOL .  __FILE__ . ":" . __LINE__  ); 

		$response = wp_remote_get( esc_url_raw( $url ) );
		$api_response = json_decode( wp_remote_retrieve_body( $response ), true );

		if( !isset($api_response['items']) || ( isset($api_response['items']) && count($api_response['items']) == 0 ) ){
			return false;
		}

		return $api_response['items'];
	}

	public function search_by_keyword( $keyword = '' ) {

		if( trim($keyword) != '' ) {
			//https://www.googleapis.com/youtube/v3/search?maxResults=2&part=snippet&q=atb&type=video&key=AIzaSyBekPSV-b70anDeXJqDSe1rZaE_TM06PCc

			$url = 'https://www.googleapis.com/youtube/v3/search?q=' . ( trim($keyword) ) . '&key=' . ( $this->apiKey ) . '&fields=items(id,etag,snippet/channelTitle,snippet/title,snippet/publishedAt,snippet/channelId,snippet/thumbnails/medium/url)&part=snippet&type=video&maxResults=50';

			$response = wp_remote_get( esc_url_raw( $url ) );
			$api_response = json_decode( wp_remote_retrieve_body( $response ), true );

			if( !isset($api_response['items']) || ( isset($api_response['items']) && count($api_response['items']) == 0 ) ){
				return false;
			}

			return $api_response['items'];
		}
		
	}


	private function print_response( $status='valid', $msg='', $data=array() )
	{
		die( json_encode( array(
			'status' => $status,
			'msg' => $msg,
			'data' => $data
		) ) );
	}

	public function ajax_request()
	{
		$action = isset($_REQUEST['sub_action']) ? $_REQUEST['sub_action'] : '';
		
		if( $action == 'save_yt_api_key' ){
			$apiKey = isset($_REQUEST['apiKey']) ? $_REQUEST['apiKey'] : '';
			
			if( $apiKey == '' ){
				$this->print_response( 'invalid', 'Invalid YouTube API Key!' );
			}

			update_option('youtenberg_yt_api_key', $apiKey);

			$this->print_response( 'valid', 'valid youtube api key', $apiKey );
		}

		elseif( $action == 'get_video_by_url' ){
			$videoID = $this->get_youtube_video_ID( $_REQUEST['url'] );
			if( $videoID === false ){
				$this->print_response( 'invalid', 'Unable to get video ID from provided URL!' );
			}

			$video = $this->get_video_by_id( $videoID );
			if( $video === false ){
				$this->print_response( 'invalid', 'Sorry, we could not get that content. Please add a valid YouTube url and try again.' );
			}

			$this->print_response( 'valid', 'youtube data ok', $video );
		}

		elseif( $action == 'get_video_by_playlist' ){
			$playlistID = $this->get_youtube_playlist_ID( $_REQUEST['url'] );

			if( $playlistID === false ){
				$this->print_response( 'invalid', 'Unable to get playlist ID from provided URL!' );
			}

			$videos = $this->get_video_by_playlist_id( $playlistID );

			$this->print_response( 'valid', 'youtube data ok', $videos );
		}
		
		elseif( $action == 'get_video_by_url_list' ){
			$url_list = str_replace( " ", "", $_REQUEST['url'] );
			$url_list = explode(",", $url_list);

			$validIDs = array();
			if( count($url_list) > 0 ){
				foreach ($url_list as $id) {
					$videoID = $this->get_youtube_video_ID( $id );

					if( $videoID ){
						$validIDs[] = $videoID;
					}
				}
			}

			if( count($validIDs) == 0 ){
				$this->print_response( 'invalid', 'Unable to get any video ID from provided URL list!' );
			}

			$videos = $this->get_video_by_id( $validIDs );
			if( $videos === false ){
				$this->print_response( 'invalid', 'Sorry, we could not get that content. Please add a valid YouTube url and try again.' );
			}
			$this->print_response( 'valid', 'youtube data ok', $videos );
		}

		elseif( $action == 'search_by_keyword' ){
			$keyword = trim($_REQUEST['keyword']);

			if( $keyword != '' && strlen($keyword) > 2 ) {
				$get_results = $this->search_by_keyword( $keyword );

				$this->print_response( 'valid', 'youtube data ok', $get_results );
			}
		}

		die("Cmon'!");
	}
}

/**
 * Main AAT_YVPY manager.
 * @var AAT_YVPY $AAT_YVPY - instance of composer management.
 * @since 1.0
 */
global $AAT_YVPY_LITE;
$AAT_YVPY_LITE = new AAT_YVPY_LITE();

function AAT_YVPY_LITE(){
	global $AAT_YVPY_LITE;

	return $AAT_YVPY_LITE;
}