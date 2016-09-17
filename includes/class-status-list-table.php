<?php

/**
 * Create a new table class that will extend the WP_List_Table
 */
class WPDT_Status_List_Table extends WP_List_Table
{
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
      'title'       => __('Title', 'dummy-translator' ),
      'descripiton' => __('Description', 'dummy-translator' ),
      'solutions'   => __('Possible solutions', 'dummy-translator' ),
      'status'   => __('Status', 'dummy-translator' )
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

    $data = array();

    $data[] = $this->check_translations_directory_permission();
    $data[] = $this->check_translations_directory();

    //$this->check_error_found( $data );

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
        case 'title':
        case 'descripiton':
        case 'solutions':
        case 'status':
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
   * Check the Translations directory permission
   *
   * @since 1.0.0
   * @access public
   */
  public function check_translations_directory_permission()
  {

    $permission = substr(sprintf('%o', fileperms(WPDT_PLUGIN_DIR)), -4);

    if ( $permission < 754 ) {
      $status = '<span class="wpdt-error">
                  <span class="dashicons dashicons-no"></span>
                  ' . $permission . '
                </span>';
    } else {
      $status = '<span class="wpdt-success">
                  <span class="dashicons dashicons-yes"></span>
                  ' . $permission . '
                </span>';
    }

    $data = array(
                'title'        => 'Plugin direcory',
                'descripiton'  => 'Plugin directory needs to have 0755 permission.',
                'solutions'   => 'Change <strong>dummy-translator</strong> directory permission to 755 in wp-content/uploads',
                'status'      => $status
                );

    return $data;
  }

  /**
   * Check the Translations directory permission
   *
   * @since 1.0.0
   * @access public
   */
  public function check_translations_directory()
  {
    $status = '';


    if ( file_exists( WPDT_PLUGIN_TRANSLATIONS_DIR ) ) {
      $status .= '<span class="wpdt-success">
                  <span class="dashicons dashicons-yes"></span>
                  Found
                </span>';

      $permission = substr(sprintf('%o', fileperms(WPDT_PLUGIN_TRANSLATIONS_DIR)), -4);

      if ( $permission < 754 ) {
        $status .= '<br><span class="wpdt-error">
                    <span class="dashicons dashicons-no"></span>
                    ' . $permission . '
                  </span>';
      } else {
        $status .= '<br><span class="wpdt-success">
                    <span class="dashicons dashicons-yes"></span>
                    ' . $permission . '
                  </span>';
      }

    } else {
      $status .= '<span class="wpdt-error">
                  <span class="dashicons dashicons-no"></span>
                  Not found.
                </span>';


    }





    $data = array(
                'title'        => 'Translations directory',
                'descripiton'  => 'By the activation of the plugin, a <strong>Translations</strong> directory has to be created in the plugin with <strong>0755</strong> permission.',
                'solutions'   => __( 'Resolve above issue otherwise create a <strong>translations</strong> directory in <strong>wp-content/plugins/dummy-translator</strong> and set the permission to <strong>0755</strong>', 'dummy-translator' ),
                'status'     => $status
                );

    return $data;
  }

  public function check_error_found( ) {


    $data = array();

    $data[] = $this->check_translations_directory_permission();
    $data[] = $this->check_translations_directory();

    function convert_multi_array($array) {
      $out = implode("&",array_map(function($a) {
        return implode("~",$a);
    },$array));
    return $out;
  }

  //echo $data;
      $da = convert_multi_array($data);

      if ( preg_match( "/wpdt-error/",  $da) ) {
          return true;
      }
        return false;
  }

}
