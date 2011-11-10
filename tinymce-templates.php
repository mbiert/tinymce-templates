<?php
/*
Plugin Name: TinyMCE Templates
Plugin URI: http://firegoby.theta.ne.jp/wp/tinymce_templates
Description: Manage & Add Tiny MCE template.
Author: Takayuki Miyauchi
Version: 2.0.0
Author URI: http://firegoby.theta.ne.jp/
*/

/*
Copyright (c) 2010 Takayuki Miyauchi (THETA NETWORKS Co,.Ltd).

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.
*/

require_once(dirname(__FILE__).'/includes/class-addrewriterules.php');
require_once(dirname(__FILE__).'/includes/mceplugins.class.php');

define('TINYMCE_TEMPLATES_DOMAIN', 'tinymce_templates');

new tinymceTemplates();


class tinymceTemplates {

private $post_type  = 'tinymcetemplates';
private $meta_param = '_tinymcetemplates-share';
private $base_url;

function __construct()
{
    register_activation_hook(__FILE__, array(&$this, 'activation'));
    $this->base_url = WP_PLUGIN_URL.'/'.dirname(plugin_basename(__FILE__));
    add_action('plugins_loaded', array(&$this, 'plugins_loaded'));
    add_action('admin_menu', array(&$this, 'admin_menu'));
    add_action('save_post', array(&$this, 'save_post'));
    add_filter('mce_css', array(&$this, 'mce_css'));
    add_action('admin_head', array(&$this, 'admin_head'));
}

public function activation()
{
    // do update function
    // do  flush rewrite rules
    flush_rewrite_rules();
}

public function plugins_loaded()
{
    load_plugin_textdomain(
        TINYMCE_TEMPLATES_DOMAIN,
        false,
        dirname(plugin_basename(__FILE__)).'/languages'
    );
    $this->addCustomPostType();
}

public function mce_css($css)
{
    $files = preg_split("/,/", $css);
    $files[] = $this->base_url.'/editor.css';
    $files = array_map('trim', $files);
    return join(",", $files);
}

public function admin_head(){
    global $wp_rewrite;
    $plugin = $this->base_url.'/mce_plugins/plugins/template/editor_plugin.js';
    $lang   = dirname(__FILE__).'/mce_plugins/plugins/template/langs/langs.php';
    $url    = home_url();
    if ($wp_rewrite->using_permalinks()) {
        $this->list_url = $url.'/wp-admin/mce_templates.js';
    } else {
        $this->list_url = $url.'/?mce_templates=1';
    }
    $inits['template_external_list_url'] = $this->list_url;
    new mcePlugins(
        'template',
        $plugin,
        $lang,
        array(&$this, 'addButton'),
        array()
    );
}

public function admin_menu()
{
    remove_meta_box('slugdiv', $this->post_type, 'normal');
}

public function savePostMeta($id)
{
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
        return $id;

    if (isset($_POST['action']) && $_POST['action'] == 'inline-save')
        return $id;

    $p = get_post($id);
    if ($p->post_type === $this->post_type) {
        if (isset($_POST[$this->meta_param])) {
            update_post_meta($id, $this->meta_param, 1);
        }
    }
}

public function addButton($buttons = array())
{
    array_unshift($buttons, '|');
    array_unshift($buttons, 'template');
    return $buttons;
}

private function addCustomPostType()
{
    $args = array(
        'label' => __('Templates', TINYMCE_TEMPLATES_DOMAIN),
        'labels' => array(
            'singular_name' => __('Templates', TINYMCE_TEMPLATES_DOMAIN),
            'add_new_item' => __('Add New Template', TINYMCE_TEMPLATES_DOMAIN),
            'edit_item' => __('Edit Template', TINYMCE_TEMPLATES_DOMAIN),
            'add_new' => __('Add New', TINYMCE_TEMPLATES_DOMAIN),
            'new_item' => __('New Template', TINYMCE_TEMPLATES_DOMAIN),
            'view_item' => __('View Template', TINYMCE_TEMPLATES_DOMAIN),
            'not_found' => __('No templatess found.', TINYMCE_TEMPLATES_DOMAIN),
            'not_found_in_trash' => __(
                'No templates found in Trash.',
                TINYMCE_TEMPLATES_DOMAIN
            ),
            'search_items' => __('Search Templates', TINYMCE_TEMPLATES_DOMAIN),
        ),
        'public' => false,
        'publicly_queryable' => false,
        'exclude_from_search' => true,
        'show_ui' => true,
        'capability_type' => 'post',
        'hierarchical' => false,
        'menu_position' => 100,
        'rewrite' => false,
        'show_in_nav_menus' => false,
        'register_meta_box_cb' => array(&$this, 'addMetaBox'),
        'supports' => array(
            'title',
            'editor',
            'excerpt',
            'revisions',
            'author',
        )
    );

    register_post_type($this->post_type, $args);
}

public function addMetaBox()
{
    add_meta_box(
        'add_meta_box-share',
        __('Share', TINYMCE_TEMPLATES_DOMAIN),
        array(&$this, 'showMetaBox'),
        $this->post_type,
        'side',
        'low'
    );
}

public function showMetaBox($post, $box)
{
    $share = get_post_meta($post->ID, $this->meta_param, true);
    echo '<select name="'.$this->meta_param.'">';
    echo '<option value="0">'.__('Private', TINYMCE_TEMPLATES_DOMAIN).'</option>';
    if ($share) {
        echo '<option value="1" selected="selected">'.__('Shared', TINYMCE_TEMPLATES_DOMAIN).'</option>';
    } else {
        echo '<option value="1">'.__('Shared', TINYMCE_TEMPLATES_DOMAIN).'</option>';
    }
    echo '</select>';
}

} // end class tinymceTemplates


// eof