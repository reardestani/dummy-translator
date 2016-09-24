<?php

/**
 * Create a new table class that will extend the WP_List_Table
 */
class WPDT_Plugins_List_Table extends WP_List_Table
{

  private $generated = false;

    /**
     * Prepare the items for the table to process
     *
     * @return Void
     */
    public function prepare_items()
    {
      $columns = $this->get_columns();
      $hidden = $this->get_hidden_columns();

      $data = $this->table_data();

      $this->_column_headers = array($columns, $hidden);
      $this->items = $data;
    }

    /**
     * Override the parent columns method. Defines the columns to use in your listing table
     *
     * @return Array
     */
    public function get_columns()
    {
      $columns = array(
        'name'       => __('Name', 'dummy-translator' ),
        'textdomain' => __('Text Domain', 'dummy-translator' ),
        'dummytext'  => __('Dummy Text', 'dummy-translator' ),
        'file'       => __('Pot File', 'dummy-translator' ),
        'actions'    => __('Actions', 'dummy-translator' )
      );

      return $columns;
    }

    /**
     * Define which columns are hidden
     *
     * @return Array
     */
    public function get_hidden_columns()
    {
        return array();
    }

    /**
     * Define the sortable columns
     *
     * @return Array
     */
    public function get_sortable_columns()
    {
        return array();
    }

    /**
     * Get the table data
     *
     * @return Array
     */
    private function table_data()
    {
      $plugins = get_plugins();
      $dummytext   = '<span contenteditable="true">' . esc_attr( get_option( 'wpdt_dummytext' ) ) . '</span>';

      $data = array();

      // Remove the plugin from the list
      unset($plugins['dummy-translator/dummy-translator.php']);

      foreach ($plugins as $key => $value) {



        if( is_plugin_active( $key ) ) {



          preg_match("/.+\//", $key, $plugin);

          $plugin = rtrim($plugin[0], '/');

          $file        = $this->build_file_column( $plugin, $value['TextDomain'] );
          $file        = $file[1];


          $data[] = array(
                      'name'        => esc_html( $value['Name'] ),
                      'textdomain'  => esc_html( $value['TextDomain'] ),
                      'dummytext'   => $dummytext,
                      'file'        => $file,
                      'actions'     => $this->build_actions_column( $plugin, $value['TextDomain'] )
                      );

          }
      }

      return $data;
    }

    /**
     * Define what data to show on each column of the table
     *
     * @param  Array $item        Data
     * @param  String $column_name - Current column name
     *
     * @return Mixed
     */
    public function column_default( $item, $column_name )
    {
        switch( $column_name ) {
          case 'name':
          case 'textdomain':
          case 'dummytext':
          case 'file':
          case 'actions':
                return $item[ $column_name ];

            default:
                return print_r( $item, true ) ;
        }
    }

    /**
   * Display the table
   *
   * @since 3.1.0
   * @access public
   */
  public function display()
  {
    $singular = $this->_args['singular'];

    //$this->display_tablenav( 'top' );

    $this->screen->render_screen_reader_content( 'heading_list' );
    ?>
      <table class="wp-list-table <?php echo implode( ' ', $this->get_table_classes() ); ?>">
        <thead>
          <tr>
            <?php $this->print_column_headers(); ?>
          </tr>
        </thead>

        <tbody id="the-list"<?php
          if ( $singular ) {
            echo " data-wp-lists='list:$singular'";
          } ?>>
          <?php $this->display_rows_or_placeholder(); ?>
        </tbody>

      </table>
    <?php
  }


  /**
   * Find Pot files in a plugin. It loads the files based on below criteria
   * *.pot
   * false
   *
   * @return string | false
   */
    public function find_pot_file( $plugin )
    {

      $Directory = new RecursiveDirectoryIterator(WP_PLUGIN_DIR . '/' . $plugin);
      $Iterator  = new RecursiveIteratorIterator($Directory);

      // Get only .pot files
      $Regex = new RegexIterator($Iterator, '/^.+\.pot$/i', RecursiveRegexIterator::GET_MATCH);

      foreach ($Regex as $potfile) {
        if ( $potfile[0] )  {
          return $potfile[0];
        } else {
          return false;
        }
      }

    }

