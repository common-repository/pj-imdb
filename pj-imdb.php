<?php
/*
Plugin Name: PJ IMDB
Plugin URI: http://pamjad.me/pj-imdb/
Description: fetch the information of the movies from IMDB databse
Version: 2.1
Author: Pouriya Amjadzadeh
Author URI: http://pamjad.me
Text Domain: pj_imdb
Domain Path: /langs/
*/

## Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

// Create new class for pjIMDB
class pjIMDB {
	function __construct(){
		//Create Link for Pro Version
		add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), function($links){
			$links[] = '<a href="https://zhaket.com/pj-imdb-pro/" target="_blank">'.__('PRO Version','pj_imdb').'</a>';
			return $links;
		});
		add_action( 'init', array($this,'init') );
		add_action( 'save_post', array($this,'save_metabox_data') );

		add_action( 'add_meta_boxes', function(){
			add_meta_box('pj-imdb',__('Fetch info from IMDB','pj_imdb'),array($this,'metabox_content'),'post','normal','high');
		});
		add_action( 'admin_enqueue_scripts',function($hook){
			if($hook != 'post.php' && $hook != 'post-new.php') return; //Check its Edit Page
			wp_enqueue_style('pjimdb', plugin_dir_url( __FILE__ ).'/assets/style.css');
		});
		add_action( 'admin_footer', array($this,'enqueue_admin') );
		add_action( 'wp_ajax_pjIMDBf', array($this, 'call_api') );
	}
	function init(){
		load_plugin_textdomain( 'pj_imdb', false, dirname( plugin_basename( __FILE__ ) ) . '/langs' );
	}
	function metabox_content($post){
	?>
	<div class="imdb-box">
		<div class="head"><?php _e('Fetch info from IMDB','pj_imdb'); ?> <i class="logo"></i></div>
		<div class="search">
			<div>
				<input type="text" name="query" placeholder="<?php _e('Title / IMDB ID','pj_imdb'); ?>">
				<input type="number" min="1950" max="<?php echo date('Y'); ?>" name="year" placeholder="<?php _e('Year','pj_imdb'); ?>">
				<?php wp_nonce_field( 'pj_imdb_nonce', 'imdb_nonce' ); ?>
			</div>
			<button type="button" id="sendAjax"><img src="<?php echo plugin_dir_url( __FILE__ ); ?>assets/search.png"></button>
		</div>
		<?php
		if(!empty(pjimdb('id',$post->ID))){
			echo '<div id="result" style="display:block">';
			$this->call_api($post->ID);
			echo '</div>';
		} else {
			echo '<div id="result"></div>';
		}
		?>
	</div>
	<?php
	}
	function call_api($metadata = ''){
		if(empty($metadata)){
			$querystring = (isset($_GET['query']) ? 'q='.sanitize_text_field($_GET['query']) : '').(!empty($_GET['year']) ? '&y='.intval($_GET['year']) : '');
			$webservice = file_get_contents("http://ws.pjimdb.ir/?$querystring");
			$pjimdb = json_decode($webservice, true);
		} else {
			$pjimdb = get_post_meta( $metadata, 'pjimdb', true );
			$pjimdb['response'] = true;
		}
		if($pjimdb['response']) :
		?>
		<img src="<?php echo esc_url($pjimdb['poster']); ?>" />
		<ul>
			<li>
				<span><?php _e('ID','pj_imdb'); ?> :</span>
				<input readonly type="text" name="pjimdb[id]" value="<?php echo esc_attr($pjimdb['id']); ?>"/>
			</li>
			<li>
				<span><?php _e('Type','pj_imdb'); ?> :</span>
				<input readonly type="text" name="pjimdb[type]" value="<?php echo esc_attr($pjimdb['type']); ?>"/>
			</li>
			<li>
				<span><?php _e('Title','pj_imdb'); ?> :</span>
				<input readonly type="text" name="pjimdb[name]" value="<?php echo esc_attr($pjimdb['name']); ?>"/>
			</li>
			<li>
				<span><?php _e('Year','pj_imdb'); ?> :</span>
				<input readonly type="text" name="pjimdb[year]" value="<?php echo esc_attr($pjimdb['year']); ?>"/>
			</li>
			<li>
				<span><?php _e('Story','pj_imdb'); ?> :</span>
				<textarea readonly name="pjimdb[story]"><?php echo esc_attr($pjimdb['story']); ?></textarea>
			</li>
			<li>
				<span><?php _e('Runtime','pj_imdb'); ?> :</span>
				<input readonly type="text" name="pjimdb[runtime]" value="<?php echo esc_attr($pjimdb['runtime']); ?>"/>
			</li>
			<li>
				<span><?php _e('Genre','pj_imdb'); ?> :</span>
				<input readonly type="text" name="pjimdb[genre]" value="<?php echo esc_attr($pjimdb['genre']); ?>"/>
			</li>
			<li>
				<span><?php _e('Author','pj_imdb'); ?> :</span>
				<input readonly type="text" name="pjimdb[author]" value="<?php echo esc_attr($pjimdb['author']); ?>"/>
			</li>
			<li>
				<span><?php _e('Actors','pj_imdb'); ?> :</span>
				<input readonly type="text" name="pjimdb[actors]" value="<?php echo esc_attr($pjimdb['actors']); ?>"/>
			</li>
			<li>
				<span><?php _e('Rated','pj_imdb'); ?> :</span>
				<input readonly type="text" name="pjimdb[rated]" value="<?php echo esc_attr($pjimdb['rated']); ?>"/>
			</li>
			<li>
				<span><?php _e('Rating','pj_imdb'); ?> :</span>
				<input readonly type="text" name="pjimdb[rating]" value="<?php echo esc_attr($pjimdb['rating']); ?>"/>
			</li>
			<li>
				<span><?php _e('IMDB Url','pj_imdb'); ?> :</span>
				<input readonly type="text" name="pjimdb[imdburl]" value="<?php echo esc_attr($pjimdb['imdburl']); ?>"/>
			</li>
		</ul>
		<input type="hidden" name="pjimdb[poster]" value="<?php echo esc_attr($pjimdb['poster']); ?>"/>
		<?php
		wp_nonce_field( 'pjimdb_nonce_action', 'pj_nonce_imdb' );
		else :
			echo '<p class="error">'.__('Failed fetch...','pj_imdb').'</p>';
		endif;
		if(empty($metadata)){
			wp_die();
		}
	}
	function enqueue_admin(){
	?>
		<script type="text/javascript">
			jQuery(document).ready(function($) {
				$("#sendAjax").click(function(){
					$.ajax({
						url: ajaxurl,
						type: 'GET',
						data: {
							'action': 'pjIMDBf',
							'query' : $('.search [name=query]').val(),
							'year' : $('.search [name=year]').val(),
						},
						beforeSend : function() {
							$("#sendAjax").html('<img src="<?php echo plugin_dir_url( __FILE__ )?>assets/loading.svg" width="20" height="20">');
						},
						success: function (response) {
							$("#sendAjax").html('<img src="<?php echo plugin_dir_url( __FILE__ )?>assets/search.png">');
							$('#result').fadeIn().html(response);
						}
					});
				});
			});
		</script>
	<?php
	}
	function upload_poster($url,$parent_post_id,$setit = false){
		add_filter( 'upload_dir', 'pjimdb_dir_poster' );
		function pjimdb_dir_poster( $param ){
			$param['subdir'] = '/pjimdb';
			$param['path'] = $param['basedir'] . $param['subdir'];
			$param['url']  = $param['baseurl'] . $param['subdir'];
			return $param;
		}

		if( !class_exists( 'WP_Http' ) ) include_once( ABSPATH . WPINC . '/class-http.php' );
		$http = new WP_Http();
		$response = $http->request( $url );
		if( $response['response']['code'] != 200 ) return false;

		$upload = wp_upload_bits( basename($url.'.jpg'), null, $response['body'] );
		if( !empty( $upload['error'] ) ) return false;

		$file_path = $upload['file'];
		$file_name = basename( $file_path );
		$attachment_title = sanitize_file_name( pathinfo( $file_name, PATHINFO_FILENAME ) );
		
		$wp_upload_dir = wp_upload_dir();
		$post_info = array(
			'guid'           => $wp_upload_dir['url'] . '/' . $file_name,
			'post_mime_type' => 'image/jpeg',
			'post_title'     => $attachment_title,
			'post_content'   => '',
			'post_status'    => 'inherit',
		);
		$attach_id = wp_insert_attachment( $post_info, $file_path, $parent_post_id );
		require_once( ABSPATH . 'wp-admin/includes/image.php' );
		$attach_data = wp_generate_attachment_metadata( $attach_id, $file_path );
		wp_update_attachment_metadata( $attach_id,  $attach_data );
		return wp_get_attachment_url($attach_id);
	}
	function save_metabox_data($post_id){
		if(!isset( $_POST['pj_nonce_imdb'] ) || !wp_verify_nonce( $_POST['pj_nonce_imdb'], 'pjimdb_nonce_action' )) return $post_id;
		if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return $post_id;
		if(!current_user_can( 'edit_page', $post_id ) || !current_user_can( 'edit_post', $post_id )) return $post_id;

		$_POST['pjimdb']['poster'] = $this->upload_poster($_POST['pjimdb']['poster'],$post_id);

		update_post_meta($post_id, 'pjimdb', $_POST['pjimdb']);
	}
}
new pjIMDB();

function pjimdb($value ='',$postID = '',$echo = false) {
	if(empty($value)) return NULL;
	if(empty($postID)) $postID = get_the_ID();

	$data = array('id','type','name','poster','year','genre','director','author','actors','country','language','rated','runtime','rating','votes','metascore','award','sell','production','story','website','imdburl');

	if(in_array($value,$data)){
		$imdb_field = get_post_meta( $postID, 'pjimdb', true );
		if($echo) echo $imdb_field[$value];
		return $imdb_field[$value];
	} else {
		return NULL;
	}
}