<?php
class MySettingsPage
{
    /**
     * Holds the values to be used in the fields callbacks
     */
    private $options;

    /**
     * Start up
     */
    public function __construct()
    {
        add_action('admin_menu', array($this, 'add_plugin_page'));
        add_action('admin_init', array($this, 'page_init'));
        add_action('export_script', array($this, 'add_export_script'));
        add_action('wp_ajax_export_to_xml', array($this, 'prefix_ajax_export_to_xml') );
		
        if (!(($_REQUEST['subaction'] == 'setsite') && ($_REQUEST['shortname'] != ''))) {
          add_action('admin_notices', 'so_settings_warning');
        }
    }

	
    /**
     * Add export script
     */
    public function add_export_script()
    {
        wp_enqueue_script('export', plugins_url( 'solidopinion-comments/media/js/export.js' ), array('jquery', 'common'));
    }
    /**
     * Add options page
     */
    public function add_plugin_page()
    {
        // This page will be under "Settings"
        add_options_page(
            __('Settings Admin', 'solidopinion-comments'),
            __('SolidOpinion Comments', 'solidopinion-comments'), 
            'manage_options', 
            'so_comments',
            array( $this, 'create_admin_page' )
        );
    }

    /**
     * Options page callback
     */
    public function create_admin_page()
    {
        global $wp;
        // Set class property
        $this->options = get_option('so_options');
        $action = isset($_REQUEST['action']) ? trim($_REQUEST['action']) : '';
        $subaction = isset($_REQUEST['subaction']) ? trim($_REQUEST['subaction']) : '';
        $shortname = isset($_REQUEST['shortname']) ? trim($_REQUEST['shortname']) : '';
        $lang = get_language();
        $so_shortname = (isset($this->options['so_shortname']) && ($this->options['so_shortname']!='')) ? $this->options['so_shortname'] : '';
        $so_site_data = parse_url(get_home_url());
        $so_site_url  = $so_site_data['host'];
        $so_site_title = get_bloginfo('name');
        
        $current_url  = 'http';
        $server_https = $_SERVER["HTTPS"];
        $server_name  = $_SERVER["SERVER_NAME"];
        $server_port  = $_SERVER["SERVER_PORT"];
        $request_uri  = $_SERVER["REQUEST_URI"];
        $current_shortname = ($shortname!='') ? $shortname : $so_shortname;
         
        if ($server_https == "on") $current_url .= "s";
        $current_url .= "://";
        if ($server_port != "80") $current_url .= $server_name . ":" . $server_port . $request_uri;
        else $current_url .= $server_name . $request_uri;
        
        if ((!$action || ($action == 'install')) && ($subaction == 'setsite')) {
		
          if ($shortname != '') {
			if ($shortname != $so_shortname){
			  do_action('export_script');
		    }
            $so_options['so_shortname'] = $shortname;
            update_option('so_options', $so_options);
            $so_shortname = $shortname;
            remove_action( 'admin_notices', 'so_settings_warning' );
          }
		  
        } elseif (($action == 'install') && ($subaction == 'unset') && ($shortname != '')) {
          if ($shortname == $so_shortname) {
            delete_option('so_options');
            $so_shortname = '';
          }
        }
        ?>
        
        <link rel="stylesheet" id="so-css"  href="<?php echo plugins_url('media/css/styles.css', dirname(__FILE__)) . '?ver=' . get_bloginfo('version'); ?>" type="text/css" media="all" />
        <div class="wrap">
            <?php screen_icon(); ?>
            <h2><?php echo __('SolidOpinion Comments Settings', 'solidopinion-comments'); ?></h2>
            <div id="so-main">
              <br><br>
              <?php if ($so_shortname) { ?>
                <iframe src="<?php echo SO_BACKEND_URL; ?>settings/<?php echo $so_shortname; ?>/?mode=so_comments&url=<?php echo $so_site_url; ?>&shortname=<?php echo $current_shortname; ?>&cs=<?php echo $so_shortname; ?>&title=<?php echo $so_site_title; ?>&ru=<?php echo urlencode($current_url); ?>" width="100%" height="700"></iframe>
              <?php } else { ?>
                <iframe src="<?php echo SO_BACKEND_URL; ?>getsites/?mode=so_comments&url=<?php echo $so_site_url; ?>&shortname=<?php echo $current_shortname; ?>&cs=<?php echo $so_shortname; ?>&title=<?php echo $so_site_title; ?>&ru=<?php echo urlencode($current_url); ?>" width="100%" height="700"></iframe>
              <?php } ?>
              <br>
            </div>
        </div>
		<div id="export_in_progress" class="hidden"><?php echo __('We\'re currently working on your comments export...', 'solidopinion-comments')?></div>
		<div id="do_export" class="hidden"><?php echo __('Would you like to import your existing comments to SolidOpinion?', 'solidopinion-comments')?></div>
        <?php
    }
	
	
	public function prefix_ajax_export_to_xml(){
		
		global $wpdb;
		$options = get_option('so_options');
		$so_shortname = $options['so_shortname'];
		$SMTP = new SMTPMailer();
		$today = date("m/d/y");
		
		$count = $wpdb->get_var('SELECT COUNT(*) FROM '. $wpdb->prefix . 'comments WHERE `comment_author_email`<>"" AND `comment_content`<>"" ');
		if ($count >= 300 ) {
			$message = 'shortname - "'.$so_shortname.'"<br>';
			$message .= 'date - '. $today .'<br>';
			$message .= $so_shortname.' would like to make WordPress comments import';
			
			if ($SMTP -> smtpmail( HELP_EMAIL, 'Import request for shortname "'. $so_shortname . '" ' . $today, $message, false, false)){
				$this->so_export_notice('warning',__('Great!', 'solidopinion-comments'),__('Our support team will contact you ASAP via email used for SolidOpinion registration to make an import! Alternatively you can drop us a line on help@solidopinion.com.', 'solidopinion-comments'));
				wp_die();
			}
			wp_die();
		}
		$result_comments = $wpdb->get_results('SELECT `comment_ID`,`comment_post_ID`,`comment_content`, `comment_date`, `comment_author_email`, `comment_author` FROM '. $wpdb->prefix . 'comments WHERE `comment_author_email`<>"" AND `comment_content`<>"" ', ARRAY_A);
		
		$doc = new DOMDocument("1.0", "UTF-8");
		$solid = $doc->createElement( 'solid' );
		
		
		$post_ids = array();
		foreach ($result_comments as $key => $value) {
			$post_ids[] = $value['comment_post_ID'];
		}
		$result_posts = $wpdb->get_results('SELECT `ID`, `post_title`, `guid` FROM '. $wpdb->prefix . 'posts WHERE `ID` IN ('.implode(",", $post_ids).')', ARRAY_A);
		
		$title = 'XML_import_'.$so_shortname.'_date('.date("m-d-y").')';
		$folder = plugin_dir_path( __FILE__ ) . '../export/';
		$filename = $folder.$title.'.xml';
		
		foreach ($result_posts as $key => $value) {
			$thread = $doc->createElement( 'thread' );
			$thread->setAttribute('id', $value['ID']);
			$thread->appendChild($doc->createElement( 'link', $value['guid']));
			$thread->appendChild($doc->createElement( 'title', $value['post_title']));
			$solid->appendChild($thread);
		}
		foreach ($result_comments as $key => $value) {
			$post = $doc->createElement( 'post' );
			$post->setAttribute('id', $value['comment_ID']);
			$post->appendChild($doc->createElement( 'id' ));
			$post->appendChild($doc->createElement( 'message', '<![CDATA['.$value['comment_content'].']]>'));
			$post->appendChild($doc->createElement( 'createdAt', $value['comment_date']));
			$author = $doc->createElement( 'author' );
			$author->appendChild($doc->createElement( 'email', $value['comment_author_email'] ));
			$author->appendChild($doc->createElement( 'name', $value['comment_author'] ));
			$post->appendChild($author);
			$thread = $doc->createElement( 'thread');
			$thread->setAttribute('id', $value['comment_post_ID']);
			$post->appendChild($thread);
			$solid->appendChild($post);
		}
		
		$doc->appendChild( $solid );
		$doc->save($filename);
		
		$zip = new ZipArchive();
		$zfile = $folder.$title.'.zip';
		if ($zip->open($zfile, ZipArchive::CREATE)==TRUE) {
			$zip->addFile($filename, $title.'.xml');
			$zip->close();
			
			$file_size = filesize($zfile); 
			$handle = fopen($zfile, "r"); 
			$content = fread($handle, $file_size); 
			fclose($handle); 
			$content = chunk_split(base64_encode($content)); 
		
			$message = 'shortname - "'.$so_shortname.'"<br>';
			$message .= 'date - '. $today .'<br>';
			$message .= 'XML file for import for shortname - "'.$so_shortname.'"<br>';
			$message .= 'filename - '.$title.'.zip' ;

			if ($SMTP -> smtpmail( INTEGRATION_EMAIL, 'Import XML for shortname "'. $so_shortname . '"', $message, $title, $content )){
				$this->so_export_notice('success',__('Export successfully done!', 'solidopinion-comments'),__('Import comments to your site will be completed during 48 hours. You\'ll get notification to email. You can also contact us via support@solidopinion.com. Thank you!', 'solidopinion-comments'));
				unlink($filename);
				unlink($zfile);
				wp_die();
			}
		}
		$this->so_export_notice('error',__('Oops! Something went wrong.', 'solidopinion-comments'),__('Yo make your comments import completed please contact us via support@solidopinion.com. Thank you!', 'solidopinion-comments'));
		wp_die();
	}

