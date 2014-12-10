<?php
/*
Plugin Name: Simple Google Translate Widget
Plugin URI: http://internet-pr-beratung.de/simple-google-translate-widget/
Description: Zeigen Deinen Lesern Deinen Inhalt in ihrer Sprache mit diesem Google Übersetzung Widget.
Author: Sammy Zimmermanns
Version: 1.0
Author URI: http://internet-pr-beratung.de
*/

function simple_google_translate_control() {

  $options = get_sgt_options();

  if ($_POST['wp_sgt_Submit']){

    $options['wp_sgt_WidgetTitle'] = htmlspecialchars($_POST['wp_sgt_WidgetTitle']);
    $options['wp_sgt_sctext_wlink'] = htmlspecialchars($_POST['wp_sgt_sctext_wlink']);
    update_option("widget_simple_google_translate", $options); 

}
?>

  <p><strong>Nutze die unteren Optionen um deutsche Wörter zu übersetzen.</strong></p>
  <p>
    <label for="wp_sgt_WidgetTitle">Text Titel: </label>
    <input type="text" id="wp_sgt_WidgetTitle" name="wp_sgt_WidgetTitle" value="<?php echo ($options['wp_sgt_WidgetTitle'] =="" ? "Übersetze" : $options['wp_sgt_WidgetTitle']); ?>" />
  </p>
 
 <p>
    <label for="wp_sgt_sctext_wlink">Unterstützen Sie mein Plugin, in dem Sie einen Hinweis zu meiner Seite internet-pr-beratung.de unter dem Widget anzeigen.</label><p align="right">Aktiviere: 
    <input type="checkbox" id="wp_sgt_sctext_wlink" name="wp_sgt_sctext_wlink" <?php echo ($options['wp_sgt_sctext_wlink'] == "on" ? "checked" : "" ); ?> /></p>
  </p>
  
  <p>
    <input type="hidden" id="wp_sgt_Submit" name="wp_sgt_Submit" value="1" />
  </p>

<?php
}


function get_sgt_options() {

  $options = get_option("widget_simple_google_translate");
  if (!is_array( $options )) {
    $options = array(
                     'wp_sgt_WidgetTitle' => 'Übersetzen',
                     'wp_sgt_sctext_wlink' => ''
                    );
  }
  return $options;
}

function get_infos ($sex, $unique, $hit=false) {

  global $wpdb;
  $table_name = $wpdb->prefix . "sc_log";
  $options = get_sgt_options();
  $sql = '';
  $stime = time()-$sex;
  $sql = "SELECT COUNT(".($unique ? "DISTINCT IP" : "*").") FROM $table_name where Time > ".$stime;

  if ($hit)
   $sql .= ' AND IS_HIT = 1 ';

  if ($options['wp_sgt_sctext_bots_filter'] > 1)
      $sql .= ' AND IS_BOT <> 1';

  return number_format_i18n($wpdb->get_var($sql));
  }

function view() {

  global $wpdb;
  $options = get_sgt_options();
  $table_name = $wpdb->prefix . "sc_log";

?>

<div align="center">
<div id="google_translate_element"></div>
<span><script type="text/javascript">
//<![CDATA[
function googleTranslateElementInit() {
  new google.translate.TranslateElement({
    pageLanguage: 'de',
    layout: google.translate.TranslateElement.InlineLayout.SIMPLE
  }, 'google_translate_element');
}
//]]>
</script><script src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>
</script></span></div>

<?php if ($options['wp_sgt_sctext_wlink'] == "on") { ?>
<br /><p align="right"><small>Widget von <a href="http://internet-pr-beratung.de" target="_blank">internet-pr-beratung.de</a></small></p>
<?php } ?>

<?php
}

function widget_simple_google_translate($args) {
  extract($args);

  $options = get_sgt_options();

  echo $before_widget;
  echo $before_title.$options["wp_sgt_WidgetTitle"];
  echo $after_title;
  view();
  echo $after_widget;
}


function is_hit ($ip) {

   global $wpdb;
   $table_name = $wpdb->prefix . "sc_log";

   $user_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name where ".time()." - Time <= 1000 and IP = '".$ip."'");

   return $user_count == 0;
}

function wp_sgt_install_db () {
   global $wpdb;

   $table_name = $wpdb->prefix . "sc_log";
   $gTable = $wpdb->get_var("show tables like '$table_name'");
   $gColumn = $wpdb->get_results("SHOW COLUMNS FROM ".$table_name." LIKE 'IS_BOT'");
   $hColumn = $wpdb->get_results("SHOW COLUMNS FROM ".$table_name." LIKE 'IS_HIT'");

   if($gTable != $table_name) {

      $sql = "CREATE TABLE " . $table_name . " (
           IP VARCHAR( 17 ) NOT NULL ,
           Time INT( 11 ) NOT NULL ,
           IS_BOT BOOLEAN NOT NULL,
           IS_HIT BOOLEAN NOT NULL,
           PRIMARY KEY ( IP , Time )
           );";

      require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
      dbDelta($sql);

   } else {
     if (empty($gColumn)) {  //old table version update

       $sql = "ALTER TABLE ".$table_name." ADD IS_BOT BOOLEAN NOT NULL";
       $wpdb->query($sql);
     }

     if (empty($hColumn)) {  //old table version update

       $sql = "ALTER TABLE ".$table_name." ADD IS_HIT BOOLEAN NOT NULL";
       $wpdb->query($sql);
     }
   }
}

function simple_google_translate_init() {

  wp_sgt_install_db ();
  register_sidebar_widget(__('Simple Google Translate'), 'widget_simple_google_translate');
  register_widget_control(__('Simple Google Translate'), 'simple_google_translate_control', 300, 200 );
}

function uninstall_sc(){

  global $wpdb;
  $table_name = $wpdb->prefix . "sc_log";
  delete_option("widget_simple_google_translate");
  delete_option("wp_sgt_WidgetTitle");
  delete_option("wp_sgt_sctext_wlink");

  $wpdb->query("DROP TABLE IF EXISTS $table_name");
}

function add_sgt_stylesheet() {
            wp_register_style('scStyleSheets', plugins_url('sgt-styles.css',__FILE__));
            wp_enqueue_style( 'scStyleSheets');
}

add_action("plugins_loaded", "simple_google_translate_init");
add_action('wp_print_styles', 'add_sgt_stylesheet');

register_deactivation_hook( __FILE__, 'uninstall_sc' );
register_activation_hook( __FILE__,'sgtinst_activate');
register_deactivation_hook( __FILE__,'sgtuni_deactivate');
add_action('admin_init', 'installredirect_redirect');

function installredirect_redirect() {
if (get_option('installredirect_do_activation_redirect', false)) { delete_option('installredirect_do_activation_redirect'); wp_redirect('../wp-admin/widgets.php');
}
}

?>