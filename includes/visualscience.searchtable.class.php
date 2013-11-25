<?php
/**
 * @file
 * File to generate and handle search and user related queries
 */

class VisualscienceSearch {
  /**
   * Makes the search string safe for PHP.
   * 
   * @param string $search
   *   The search string.
   *   
   * @return string
   *   Safe search string query.
   */
  protected function ensureSearchSafety($search) {
    // TODO: Implement it in a better way!
    return check_plain($search);
  }

  /**
   * Returns the current fields in the visualscience configuration page.
   * 
   * @return array 
   *   Of those fields with their values.
   */
  protected function getFieldsFromConfig() {
    $query = db_select('visualscience_search_config', 'f')
    ->fields('f', array('name', 'mini', 'full', 'first', 'last'));
    $result = $query->execute();
    $final = array();
    for ($i = 0; $record = $result->fetchAssoc(); $i++) {
      $final[$record['name']] = $record;
    }
    return $final;
  }

  /**
   * Gets the value of a given field and given user.
   * 
   * @param array $field
   *   Containing the field configuration.
   * @param object $user
   *   User object from db.
   *   
   * @return string
   *   The content of the queried field.
   */
  protected function getValueOfField($field, $user) {
    $value = $user->$field['name'];
    $if_def_field = field_view_field('user', $user, $field['name']);
    if (gettype($value) == 'object') {
      $value = 'Object';
    }
    if (gettype($value) == 'array' && !empty($if_def_field)) {
      $value = $if_def_field[0]['#markup'];
    }
    if (gettype($value) == 'array') {
      $list = '';
      foreach ($value as $inner_val) {
        if (gettype($inner_val) == 'array') {
          $list .= 'Array';
          break;
        }
        else {
          $list .= $inner_val . '; ';
        }
      }
      $value = $list;
    }
    return $value . '';
  }

  /**
   * Gets the fields'values for a range of users.
   * 
   * @param array  $fields 
   *   The fields asked for.
   * @param int $from   
   *   Start index of users to get.
   * @param int $to     
   *   End index of users to get.
   * 
   * @return array
   *   The array of fields for queried users.
   */
  protected function getUsersFields($fields, $from = 0, $to = 0) {
    if ($to != 0) {
      $users_ids = array();
      while ($from <= $to) {
        array_push($users_ids, $from);
        $from++;
      }
    }
    else {
      $users_ids = $this->getAllUsersIds();
    }
    $users = user_load_multiple($users_ids);
    $user_fields = array();
    foreach ($users as $user) {
      // Check for anonymous user.
      if ($user->name != '') {
        $user_fields[$user->uid] = array();
        foreach ($fields as $field) {
          $value_of_field = $this->getValueOfField($field, $user);
          if ($field['first'] == 1) {
            $user_fields[$user->uid]['first'] = $value_of_field;
          }
          elseif ($field['last'] == 1) {
            $user_fields[$user->uid]['last'] = $value_of_field;
          }
          else {
            $user_fields[$user->uid][$field['name']] = $value_of_field;
          }
        }
      }
    }
    return $user_fields;
  }

  /**
   * Gets the range users' fields in a json format.
   * 
   * @param array $fields 
   *   The fields to get.
   * @param int $from   
   *   Start index to get users.
   * @param int $to     
   *   End index to get users.
   *   
   * @return string         
   *   Json string of all fields for queried useres.
   */
  protected function getJsonUsersFields($fields, $from, $to) {
    return json_encode($this->getUsersFields($fields, $from, $to));
  }

  /**
   * Returns the display configuration for search table in json format.
   * 
   * @param array $fields 
   *   Fields that should be displayed.
   * 
   * @return string         
   *   Json format of the configuration.
   */
  protected function getJsonDisplayConfig($fields) {
    $config = '{"fields": [';
    $end_config = ']';
    foreach ($fields as $field) {
      $config .= '{"name": "' . $field['name'] . '","mini": ' . $field['mini'] . ', "full": ' . $field['full'] . '},';
      if ($field['first'] == 1) {
        $end_config .= ', "first": "' . $field['name'] . '"';
      }
      if ($field['last'] == 1) {
        $end_config .= ', "last": "' . $field['name'] . '"';
      }
    }
    $config = substr($config, 0, strlen($config) - 1) . $end_config . '}';
    return $config;
  }