  /**
   * Find Pot files in a plugin. It loads the files based on below criteria
   * *.pot
   * false
   *
   * @return string | false
   */
    public function find_generated_pot_file( $textdomain )
    {

      $pot_file = $textdomain . '.pot';


      if ( file_exists( WPDT_PLUGIN_TRANSLATIONS_DIR . '/' . $pot_file ) ) {
        return WPDT_PLUGIN_TRANSLATIONS_DIR . '/' . $pot_file;
      }

      return false;

      // $Directory = new RecursiveDirectoryIterator( WPDT_PLUGIN_TRANSLATIONS_DIR );
      // $Iterator  = new RecursiveIteratorIterator( $Directory );
      //
      // // Get only .pot files
      // $Regex = new RegexIterator($Iterator, '/^.+\.pot$/i', RecursiveRegexIterator::GET_MATCH);
      //
      // foreach ($Regex as $potfile) {
      //   if ( $potfile[0] )  {
      //     return $potfile[0];
      //   } else {
      //     return false;
      //   }
      // }

    }

    /**
     * Find Po files in a plugin. It loads the files based on below criteria
     * *-en_US.po
     * *.po
     * false
     *
     * @return string | false
     */
    public function find_pofile( $plugin )
    {

      $Directory = new RecursiveDirectoryIterator(WP_PLUGIN_DIR . '/' . $plugin);
      $Iterator  = new RecursiveIteratorIterator($Directory);

      // Get only .po files
      $Regex = new RegexIterator($Iterator, '/^.+\.po$/i', RecursiveRegexIterator::GET_MATCH);

      foreach ($Regex as $pofile) {
        if ( preg_match( '/en_US.po$/', $pofile[0] ) )  {
          return $pofile[0];
        }
      }

      return false;

    }

  /**
   * Find Pot / Po file. It searches in
   * 1. Theme language directory
   * 2. WordPress global language directory
   *
   * @since 1.0
   * @access public
   */
    public function find_language_file( $plugin = '', $textdomain )
    {
      $lang_file = '';
      $pot_file   = $this->find_pot_file( $plugin );
      $generated_pot_file   = $this->find_generated_pot_file( $textdomain );
      $pofile    = $this->find_pofile( $plugin );

      if ( $pot_file ) {
        $this->generated = false;
        return $lang_file = $pot_file;
      }

      if ( $generated_pot_file ) {
        $this->generated = true;
       return $lang_file = $generated_pot_file;
      }

      return false;

    }

  /**
   * Build Pot / Po file column
   *
   * @since 1.0
   * @access public
   */
  public function build_file_column( $plugin, $textdomain )
  {
    // Find the .pot or .po file
    $file = $this->find_language_file( $plugin, $textdomain );

    // Generate a nonce
    $generate_nonce = wp_create_nonce( 'wpdt-generate-' . $textdomain );

    // If .pot or .po file exists, return the file otherwise return the generate link
    if ( file_exists( $file ) ) {
        return ['success', $file];
    } else {
        return ['error', '<span>No file. <a href="#" data-nonce="' . $generate_nonce . '" data-plugin="' . $plugin . '" data-type="plugin" data-action="generate" class="wpdt-action">' . __( 'Generate', 'dummy-translator' ) . '</a></span>'];
    }
  }


  /**
   * Build actions column
   *
   * @since 1.0
   * @access public
   */
    public function build_actions_column( $plugin, $textdomain )
    {
      // Check the availability of the file
      $file = $this->build_file_column( $plugin, $textdomain );

      // Return false if no file found
      if ( $file[0] == 'error' ) {
        return;
      }

      $filename = WPDT_PLUGIN_TRANSLATIONS_DIR . '/' . $textdomain . '.mo';

      // Generate nonces
      $translate_nonce = wp_create_nonce( 'wpdt-translate-' . $textdomain );
      $delete_nonce = wp_create_nonce( 'wpdt-delete-' . $textdomain );

      if ( file_exists( $filename ) ) {
          return '<a href="#" data-nonce="' . $translate_nonce . '" data-plugin="' . $plugin . '" data-type="plugin" data-generated="' . $this->generated . '" data-action="translate" class="wpdt-action">' . __( 'Retranslate', 'dummy-translator' ) . '</a> &middot <a href="#" data-nonce="' . $delete_nonce . '" data-action="delete" class="wpdt-action wpdt-error">' . __( 'Delete', 'dummy-translator' ) . '</a>';
      } else {
          return '<a href="#" data-nonce="' . $translate_nonce . '" data-textdomain="' . $textdomain . '" data-plugin="' . $plugin . '" data-type="plugin" data-generated="' . $this->generated . '" data-action="translate" class="wpdt-action">' . __( 'Translate', 'dummy-translator' ) . '</a>';
      }
    }



}
