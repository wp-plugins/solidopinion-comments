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
        
        if (!(($_REQUEST['subaction'] == 'setsite') && ($_REQUEST['shortname'] != ''))) {
          add_action('admin_notices', 'so_settings_warning');
        }
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
          $so_options = get_option('so_options');
          if ($shortname != '') {
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
            <?php /*if (!$so_shortname) { ?>
            <ul id="so-tabs" style="float:left;">
              <li<?php echo (!$action || ($action == 'install')) ? ' class="selected"' : ''; ?>>
                <?php echo sprintf(__('Welcome to SolidOpinion! Thank you for joining us!<br>To make SolidOpinion comments enable on your site please %sAdd new integration%s.', 'solidopinion-comments'), '<a href="'.get_settings('siteurl').'/wp-admin/options-general.php?page=so_comments">', '</a>'); ?>
              </li>
            </ul>
            <?php }*/ ?>
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
        <?php
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