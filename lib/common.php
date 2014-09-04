<?php 
function get_include_contents($filename) {
    if (is_file($filename)) {
        ob_start();
        include $filename;
        return ob_get_clean();
    }
    return false;
}

function get_language(){
    $lang_data = get_locale();
    if (isset($lang_data) && $lang_data){
        $langs = explode('_', $lang_data);
        if ($langs && isset($langs[0]) && $langs[0]){
            return strtolower($langs[0]);
        }
    }
    return 'en';
}

function get_so_language_id(){
    $languages_ids = array(
        'en' => 2,
        'ru' => 1,
        'ua' => 1,
        'uk' => 1
    );
    $current_lang = get_language();
    if (isset($languages_ids[$current_lang])){
        return $languages_ids[$current_lang];
    }
    return 2;
}

function so_community_widget($args) {
    $so_option = get_option('so_options');
    if ( !($so_option && isset($so_option['so_shortname']) && ($so_option['so_shortname']!='')) ) {
        return;
    }
    extract($args);
    echo $before_widget;
    echo $before_title;
    echo __('Community', 'solidopinion-comments');
    echo $after_title;
    echo str_replace(array('%%SO_SITENAME%%'), array($so_option['so_shortname']), get_include_contents(SO_COMMENTS_DIR . '/templates/community_template.php'));; 
    echo $after_widget; 
} 

function register_so_community_widget() {
    register_sidebar_widget(__('SolidOpinion Community', 'solidopinion-comments'), 'so_community_widget');
    wp_register_sidebar_widget(
        'so_widget_1',
        __('SolidOpinion Community', 'solidopinion-comments'),
        'so_community_widget',
        array(                  // options
            'description' => __('SolidOpinion Community Widget', 'solidopinion-comments')
        )
    );
}

function so_get_comments_number($anchor='#comments') {
    $so_option = get_option('so_options');
    if ( !($so_option && isset($so_option['so_shortname']) && ($so_option['so_shortname']!='')) ) {
        return;
    }
    $link_data = parse_url(get_permalink());
    $tmp_so_sitename = $so_option['so_shortname'];
    $tmp_so_thread_url = $link_data['path'] . ((isset($link_data['query']) && ($link_data['query'] != '')) ? '?' . $link_data['query'] : '');
    $return = is_home() ? str_replace(array('%%SO_SITENAME%%', '%%SO_THREAD_URL%%'), array($tmp_so_sitename, $tmp_so_thread_url), get_include_contents(SO_COMMENTS_DIR . '/templates/counter_template.php')) : '';
    return $return;
}

function so_comment_template($comment_template) {
    global $post;
    $so_option = get_option('so_options');
    if ( !( is_singular() && ( have_comments() || 'open' == $post->comment_status ) ) || !($so_option && isset($so_option['so_shortname']) && ($so_option['so_shortname']!='')) ) {
        return;
    }
    return SO_COMMENTS_DIR . '/templates/comments_template.php';
}

function so_comments_uninstall()
{
    if (!current_user_can('activate_plugins')) return;

    //if ( __FILE__ != WP_UNINSTALL_PLUGIN ) return;
    
    $so_option = get_option('so_options');
    if (isset($so_option) && $so_option){
        delete_option('so_options');
    }

}
