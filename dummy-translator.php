<?php
/**
 * @package Dummy_Translator
 * @version 1.0.0
 */
/*
Plugin Name: Dummy Translator
Plugin URI: http://wordpress.org/plugins/dummy-translator/
Description: Generate dummy translations to test translation of themes and plugins.
Author: Reza Ardestani
Version: 1.0.0
Text Domain: dummy-translator
*/

define( 'WPDT_VERSION', '1.0' );
define( 'WPDT_PLUGIN', __FILE__ );
define( 'WPDT_PLUGIN_DIR', untrailingslashit( plugin_dir_path( WPDT_PLUGIN ) ) );
define( 'WPDT_PLUGIN_TRANSLATIONS_DIR', WPDT_PLUGIN_DIR . '/translations' );
define( 'WPDT_PLUGIN_URL', untrailingslashit( plugin_dir_url( WPDT_PLUGIN ) ) );

if( ! class_exists( 'WP_List_Table' ) ) {
  require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

require_once( WPDT_PLUGIN_DIR . "/libraries/gettext/src/autoloader.php" );
require_once( WPDT_PLUGIN_DIR . "/libraries/cldr-to-gettext-plural-rules/src/autoloader.php" );
require_once( WPDT_PLUGIN_DIR . "/includes/class-status-list-table.php" );
require_once( WPDT_PLUGIN_DIR . "/includes/class-theme-list-table.php" );
require_once( WPDT_PLUGIN_DIR . "/includes/class-plugins-list-table.php" );

use Gettext\Translations;
use Gettext\Merge;
use Gettext\Translator;

class Dummy_Translator
{

  protected $errors = [];

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_submenu_pages' ) );
		add_action( 'admin_menu', array( $this, 'remove_submenu_pages' ) );
    add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
    add_action( 'wp_ajax_wpdt_translate', array( $this, 'translate' ) );
    add_action( 'wp_ajax_wpdt_delete', array( $this, 'delete' ) );
    add_action( 'wp_ajax_wpdt_generate', array( $this, 'generate' ) );
    add_action( 'init', array( $this, 'load_textdomain' ) );

  	add_action( 'admin_init', array( $this, 'register_settings' ));
  	//add_action( 'admin_init', array( $this, 'set_default_settings' ));

    register_activation_hook( WPDT_PLUGIN, array( $this, 'register_activation_hooks' ) );
	}

  public function register_activation_hooks() {
    $this->set_default_settings();
    $this->create_translations_directory();
  }

  public function create_translations_directory() {
    mkdir(  WPDT_PLUGIN_DIR . '/translations', 0755);
  }

  /**
   * Loads all the translations in WordPress from translations direcory
   *
   * @return Void
   */
  function load_textdomain() {
    // Array of all trnalsations absolute pathes
    $translations = glob( WPDT_PLUGIN_TRANSLATIONS_DIR . '/*.mo' );

    if ( ! empty( $translations ) ) {
      foreach ( $translations as $translation ) {
        $textdomain = strstr( substr( strrchr( $translation, "/" ), 1 ), '.', true );
        load_textdomain( $textdomain, $translation );
      }
    }
  }

  /**
   * Loads all the translations in WordPress from translations dirdecory
   *
   * @return Void
   * @param String
   */
  function enqueue( $hook ) {
    // Return if not on Tools > Dummpy Translator page

    if ( ! preg_match( "/tools_page_dummy-translator/i", $hook ) ) {
        return;
    }

    wp_register_style( 'wpdt_admin_css', WPDT_PLUGIN_URL . '/assets/css/styles.css' );
    wp_enqueue_style( 'wpdt_admin_css' );

  	wp_enqueue_script( 'wpdt_admin_js', WPDT_PLUGIN_URL . '/assets/js/scripts.js', array('jquery') );
  	wp_localize_script( 'wpdt_admin_js', 'ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
  }

  /**
   * Tanslate the Core, a Theme or a Plugin
   *
   * @return String
   */
  function translate() {
    $textdomain = $_POST['textdomain'];
    $dummytext  = $_POST['dummytext'];
    $type       = $_POST['type'];
    $plugin     = $_POST['plugin'];
    $generated     = $_POST['generated'];



    // Check the nonce
    check_ajax_referer( 'wpdt-translate-' . $textdomain,  'nonce');

    if ( $generated != true ) {
      if ( $type == 'core' ) {
        $item_list_table = $this->get_core_list_table();
        $this->copy_core_language_file( $textdomain, $item_list_table );
      } elseif ( $type == 'theme' ) {
        $item_list_table = $this->get_theme_list_table();
        $this->copy_theme_language_file( $textdomain, $item_list_table );
      } else {
        $item_list_table = $this->get_plugins_list_table();
        $this->copy_plugin_language_file( $plugin, $textdomain, $item_list_table );
       }
    }






		$po_file = $this->read_po_file( $textdomain );

    if ( get_option( 'wpdt_mode_translation' ) == 0 ) {
      $po_file_dummy = $this->replace_dummytext( $dummytext, $po_file );
    } else {
      $po_file_dummy = $this->append_dummytext( $dummytext, $po_file );
    }

    $this->save_po_file( $textdomain, $po_file_dummy );

		$this->generate_mo_file( $textdomain );

    echo "success";

  	wp_die();
  }


  function generate( $plugin, $textdomain ) {

    $textdomain = $_POST['textdomain'];
    $dummytext  = $_POST['dummytext'];
    $type       = $_POST['type'];
    $plugin     = $_POST['plugin'];

    // Check the nonce
    check_ajax_referer( 'wpdt-generate-' . $textdomain,  'nonce');

    $Directory = new RecursiveDirectoryIterator( WP_PLUGIN_DIR . '/' . $plugin );
    $Iterator  = new RecursiveIteratorIterator( $Directory );

    // Get only .pot files
    $files = new RegexIterator($Iterator, '/^.+\.php/i', RecursiveRegexIterator::GET_MATCH);

    //Or an interator
    $translations = new Translations();
    foreach ($files as $file) {
      $translations->addFromPhpCodeFile($file, [
        'functions' => [
          '_e'         => 'gettext',
          'esc_html_e' => 'gettext',
          'esc_attr_e' => 'gettext',
          '__'         => 'gettext',
          'esc_html__' => 'gettext',
          'esc_attr__' => 'gettext'
        ]
      ]);
    }

		// Save it in size this plugin trnalations directory
		$translations->toPoFile(WPDT_PLUGIN_DIR . '/translations/' . $textdomain . '.pot');

    // if ( $type == 'core' ) {
    //   $item_list_table = $this->get_core_list_table();
    //   $this->copy_core_language_file( $textdomain, $item_list_table );
    // } elseif ( $type == 'theme' ) {
    //   $item_list_table = $this->get_theme_list_table();
    //   $this->copy_theme_language_file( $textdomain, $item_list_table );
    // } else {
    //   $item_list_table = $this->get_plugins_list_table();
    //   $this->copy_plugin_language_file( $plugin, $textdomain, $item_list_table );
    // }
    //
		// $po_file = $this->read_po_file( $textdomain );
    //
    // if ( get_option( 'wpdt_mode_translation' ) == 0 ) {
    //   $po_file_dummy = $this->replace_dummytext( $dummytext, $po_file );
    // } else {
    //   $po_file_dummy = $this->append_dummytext( $dummytext, $po_file );
    // }
    //
    // $this->save_po_file( $textdomain, $po_file_dummy );
    //
		// $this->generate_mo_file( $textdomain );

    echo "success";

  	wp_die();


    //Using an array
    //$filess = glob( WP_PLUGIN_DIR . '/LayerSlider/**/*.php');

    // $Directory = new RecursiveDirectoryIterator( WP_PLUGIN_DIR . '/' . $plugin );
    // $Iterator  = new RecursiveIteratorIterator( $Directory );
    //
    // // Get only .pot files
    // $files = new RegexIterator($Iterator, '/^.+\.php/i', RecursiveRegexIterator::GET_MATCH);
    //
    // //Or an interator
    // $translations = new Translations();
    // foreach ($files as $file) {
    //   $translations->addFromPhpCodeFile($file, [
    //     'functions' => [
    //       '_e' => 'gettext',
    //       '__' => 'gettext'
    //     ]
    //   ]);
    // }
    //
		// // Save it in size this plugin trnalations directory
		// $translations->toPoFile(WPDT_PLUGIN_DIR . '/translations/' . $textdomain . '.pot');
  }



  /**
   * Copy .pot / .po file from Core, Theme or Plugin to this plugin's
   * translations folder
   *
   * @param String $textdomain
   * @param String $dummytext
   * @param Object $item_list_table
   *
   * @return Void
   */
  function copy_theme_language_file( $textdomain, $item_list_table ) {
    // Create a Translations instance using a po file
		$translations = Gettext\Translations::fromPoFile(  $item_list_table->find_language_file( $textdomain ) );

		// Save it in size this plugin trnalations directory
		$translations->toPoFile(WPDT_PLUGIN_DIR . '/translations/' . $textdomain . '.po');
  }

  /**
   * Copy .pot / .po file from Core, Theme or Plugin to this plugin's
   * translations folder
   *
   * @param String $textdomain
   * @param String $dummytext
   * @param Object $item_list_table
   *
   * @return Void
   */
  function copy_plugin_language_file( $plugin, $textdomain, $item_list_table ) {
    // Create a Translations instance using a po file
		$translations = Gettext\Translations::fromPoFile(  $item_list_table->find_language_file( $plugin ) );

		// Save it in size this plugin trnalations directory
		$translations->toPoFile(WPDT_PLUGIN_DIR . '/translations/' . $textdomain . '.pot');
  }


  /**
   * Copy .pot / .po file from Core, Theme or Plugin to this plugin's
   * translations folder.
   *
   * @param String $textdomain
   *
   * @return String
   */
  function read_po_file( $textdomain ) {
    $po_file = file_get_contents(WPDT_PLUGIN_DIR . '/translations/' . $textdomain . '.pot');

    return $po_file;
  }

  /**
   * Save the generated .po file which includes the dummytext.
   *
   * @param String $textdomain
   * @param String $dummy_po_file
   *
   * @return Void
   */
  function save_po_file( $textdomain, $dummy_po_file ) {
    file_put_contents(WPDT_PLUGIN_DIR . '/translations/' . $textdomain . '.po', $dummy_po_file);
  }

  /**
   * Read the generated .po file which includes the dummytext.
   * Generate .mo file from .po file
   *
   * @param String $textdomain
   *
   * @return Void
   */
  function generate_mo_file( $textdomain ) {
		$translations = Translations::fromPoFile(WPDT_PLUGIN_DIR . '/translations/' . $textdomain . '.po');

		$translations->toMoFile(WPDT_PLUGIN_DIR . '/translations/' . $textdomain . '.mo');
  }

  /**
   * Recieve content of .po file.
   * Replace dummytext with msgstr's value.
   *
   * @param String $dummytext
   * @param String $po_file
   *
   * @return String
   */
  function replace_dummytext( $dummytext, $po_file ) {
    $po_file_dummy = preg_replace("/msgst(r|r\[.]) \"/", "$0" . $dummytext, $po_file );

    return $po_file_dummy;
  }

  /**
   * Recieve content of .po file.
   * Get value of msgid.
   * Append the dummytext to it.
   * Replace it with msgstr's value.
   *
   * @param String $dummytext
   * @param String $po_file
   *
   * @return String
   */
  function append_dummytext( $dummytext, $po_file ) {
    // Source of Regex: https://goo.gl/3FCxhG
    $regex = '~^msgid\h+"(.*)"(?:\Rmsgid_plural\h+"(.*)")?(?:\Rmsgstr(?:\[\d])?\h*"")+$~m';
    $po_file_dummy = preg_replace_callback( $regex, function( $m ) use ( $dummytext ) {
      $loc = $m[0];
      if (isset($m[2])) {
        $loc = str_replace( 'msgstr[1] ""','msgstr[1] "'. $dummytext . '' . $m[2] . '"', $loc );
      }
      return preg_replace( '~^(msgstr(?:\[0])?\h+)""~m', "$1\"$dummytext$m[1]\"", $loc );
    }, $po_file );

    return $po_file_dummy;
  }

  /**
   * Delete .po / .mo files from translations folder
   *
   * @return String
   */
  function delete() {
    $textdomain = $_POST['textdomain'];

    // Check the nonce
    check_ajax_referer( 'wpdt-delete-' . $textdomain,  'nonce');

    $po_file = WPDT_PLUGIN_TRANSLATIONS_DIR . '/' . $textdomain . '.po';
    $mo_file = WPDT_PLUGIN_TRANSLATIONS_DIR . '/' . $textdomain . '.mo';

    if ( unlink( $po_file ) == false || unlink( $mo_file ) == false ) {
      echo 'error';

      wp_die();
    }

    echo 'success';

    wp_die();
  }

  /**
   * Register Dummy Translator page in Tools > Dummy Translator
   *
   * @return Void
   */
	public function register_submenu_pages() {
    add_submenu_page(
      'tools.php',
      'Dummy Translator',
      'Dummy Translator',
      'manage_options',
      'dummy-translator',
      array( $this, 'render_page' )
    );

    add_submenu_page(
      'tools.php',
      'Dummy Translator Options',
      'Dummy Translator Options',
      'manage_options',
      'dummy-translator-options',
      array( $this, 'render_page' )
    );

    add_submenu_page(
      'tools.php',
      'Dummy Translator Status',
      'Dummy Translator Status',
      'manage_options',
      'dummy-translator-status',
      array( $this, 'render_page' )
    );
	}

  /**
   * Remove options page's menu link
   *
   * @return Void
   */
	public function remove_submenu_pages() {
    remove_submenu_page( 'tools.php', 'dummy-translator-status' );
    remove_submenu_page( 'tools.php', 'dummy-translator-options' );
	}

  /**
   * Render the translate page
   *
   * @return Mixed
   */
	public function render_page() {
    // Return options page based on page
    if( $_GET['page'] === 'dummy-translator-status' ) {
      return $this->build_status_page();
    }

    if( $_GET['page'] === 'dummy-translator-options' ) {
      return $this->build_options_page();
    }

    $this->build_translate_page();
	}

  /**
   * Build the page tabs
   *
   * @return Mixed
   */
	public function build_page_tabs() {

    $status_list_table = $this->get_status_list_table();



    //$error="12 ";
    if ( $status_list_table->check_error_found() == true ) {
      $error = 'wpdt-system-error ';
    }

    ?>
      <h2 class="nav-tab-wrapper wp-clearfix">
        <a href="<?php echo admin_url( 'tools.php?page=dummy-translator' ); ?>" class="nav-tab <?php echo ( $_GET['page'] == 'dummy-translator' ) ? 'nav-tab-active' : ''; ?>"><?php _e( 'Translate', 'dummy-translator' ) ?></a>
        <a href="<?php echo admin_url( 'tools.php?page=dummy-translator-options' ); ?>" class="nav-tab <?php echo ( $_GET['page'] == 'dummy-translator-options' ) ? 'nav-tab-active' : ''; ?>"><?php _e( 'Options', 'dummy-translator' ) ?></a>
        <a href="<?php echo admin_url( 'tools.php?page=dummy-translator-status' ); ?>" class="nav-tab  <?php echo $error; echo ( $_GET['page'] == 'dummy-translator-status' ) ? 'nav-tab-active' : ''; ?>"><?php _e( 'System Status', 'dummy-translator' ) ?></a>
      </h2>
    <?php
  }

  /**
   * Build the translate page
   *
   * @return Mixed
   */
	public function build_translate_page() {
    // Instantiate the custom list tables
		$theme_list_table   = $this->get_theme_list_table();
		$plugins_list_table = new WPDT_Plugins_List_Table();

    // Prepare the items to show
		$theme_list_table->prepare_items();
		$plugins_list_table->prepare_items();
    ?>
    <div class="wrap">
      <h1><?php _e( 'Dummy Translator', 'dummy-translator' ) ?> <small><?php echo WPDT_VERSION; ?></small></h1>

      <?php $this->build_page_tabs(); ?>

			<p><?php _e( 'Generate dummy translations to test translation of themes and plugins.', 'dummy-translator' ) ?></p>
      <div>
      	<h2><?php _e('Active Theme', 'dummy-translator' ) ?></h2>
				<?php $theme_list_table->display(); ?>
			</div>

			<br>

      <div>
      	<h2><?php _e('Active Plugins', 'dummy-translator' ) ?></h2>
				<?php $plugins_list_table->display(); ?>
      </div>
    </div>
    <?php
	}

  /**
   * Build the translate page
   *
   * @return Mixed
   */
	public function build_options_page() {
    ?>
    <div class="wrap">
      <h1><?php _e( 'Dummy Translator', 'dummy-translator' ) ?> <small><?php echo WPDT_VERSION; ?></small></h1>

      <?php $this->build_page_tabs(); ?>

      <form method="post" action="options.php">
        <?php settings_fields( 'wpdt-settings-group' ); ?>
        <?php do_settings_sections( 'wpdt-settings-group' ); ?>
        <table class="form-table">
          <tr>
            <th scope="row">
              <?php _e( 'Mode of Translation', 'dummy-translator' ); ?>
            </th>
            <td>
              <fieldset>
                <legend class="screen-reader-text">
                  <span><?php _e( 'Mode of Translation', 'dummy-translator' ); ?></span>
                </legend>
                <p>
                  <label>
                    <input name="wpdt_mode_translation" type="radio" value="0" <?php checked( 0, get_option( 'wpdt_mode_translation', 0 ), true ); ?>> <?php _e( '<strong>Replace</strong> - Replaces all the strings with dummy text', 'dummy-translator' ); ?>
                  </label>
                  <br>
                  <label>
                    <input name="wpdt_mode_translation" type="radio" value="1" <?php checked( 1, get_option( 'wpdt_mode_translation' ), true ); ?>> <?php _e( '<strong>Append</strong> - Append the dummy text to the beginning of the strings', 'dummy-translator' ); ?>
                  </label>
                </p>
              </fieldset>
            </td>
          </tr>
          <tr>
            <th scope="row">
              <label for="wpdt-dummytext-descripiton"><?php _e( 'Dummy Text', 'dummy-translator' ); ?></label>
            </th>
            <td>
              <input name="wpdt_dummytext" type="text" id="wpdt_dummytext" aria-describedby="wpdt-dummytext" value="<?php echo esc_attr( get_option( 'wpdt_dummytext', 'Translated' ) ); ?>" class="regular-text">
              <p class="description" id="wpdt-dummytext-description"><?php _e( 'The defaut dummy text for all  translations.', 'dummy-translator' ); ?></p>
            </td>
          </tr>

        </table>

        <?php submit_button(); ?>

      </form>

    </div>
    <?php
	}

  /**
   * Build status page
   *
   * @return Mixed
   */
	public function build_status_page() {
    $status_list_table   = $this->get_status_list_table();

    $status_list_table->prepare_items();
    ?>
    <div class="wrap">
      <h1><?php _e( 'Dummy Translator', 'dummy-translator' ) ?> <small><?php echo WPDT_VERSION; ?></small></h1>

      <?php $this->build_page_tabs(); ?>

      <p><?php _e( 'It seems there are some issues in the server, please fix all the below issues to be able to use this plugin as expected.', 'dummy-translator' ) ?></p>

      <div>
        <br>
				<?php $status_list_table->display(); ?>
			</div>


    </div>
    <?php
	}

  public function register_settings() {
    //register our settings
    register_setting( 'wpdt-settings-group', 'wpdt_mode_translation' );
    register_setting( 'wpdt-settings-group', 'wpdt_dummytext' );
  }

  public function set_default_settings() {
    update_option( 'wpdt_dummytext', 'Translated', 'yes');
    update_option( 'wpdt_mode_translation', 0, 'yes');
  }

  /**
   * Get theme list table
   *
   * @return String
   */
  private function get_theme_list_table() {
    $theme_list_table = new WPDT_Theme_List_Table();

    return $theme_list_table;
  }

  /**
   * Get theme list table
   *
   * @return String
   */
  private function get_status_list_table() {
    $status_list_table = new WPDT_Status_List_Table();

    return $status_list_table;
  }

  /**
   * Get theme list table
   *
   * @return String
   */
  private function get_plugins_list_table() {
    $plugins_list_table = new WPDT_Plugins_List_Table();

    return $plugins_list_table;
  }

}

$dummy = new Dummy_Translator();
//$dummy->create_translations_directory();
