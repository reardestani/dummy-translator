<?php

/**
 * Create a new table class that will extend the WP_List_Table
 */
class WPDT_Plugins_List_Table extends WP_List_Table
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
        'name'       => 'Name',
        'textdomain' => 'Text Domain',
        'file'        => 'Pot / Po file',
        'actions'    => 'Actions'
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

      $data = array();

      foreach ($plugins as $key => $value) {
        if( is_plugin_active( $key ) ) {
          $data[] = array(
                      'name'        => esc_html( $value['Name'] ),
                      'textdomain'  => esc_html( $value['TextDomain'] ),
                      'file'        => 'twentyfourteen.pot',
                      'actions'     => '<a href="#">Translate</a>'
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
public function display() {
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

}