	public function so_export_notice($type, $title, $text = ''){
		if ($type == 'warning') $class = 'updated highlight';
		if ($type == 'error') $class = 'error';
		if ($type == 'success') $class = 'updated';
	
		echo '<div class="'.$class.'"><p style="font-size: 14px;"><strong>'.$title.'</strong> 
		'. $text.'</p></div>';
	}


	
    /**
     * Register and add settings
     */
    public function page_init()
    {   
        register_setting(
            'so_option_group', // Option group
            'so_options', // Option name
            array( $this, 'sanitize' ) // Sanitize
        );

        add_settings_section(
            'setting_section_id', // ID
            '', // Title
            array( $this, 'print_section_info' ), // Callback
            'so_comments' // Page
        );  

        add_settings_field(
            'so_shortname', 
            __('Integration shortname', 'solidopinion-comments'), 
            array( $this, 'shortname_callback' ), 
            'so_comments',
            'setting_section_id'
        );
        
        
    }
	
    /**
     * Sanitize each setting field as needed
     *
     * @param array $input Contains all settings fields as array keys
     */
    public function sanitize( $input )
    {
        $new_input = array();

        if( isset( $input['so_shortname'] ) )
            $new_input['so_shortname'] = sanitize_text_field( $input['so_shortname'] );

        return $new_input;
    }

    /** 
     * Print the Section text
     */
    public function print_section_info()
    {
        print '';//__('Enter your settings below:', 'solidopinion-comments');
    }

    /** 
     * Get the settings option array and print one of its values
     */
    public function shortname_callback()
    {
        printf(
            '<input type="text" id="so_shortname" name="so_options[so_shortname]" value="%s" />',
            (isset($this->options['so_shortname']) && $this->options['so_shortname']) ? esc_attr( $this->options['so_shortname']) : (isset($_REQUEST['shortname']) ? trim($_REQUEST['shortname']) : '')
        );
    }
}