  /**
   * Returns the ui dof every user in db.
   * 
   * @return array a
   *   Array of all uids.
   */
  protected function getAllUsersIds() {
    $query = db_select('users', 'f')
    ->fields('f', array('uid'));
    $result = $query->execute();
    $final = array();
    for ($i = 0; $record = $result->fetchAssoc(); $i++) {
      array_push($final, $record['uid']);
    }
    return $final;
  }

  /**
   * Returns the max user id.
   * 
   * @return int 
   *   The max user id.
   */
  protected function getMaxUserId() {
    $max_id = db_select('users', 'x')
    ->fields('x', array('uid'))
    ->orderby('uid', 'DESC')
    ->range(0, 1)
    ->execute()
    ->fetchCol();

    return $max_id[0];
  }

  /**
   * Counts the number of users in the db.
   * @return int 
   *   Number of users in db.
   */
  protected function getCountOfUsers() {
    $query = db_select('users', 'f')
    ->fields(NULL, array('uid'));
    $result = $query->execute()->fetchAll();
    return count($result);
  }

  /**
   * Returns the saved searched.
   * 
   * @return string 
   *   To be implemented.
   */
  public function getSavedSearch() {
    // TODO: Implement it.
    if (isset($_GET['search'])) {
      return $_GET['search'];
    }
    return '';
  }

  /**
   * Returns the basic HTML for the search bar.
   * 
   * @param string $search_value
   *   Search string.
   * 
   * @return string
   *   The HTML of the searchbar.
   */
  public function getHtmlSearchBar($search_value = "") {
    $safe_search_val = $this->ensureSearchSafety($search_value);
    return '<div align="center">
    <input type="search" placeholder="Search..." val="' . $safe_search_val . '" class="visualscience-search-main visualscience-search" id="visualscience-search-bar" " onKeyUp="vsUserlist.search();" />
    <div style="width:98%;" align="left">
    <p class="visualscience-right" align="right" style="display:inline;max-width:30%;">' . l(t("Help"), "admin/help/visualscience") . '</p>
    <p class="clickable" style="display:inline;max-width:30%;text-align:center;" align="center"><a onClick="vsUserlist.reloadUserDatabase(0);">Reload User Database</a></p>
    <p class="visualscience-left" align="right" style="visibility:hidden;display:inline;max-width:30%;text-align:center;margin-left:30%;"><a onClick="vsUserlist.saveSearch();">Save/Load</a></p>
    </div>
    </div>';
  }

  /**
   * Returns the HTML for the visualscience page.
   * 
   * @return string 
   *   The html where VisualScience is going to be set up.
   */
  public function getHtmlSearchTable() {
    return '<div id="visualscience-container"></div>';
  }

  /**
   * Gets the JSON of the user database with the config.
   * 
   * @return string 
   *   Script tag with the database in json format.
   */
  public function getJsonDatabase() {
    $fields = $this->getFieldsFromConfig();
    $json_users_and_fields = $this->getJsonUsersFields($fields);
    $json_display_config = $this->getJsonDisplayConfig($fields);
    $search_db = '{"users": ' . $json_users_and_fields . ', "config":' . $json_display_config . '}';
    return '<script type="text/javascript" charset="utf-8">var vsSearchDB = ' . $search_db . ';</script>';
  }

