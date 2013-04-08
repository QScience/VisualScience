<?php
class Search {

	private function ensureSearchSafety ($search) {
		//TODO: Implement it !
		return $search;
	}

	private function getFieldsFromConfig () {
		$query = db_select('visualscience_search_config', 'f')
		->fields('f', array('name','mini','full','first','last'));
		$result = $query->execute();
		$final = array();
		for ($i=0; $record = $result->fetchAssoc(); $i++) {
			$final[$record['name']] = $record;
		}
		return $final;	
	}

	private function getUsersFields ($fields) {
		$usersIds = $this->getAllUsersIds();
		$users = user_load_multiple($usersIds);
		$userFields = array();
		foreach ($users as $user) {
			foreach ($fields as $field) {
				//Change and create a 2-dim. array.(each user has each field in fields with mini, full, etc...)
				echo $user->$field['name'];
			}
		}
		return $userFields;
	}

	private function getAllUsersIds () {
		$query = db_select('users', 'f')
		->fields('f', array('uid'));
		$result = $query->execute();
		$final = array();
		for ($i=0; $record = $result->fetchAssoc(); $i++) {
			array_push($final, $record['uid']);
		}
		return $final;
	}

	public function getSavedSearch () {
		//TODO: Implement it
		if (isset($_GET['search'])) {
			return $_GET['search'];
		}
		return '';
	}

	public function getHtmlSearchBar ($searchValue= "") {
		$safeSearchVal = $this->ensureSearchSafety($searchValue);

		return '<div align="center">
		<input type="search" placeholder="Search..." val="'.$safeSearchVal.'" class="visualscience-search-main" id="visualscience-search-bar" onKeyUp="vsUserlist.search();" />
		<div style="width:48%;" align="left">
		<p class="visualscience-right" align="right">'.l(t("Help"), "admin/help/visualscience").'</p>
		<p class="visualscience-left" align="left"><a onClick="vsUserlist.saveSearch();">Save/Load</a></p>
		</div>
		</div>';
	}

	public function getHtmlSearchTable () {
		return '<p>Here there will be the table witht the users.</p>';
	}

	public function getJsonDatabase () {
		$searchDB = '';
		$fields = $this->getFieldsFromConfig();
		$usersAndFields = $this->getUsersFields($fields);

		return '<script type="text/javascript" charset="utf-8">var searchDB = '. $searchDB .';</script>';
	}

	public function getClientSideFiles () {
		global $user;
		drupal_add_library('system', 'ui.autocomplete');
		drupal_add_library('system', 'ui.datepicker');
		drupal_add_library('system', 'ui.dialog');
		drupal_add_library('system', 'ui.tabs');

		drupal_add_js('http://livingscience.ethz.ch/livingscience/livingscience/livingscience.nocache.js', 'external');
		drupal_add_css(drupal_get_path('module', 'visualscience') .'/css/visualscience.css');
		drupal_add_js(drupal_get_path('module', 'visualscience') .'/javascript/lib/visualscience.jquery.layout.js');
		drupal_add_css(drupal_get_path('module', 'visualscience') .'/css/visualscience.jquery.layout.css');
		drupal_add_js(drupal_get_path('module', 'visualscience') .'/javascript/lib/visualscience.jquery.tablesorter.js');
		drupal_add_css(drupal_get_path('module', 'visualscience') .'/css/visualscience.jquery.tablesorter.css');
		drupal_add_js(drupal_get_path('module', 'visualscience') .'/javascript/lib/visualscience.handlebars.js');
		drupal_add_js(drupal_get_path('module', 'visualscience') .'/javascript/lib/visualscience.nddb.js');
  		//Settings necessary to VisualScience:
		drupal_add_js(array('installFolder' => url(drupal_get_path('module', 'visualscience')).'/'), 'setting');
		if (isset($user->name)) {
			drupal_add_js(array('username' => $user->name), 'setting');
		}
		drupal_add_js(drupal_get_path('module', 'visualscience') .'/javascript/visualscience.utils.js');
		drupal_add_js(drupal_get_path('module', 'visualscience') .'/javascript/visualscience.database.js');
		drupal_add_js(drupal_get_path('module', 'visualscience') .'/javascript/visualscience.interface.js');
		drupal_add_js(drupal_get_path('module', 'visualscience') .'/javascript/visualscience.search.js');
		drupal_add_js(drupal_get_path('module', 'visualscience') .'/javascript/visualscience.message.js');
		drupal_add_js(drupal_get_path('module', 'visualscience') .'/javascript/visualscience.csv.js');
		drupal_add_js(drupal_get_path('module', 'visualscience') .'/javascript/visualscience.userlist.js');
		drupal_add_js(drupal_get_path('module', 'visualscience') .'/javascript/visualscience.lscomparison.js');
		drupal_add_js(drupal_get_path('module', 'visualscience') .'/javascript/visualscience.search.js');
		drupal_add_js(drupal_get_path('module', 'visualscience') .'/javascript/visualscience.conference.js');
		drupal_add_js(drupal_get_path('module', 'visualscience') .'/javascript/visualscience.livingscience.js');
	}
}