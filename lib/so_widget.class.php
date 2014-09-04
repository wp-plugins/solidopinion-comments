<?php
class so_community_widget extends WP_Widget {

	// constructor
	function __construct() {
		parent::WP_Widget(false, $name = __('SolidOpinion Community', 'solidopinion-comments') );
	}

	// widget form creation
	function form($instance) {	
    // Check values
     	
    $tabs = array('1' => array('name' => 'top_by_points', 'text' => __('People', 'solidopinion-comments')), 
                  '2' => array('name' => 'last_threads', 'text' => __('Recent', 'solidopinion-comments')), 
                  '3' => array('name' => 'popular_thread', 'text' => __('Popular', 'solidopinion-comments')));
				  
                  
    if($instance) {
      $default_tab = esc_attr($instance['default_tab']);
      $items_number = esc_attr($instance['items_number']);
      $popular_thread = (isset($instance['popular_thread'])) ? esc_attr($instance['popular_thread']) : '';
      $last_threads = (isset($instance['last_threads'])) ? esc_attr($instance['last_threads']) : '';
      $top_by_points = (isset($instance['top_by_points'])) ? esc_attr($instance['top_by_points']) : '';
      
      $tabs_list = array($top_by_points, $last_threads, $popular_thread);
      if (!in_array($default_tab, $tabs_list)) {
        for ($i=0; $i<3; $i++){
          if ($tabs_list[$i]){
            $default_tab = $tabs_list[$i];
            break;
          }
        }
      }
    } else {
      $default_tab = '';
      $items_number = '';
      $popular_thread = '';
      $last_threads = '';
      $top_by_points = '';
    }
    
?>
    
    <p>
      <label for="<?php echo $this->get_field_id('default_tab'); ?>"><?php echo __('Default tab', 'solidopinion-comments'); ?>:</label>
      <select class="widefat" style="width:100px;" id="<?php echo $this->get_field_id('default_tab'); ?>" name="<?php echo $this->get_field_name('default_tab'); ?>">
        <?php foreach ($tabs as $key=>$val) {
            //if (isset(${$val['name']}) && ${$val['name']}) { ?>
            <option value="<?php echo $val['name']; ?>" <?php if ($default_tab == $val['name']) { ?> selected<?php } ?>><?php echo $val['text']; ?></option>
        <?php //}
        } ?>
      </select>
    </p>
    <p>
      <label for="<?php echo $this->get_field_id('items_number'); ?>"><?php echo __('Number of items', 'solidopinion-comments'); ?>:</label>
      <input class="widefat" id="<?php echo $this->get_field_id('items_number'); ?>" name="<?php echo $this->get_field_name('items_number'); ?>" type="text" value="<?php echo $items_number; ?>" style="width:50px;" maxlength="3" />
    </p>
    <script>
      jQuery(document).ready(function(){
        jQuery(document).on('keyup', '#<?php echo $this->get_field_id('items_number'); ?>', function(){
          var val = jQuery(this).val();
          if (val<0) {
            jQuery(this).val(Math.abs(val));
          }
        });
      });
    </script>
    
    <p><?php echo __('Show tabs', 'solidopinion-comments'); ?>:</p>
    <p>
      <?php foreach ($tabs as $key=>$val) { ?>
        <input class="widefat so_tabs" style="width:auto" id="<?php echo $this->get_field_id($val['name']); ?>" name="<?php echo $this->get_field_name($val['name']); ?>" type="checkbox" value="<?php echo $val['name']; ?>"<?php if (${$val['name']}) { ?> checked="checked"<?php } ?> />
        <label for="<?php echo $this->get_field_id($val['name']); ?>"><?php echo $val['text']; ?></label><br>
      <?php } ?>
      <br>
    </p>
<?php 
  }

	// widget update
	function update($new_instance, $old_instance) {
		$instance = $old_instance;
    
    $instance['items_number'] = strip_tags($new_instance['items_number']);
    $instance['popular_thread'] = strip_tags($new_instance['popular_thread']);
    $instance['last_threads']  = strip_tags($new_instance['last_threads']);
    $instance['top_by_points']  = strip_tags($new_instance['top_by_points']);
    
    $tabs_list = array($new_instance['top_by_points'], $new_instance['last_threads'], $new_instance['popular_thread']);
    if (!in_array($new_instance['default_tab'], $tabs_list)) {
      for ($i=0; $i<3; $i++){
        if ($tabs_list[$i]){
          $instance['default_tab'] = $tabs_list[$i];
          break;
        }
      }
    } else {
      $instance['default_tab']  = strip_tags($new_instance['default_tab']);
    }
    
    
    
    
    return $instance;
	}

	// widget display
	function widget($args, $instance) {
    extract($args);    
    $so_option = get_option('so_options');
    if ( !($so_option && isset($so_option['so_shortname']) && ($so_option['so_shortname']!='')) ) {
        return;
    }
    $default_tab = $instance['default_tab'];
    $items_number = $instance['items_number'];
    $popular_thread = $instance['popular_thread'];
    $last_threads = $instance['last_threads'];
    $top_by_points = $instance['top_by_points'];
    $tabs_list = array($top_by_points, $last_threads, $popular_thread);
    if (!in_array($default_tab, $tabs_list)) {
      for ($i=0; $i<3; $i++){
        if ($tabs_list[$i]){
          $default_tab = $tabs_list[$i];
          break;
        }
      }
    }
    $allow_tabs = ($top_by_points ? $top_by_points : '') . (($top_by_points && $last_threads) ? (',' . $last_threads) : ((!$top_by_points && $last_threads) ? $last_threads : '')) . 
                  ((($top_by_points || $last_threads) && $popular_thread) ? ',' . $popular_thread : ((!$top_by_points && !$last_threads && $popular_thread) ? $popular_thread : '') );
    $lang_id = get_so_language_id();
    $so_shortname = $so_option['so_shortname'];
    
    $url = SO_API_URL.'api/Site/getpublic/?shortname='.$so_shortname;
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    curl_setopt($curl, CURLOPT_USERPWD, "dev:eQ9UmN6WsuY");
    curl_setopt($curl, CURLOPT_HTTPHEADER, array('Connection: Close'));
    $response = json_decode(curl_exec($curl),true);
    curl_close($curl);

    if ($items_number && !empty($response) && isset($response['total_messages']) && $response['total_messages'] > 0) {
      echo $before_widget;
      echo $before_title;
      echo __('Community', 'solidopinion-comments');
      echo $after_title;
      echo str_replace(array('%%SO_SITENAME%%', '%%TAB_ID%%', '%%MAX_VALUE%%', '%%TABS%%', '%%LANG_ID%%'), array($so_shortname, $default_tab, ($items_number<10) ? (($items_number>0) ? $items_number : 1) : 10, $allow_tabs, $lang_id), get_include_contents(SO_COMMENTS_DIR . '/templates/community_template.php'));
      echo $after_widget; 
    }
    
	}
}