  /**
   * Adds client-side files needed for the application.
   * 
   * @return none 
   *   Files added with druapal api.
   */
  public function getClientSideFiles() {
    global $user;
    global $base_path;
    drupal_add_library('system', 'ui.autocomplete');
    drupal_add_library('system', 'ui.datepicker');
    drupal_add_library('system', 'ui.dialog');
    drupal_add_library('system', 'ui.tabs');
    drupal_add_library('system', 'ui.progressbar');

    drupal_add_js('http://livingscience.ethz.ch/livingscience/livingscience/livingscience.nocache.js', 'external');
    drupal_add_css(drupal_get_path('module', 'visualscience') . '/css/visualscience.css');
    drupal_add_js(drupal_get_path('module', 'visualscience') . '/javascript/lib/visualscience.jquery.layout.js');
    drupal_add_css(drupal_get_path('module', 'visualscience') . '/css/visualscience.jquery.layout.css');
    drupal_add_js(drupal_get_path('module', 'visualscience') . '/javascript/lib/visualscience.jquery.tablesorter.js');
    drupal_add_css(drupal_get_path('module', 'visualscience') . '/css/visualscience.jquery.tablesorter.css');
    drupal_add_js(drupal_get_path('module', 'visualscience') . '/javascript/lib/visualscience.handlebars.js');
    drupal_add_js(drupal_get_path('module', 'visualscience') . '/javascript/lib/visualscience.nddb.js');
    // Settings necessary to VisualScience:
    drupal_add_js(array('installFolder' => $base_path . drupal_get_path('module', 'visualscience') . '/'), 'setting');
    if (isset($user->name)) {
      drupal_add_js(array('username' => $user->name), 'setting');
    }
    drupal_add_js(drupal_get_path('module', 'visualscience') . '/javascript/visualscience.utils.js');
    drupal_add_js(drupal_get_path('module', 'visualscience') . '/javascript/visualscience.database.js');
    drupal_add_js(drupal_get_path('module', 'visualscience') . '/javascript/visualscience.interface.js');
    drupal_add_js(drupal_get_path('module', 'visualscience') . '/javascript/visualscience.text.js');
    drupal_add_js(drupal_get_path('module', 'visualscience') . '/javascript/visualscience.message.js');
    drupal_add_js(drupal_get_path('module', 'visualscience') . '/javascript/visualscience.csv.js');
    drupal_add_js(drupal_get_path('module', 'visualscience') . '/javascript/visualscience.userlist.js');
    drupal_add_js(drupal_get_path('module', 'visualscience') . '/javascript/visualscience.lscomparison.js');
    drupal_add_js(drupal_get_path('module', 'visualscience') . '/javascript/visualscience.search.js');
    drupal_add_js(drupal_get_path('module', 'visualscience') . '/javascript/visualscience.conference.js');
    drupal_add_js(drupal_get_path('module', 'visualscience') . '/javascript/visualscience.livingscience.js');
  }

  /**
   * Returns the configuration for the export action of pattern.
   * 
   * @return array 
   *   With the search table fields configuration.
   */
  public function getPatternConfiguration() {
    return $this->getFieldsFromConfig();
  }

  /**
   * Gets every informations needed for the client-side DB, called through ajax.
   * 
   * @param integer $from    
   *   From which user to load the configurations
   * @param integer $how_many 
   *   How many users to load from $from
   * 
   * @return string           
   *   The json data containing client configuration and users' data
   */
  public function getUsersEntries($from = 0, $how_many = 1000) {
    $final = $from + $how_many;
    $fields = $this->getFieldsFromConfig();
    $json_users_and_fields = $this->getJsonUsersFields($fields, $from, $final);
    if ($from == 0) {
      $max_id = $this->getMaxUserId();
      $nb_users_per_page = variable_get('visualscience_user_per_search_page', 150);
      $nb_usersin_server_d_b = $this->getCountOfUsers();
      $show_messages_button = variable_get('visualscience_show_messages_button');
      $show_csv_button = variable_get('visualscience_show_csv_button');
      $show_living_science_button = variable_get('visualscience_show_livingscience_button');
      // $show_conference_button 
      // = variable_get('visualscience_show_conference_button');.
      $show_conference_button = 0;
    }
    else {
      $max_id = 0;
      $nb_users_per_page = 150;
      $nb_usersin_server_d_b = 0;
      $show_messages_button = 1;
      $show_csv_button = 1;
      $show_living_science_button = 1;
      $show_conference_button = 1;
    }
    $json_display_config = $this->getJsonDisplayConfig($fields);
    $search_db = '{"users": ' . $json_users_and_fields . ', "config":' . $json_display_config . ', "from": ' . $from . ',  "how_many":' . $how_many . ', "nb_users_per_page": ' . $nb_users_per_page . ', "nbUsersInServerDB": ' . $nb_usersin_server_d_b . ', "total": ' . $max_id . ', "csv": ' . $show_csv_button . ', "messages": ' . $show_messages_button . ', "livingscience": ' . $show_living_science_button . ', "conference": ' . $show_conference_button . ' }';
    return $search_db;
  }
}
