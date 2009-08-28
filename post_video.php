<?php
/*  Copyright 2008  http://www.spotonseoservices.com (email: seo@spotonseoservices.com)

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

/*
Plugin Name: Embed YouTube Videos in WordPress
Plugin URI: http://www.spotonseoservices.com/embed-youtube-videos-in-wordpress-plugin/
Description: Include YouTube videos inside posts or pages and also in a widget. Works in WP version 2.5+
Version: 1.0
Author: SpotOn SEO Services
Author URI: http://www.spotonseoservices.com

*/


	//independent hooks
	register_activation_hook(__FILE__,'pv_install');
	register_deactivation_hook(__FILE__,'pv_uninstall');
	
	add_action('save_post', 'pv_meta_save');
	add_filter('the_content','pv_attach_video',1);
	add_action('admin_menu','pv_settings');
	add_action("plugins_loaded", "init_widget");
	add_action('wp_footer','pv_footer');
	
	function pv_install(){
		add_option('pv_height','200');
		add_option('pv_width','300');
		add_option('pv_single_only',1);
		add_option('pv_float','right');
		add_option('pv_widget_title');
		add_option('pv_in_post',1);
		add_option('pv_in_widget',0);
	}
	function pv_uninstall(){
		delete_option('pv_height');
		delete_option('pv_width');
		delete_option('pv_single_only');
		delete_option('pv_float');
		delete_option('pv_widget_title');
		delete_option('pv_in_post');
		delete_option('pv_in_widget');
	}
	
	add_action('admin_menu', 'pv_meta_init');
	
	function init_widget(){
		register_sidebar_widget('Post Video','pv_widget');
		register_widget_control('Post Video','pv_widget_control');
	}

	function pv_widget_control(){
		if(isset($_POST['pv_widget_title'])) {
			update_option('pv_widget_title',$_POST['pv_widget_title']);
			
		}
		echo '<label>Title <input type="text" id="pv_widget_title" name="pv_widget_title" value="'.get_option('pv_widget_title').'"></label>';
	}
	function pv_widget($args){
		global $post;

		if($post->post_type=='post' && !is_single()) return false; //only show in widget when it is single post mode
		
		
		if(get_post_meta($post->ID,'pv_in_widget',true)==1){
			$pv = get_post_meta($post->ID, 'pv_video', true);
			if(!empty($pv)){
				extract($args);
				
				$widget_title = get_option('pv_widget_title');
				$widget_title = !empty($widget_title) ? $widget_title : 'Post Video';
				
				echo $before_widget;
				
		        echo $before_title . $widget_title  . $after_title;
				
				
				echo pv_show_video($pv,$post->ID,'widget');
				echo '<br /><center>'.get_post_meta($post_id,'pv_video_text',true).'</center>';
		        echo $after_widget; 
			}
		}	
	}
		

	
	function pv_meta_init() {
		
		if (function_exists('add_meta_box')) {
			add_meta_box('pv_meta','Post Video', 'pv_meta_box','page', 'advanced','high');
			add_meta_box('pv_meta','Post Video', 'pv_meta_box','post', 'advanced','high');
		} 
	}

	/**
	Meta box code for WordPress 2.5+
	*/
	function pv_meta_box() {
		global $post_ID;
			
		$pv = get_post_meta($post_ID, 'pv_video', true);
		
		//get meta value or default value (when meta value is empty)
		$height = get_post_meta($post_ID,'pv_height',true);
		$height = !empty($height) ? $height : get_option('pv_height');
		
		$width = get_post_meta($post_ID,'pv_width',true);
		$width = !empty($width) ? $width : get_option('pv_width');
		
		$float = get_post_meta($post_ID,'pv_float',true);
		$float = !empty($float) ? $float : get_option('pv_float');
		
		$in_post = get_post_meta($post_ID,'pv_in_post',true);
		$in_post = isset($in_post) ? $in_post : get_option('pv_in_post');		
		
		$in_widget = get_post_meta($post_ID,'pv_in_widget',true);
		$in_widget = isset($in_widget) ? $in_widget : get_option('pv_in_widget');
		

		
		echo "<input type=\"hidden\" name=\"pv_nonce\" id=\"pv_nonce\" value=\"" . wp_create_nonce(md5(plugin_basename(__FILE__))) . "\" />";

		echo '<label>Video URL: <input type="text" name="pv_video_url" size="50" value="'.$pv.'" /></label>';
		echo '<br /><label>Height: <input type="text" name="pv_height" size="5" id="pv_height" value="'.$height.'" />px</label>';
		echo '&nbsp;&nbsp;<label>Width: <input type="text" name="pv_width" id="pv_width" size="5" value="'.$width.'" />px</label>';
		
		echo '&nbsp;&nbsp;<label>Float: '; 
		$opt = array('Left'=>'left','Right'=>'right','Inherit'=>'inherit','None'=>'none'); 
		echo '<label><select name="pv_float" id="pv_float">';
					
			foreach($opt as $name=>$value){
				if($value==$float){
					echo "<option selected value=\"$value\">$name</option>";
				} else {
					echo "<option value=\"$value\">$name</option>";
				}
			}
						
		echo '</select></label>';
		echo '&nbsp;&nbsp;<label>Show in Post/Page <input type="checkbox" value="1" name="pv_in_post" id="pv_in_post"';
			if($in_post==1) echo 'checked="checked"';
		echo ' /></label>';
		echo '&nbsp;&nbsp;<label>Show in Widget <input type="checkbox" value="1" name="pv_in_widget" id="pv_in_widget"';
			if($in_widget==1) echo 'checked="checked"';
		echo ' /></label>';
		echo '<br /><label>Video Text: <input type="text" name="pv_video_text" size="50" value="'.get_post_meta($post_ID,'pv_video_text',true).'" /></label>';
	}

	
	function pv_meta_save($post_id){
		if (!wp_verify_nonce($_POST['pv_nonce'], md5(plugin_basename(__FILE__)))) { return $post_id; }

		if ('post' == $_POST['post_type']) {
			if (!current_user_can('edit_posts', $post_id)) { return $post_id; }
		}
		if ('page' == $_POST['post_type']) {
			if (!current_user_can('edit_pages', $post_id)) { return $post_id; }
		}
		

 		update_post_meta($post_id, 'pv_video', $_POST['pv_video_url']);
 		update_post_meta($post_id, 'pv_height', $_POST['pv_height']);
 		update_post_meta($post_id, 'pv_width', $_POST['pv_width']);
 		update_post_meta($post_id, 'pv_float', $_POST['pv_float']);
 		update_post_meta($post_id, 'pv_in_post', empty($_POST['pv_in_post']) ? 0 : $_POST['pv_in_post']);
 		update_post_meta($post_id, 'pv_in_widget', empty($_POST['pv_in_widget']) ? 0 : $_POST['pv_in_widget']);
 		update_post_meta($post_id, 'pv_video_text', $_POST['pv_video_text']);
 		
 		return; 
 		
	}
	
	function pv_attach_video($content){
		global $post;
		
		if(get_post_meta($post->ID,'pv_in_post',true) != 1) return $content;
		
		$pv = get_post_meta($post->ID,'pv_video',true);
		if(empty($pv)) return $content;
		
		$singleonly = get_option('pv_single_only');


		if(is_page()){
			
			$return=pv_show_video($pv,$post->ID);
			$return = $return.$content;
			return $return;
		} 
		
		if($singleonly AND !is_single($post->ID)) {
			return $content;
		} else {
			return pv_show_video($pv,$post->ID).$content;
		}

		
		return $content;
		
	}

	function pv_show_video($url,$post_id,$mode='post'){
		$height = get_post_meta($post_id,'pv_height',true);
		$height = !empty($height) ? $height : get_option('pv_height');
		
		$width = get_post_meta($post_id,'pv_width',true);
		$width = !empty($width) ? $width : get_option('pv_width');
		
		$float = get_post_meta($post_id,'pv_float',true);
		$float = !empty($float) ? $float : get_option('pv_float');
		
		
		if($mode == 'widget'){
			$return = '<div>';
		} else {
			$return = '<div style="margin: 5px 20px; float:'.$float.'">';
		}
	
		
		$return .= '<object type="application/x-shockwave-flash" style="width:'.$width.'px; height:'.$height.'px;" data="'.$url.'"><param name="movie" value="'.$url.'" /><param name="wmode" value="transparent"></param></object><br /><center>'.get_post_meta($post_id,'pv_video_text',true).'</center></div>';
		return $return;
	}
	
	
	function pv_settings(){
		add_options_page('Post Video','Post Video',9,basename(__FILE__),'pv_settings_page');
	}
	
	function pv_settings_page(){
		
		
	?>
		<div class="wrap">
		<h2>Default Settings for Post Video</h2>
		<form action="options.php" method="post">
		<?php wp_nonce_field('update-options'); ?>
			<table class="form-table" >
			<tbody>
				<tr><th> Single Only: </th><td> <input type="checkbox" value="1" name="pv_single_only" id="pv_single_only" <?php if(get_option('pv_single_only')==1) echo 'checked="checked"';?> /> Show video when viewing single post</td> </tr>
				<tr><th>Height: </th> <td>  <input type="text" name="pv_height" size="5" id="pv_height" value="<?php echo get_option('pv_height') ?>" />px</td></tr>
				<tr><th>Width: </th> <td> <input type="text" name="pv_width" id="pv_width" size="5" value="<?php echo get_option('pv_width') ?>" />px</td></tr>
				<tr><th><label> Float: </th> <td> 
				<?php $opt = array('Left'=>'left','Right'=>'right','Inherit'=>'inherit','None'=>'none'); ?>
				<select name="pv_float" id="pv_float">
					<?php
					foreach($opt as $name=>$value){
						if($value==get_option('pv_float')){
							echo "<option selected value=$value>$name</option>";
						} else {
							echo "<option value=$value>$name</option>";
						}
					}
					?>
				</select></label></td></tr>
				<tr><th>Show in Post/Page: </th><td><input type="checkbox" value="1" name="pv_in_post" id="pv_in_post" <?php if(get_option('pv_in_post')==1) echo 'checked="checked"'; ?> /></td></tr>
				<tr><th>Show in Widget: </th><td><input type="checkbox" value="1" name="pv_in_widget" id="pv_in_widget" <?php if(get_option('pv_in_widget')==1) echo 'checked="checked"'; ?> /></td></tr>
			</tbody>
			</table>
			<p class="submit">
				<input type="hidden" value="update" name="action">
				<input type="hidden" value="pv_single_only,pv_height,pv_width,pv_float,pv_in_post,pv_in_widget" name="page_options">
				<input type="submit" value="<?php _e('Save Changes') ?>" name="Submit">
			</p>
		</form>
		
		</div>
	<?php	
	}
	
		
	function pv_footer(){
		if(is_single()){
			global $post;
			$pv = get_post_meta($post->ID,'pv_video',true);
			if(!empty($pv)){
				echo '<p align="center"><a href="http://www.spotonseoservices.com/embed-youtube-videos-in-wordpress-plugin/">YouTube Videos in WordPress Plugin</a> by <a href="http://www.spotonseoservices.com">SpotOn Search Engine Optimization</a></p>';
			}
		}
	}
?>