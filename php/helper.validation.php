<?php

/* Validation Class
 * How to use:
 *
 * 1. Forge your validation object
 * $val = Validation::forge('my_validation');
 *
 * 2. Add your fields, conditions, and error messages:
 * $val->add_rule('field_name_1', 'not_empty', array('empty error!'));
 * $val->add_rule('field_name_2', 'min:5|max:15', array('min 5 char!', 'max 15 char!'));
 *
 * or
 *
    
	$val->add_rule(
   		array(
   			'one' => array(
   				'table_news_hasnt_id', 'table error!'
   			),
   			'two' => array(
   				'min:5|max:10|not_empty',
   				array(
   					'min error!',
   					'max error!',
   					'not empty error!'
   				)
   			)
   		)
   	);
   
	//3. Run the validation like this:
    
	if($val->run()) {
   		echo 'validation true';
    }else{
   		$this->errors = $val->get_errors();
    }
 
 *
 * Conditions:
 *
 * 1.required - required only, isset
 * 2.not_empty - check if not empty
 * 3.numeric - any numeric, ex.: 6, 6.5
 * 4.float - float number, ex.: 6.5
 * 5.int - entire number, ex.: 6
 * 6.array - check if array
 * 7.min:(int) - set min value
 * 8.max:(int) - set max value
 * 9.email - if email
 * 10.date - if date
 * 11.cyrillic - if cyrillic
 * 12.not - not contains any value (string) or (int)
 * 13.equal - if contains any value (string) or (int)
 * 14.table_(table_name)_has_(column):$this->request->post('value') - check if value exists in a database, ex.: = (string), (int)
 * 15.table_(table_name)_hasnt_(column) - check if value doesn't exists in a database, ex.: <> (string), (int)
 * 16.length:(int) - set fixed length
 * 17.captcha:($session_variable) - check if captcha matches
 *
 *
 *
 *
 * Ivaylo Zahariev
 * 17.08.2013
 *
 */

class Validation {

	/**
	 * @var  Fieldset  the fieldset this instance validates
	 */
	protected $fieldset;

	/**
	 * @var  array  available after validation started running: contains given input values
	 */
	protected $input = array();

	/**
	 * @var  array  contains values of fields that validated successfully
	 */
	protected $validated = array();

	/**
	 * @var  array  contains Validation_Error instances of encountered errors
	 */
	protected $errors = array();

	/**
	 * @var  array, array  contains Validation_Error instances of encountered errors
	 */
	protected $output_errors = array();

	/**
	 * @var  array  all fields for validation
	 */
	protected $fields = array();

	/**
	 * @var  array  data is going to be validated, ex.: post or custom.
	 */
	protected $data = array();

	/**
	 * @var  int  contains filter's second parementer.
	 */
	protected $range = null;

	/**
	 * @var  array  contains validation error messages, will overwrite those from lang files
	 */
	protected $error_messages = array();

	/**
	 * Gets a new instance of the Validation class.
	 *
	 * @param   string      The name or instance of the Fieldset to link to
	 * @return  Validation
	 */
	public static function forge($fieldset) {
		return new self($fieldset);
	}

	protected function __construct($fieldset) {
		$this->fieldset = $fieldset;
	}

	public function add_rule($rules, $condition = null, array $messages = array()) {
		if (is_array($rules)) {
			foreach ($rules as $key => $rule) {
				if (is_array($rule)) {
					$this->dismember($key, current($rule), next($rule));
				} else {
					$this->dismember($key, $rule, $messages);
				}
			}
		} else {
			$this->dismember($rules, $condition, $messages);
		}
	}

	protected function dismember($rule, $condition, $messages) {
		if (strpos($condition, '|') !== false) {
			$parser = explode('|', $condition);
			foreach ($parser as $k => $pr) {
				$this->add_field($rule, $pr, $messages[$k]);
			}
		} else {
			$add_message = is_array($messages) ? current($messages) : $messages;
			$this->add_field($rule, $condition, $add_message);
		}
	}

	protected function add_field($field, $condition, $message) {
		$this->fields[$field][] = $condition;
		$this->error_messages[$field][$condition] = $message;
	}

	public function run($data = null) {
		if ($data == null) {
			if ($data = $_POST) {
				$this->data = $data;
			} else {
				return false;
			}
		} else {
			$this->data = $data;
		}
		return $this->action($data);
	}

	protected function parse($filter) {
		if (strpos($filter, ':') !== false) {
			$parser = explode(':', $filter);
			$this->range = $parser[1];
			return $parser[0];
		} else {
			$this->range = null;
			return $filter;
		}
	}

	protected function action($data) {

		foreach ($this->fields as $field_name => $patterns) {

			foreach ($patterns as $pattern) {

				if (array_key_exists($field_name, $this->fields)) {

					$filter = $this->parse($pattern);

					$value = $this->data[$field_name];

					$function = '_validation_' . $filter;

					switch (true) {
						case preg_match("/(^table_)(.*)(_has_)(.*)$/", $filter, $matches):
							$params = '$value, $matches[2], $matches[4]';
							$function = '_validation_table_has';
							break;
						case preg_match("/(^table_)(.*)(_hasnt_)(.*)$/", $filter, $matches):
							$params = '$value, $matches[2], $matches[4]';
							$function = '_validation_table_hasnt';
							break;
						default:
							$params = '$value';
					}

					if (method_exists($this, $function)) {
						eval('$result = $this->' . $function . '(' . $params . ');');
					}

					if ($result) {
						$this->add_validation($field_name, $filter);
					} else {
						$this->add_error($field_name, $filter);
					}
				}
			}
		}
		return $this->pass();
	}

	protected function pass() {
		return empty($this->errors);
	}

	protected function add_validation($field_name, $filter) {
		$this->validated[$field_name][] = $filter;
	}

	protected function add_error($field_name, $filter) {
		$filter = !empty($this->range) ? $filter . ':' . $this->range : $filter;
		$this->errors[$field_name] = !empty($this->error_messages[$field_name][$filter]) ? $this->error_messages[$field_name][$filter] : $filter;
		$this->output_errors[$field_name][] = $filter;
	}

	public function get_output_errors() {
		return $this->output_errors;
	}

	public function get_errors() {
		$errors = $this->errors;
		$output_errors = array();
		foreach($errors as $field => $error) {
			$output_errors[$field] = $error;
		}
		return $output_errors;
	}

	public function get_validated() {
		return $this->validated;
	}

	public function get_messages() {
		return $this->error_messages;
	}

	/**
	 * Special empty method because 0 and '0' are non-empty values
	 *
	 * @param   mixed
	 * @return  bool
	 */
	public static function _empty($val) {
		return ($val === false or $val === null or $val === '' or $val === array());
	}

	/**
	 * @function  boolean  required only, isset
	 */
	public function _validation_required($val) {
		return isset($val);
	}

	/**
	 * @function  boolean  check if not empty
	 */
	public function _validation_not_empty($val) {
		return !$this->_empty($val);
	}

	/**
	 * @function  boolean  any numeric, ex.: 6, 6.5
	 */
	public function _validation_numeric($val) {
		return is_numeric($val);
	}

	/**
	 * @function  boolean  float number, ex.: 6.5
	 */
	public function _validation_float($val) {
		return is_numeric($val) && floor($val) != $val;
	}

	/**
	 * @function  boolean  entire number, ex.: 6
	 */
	public function _validation_int($val) {
		return is_numeric($val) && floor($val) == $val;
	}

	/**
	 * @function  boolean  check if array
	 */
	public function _validation_array($val) {
		return is_array($val);
	}

	/**
	 * @function  boolean  set min value
	 */
	public function _validation_min($val) {
		return strlen($val) >= $this->range;
	}

	/**
	 * @function  boolean  set max value
	 */
	public function _validation_max($val) {
		return strlen($val) <= $this->range;
	}

	/**
	 * @function  boolean  set a fixed length of a value
	 */
	public function _validation_length($val) {
		return strlen($val) == $this->range;
	}

	/**
	 * @function  boolean  check if capcha matches
	 */
	public function _validation_captcha($val) {
		if(!isset($_SESSION[$this->range])) return false;
		return md5($val) == $_SESSION[$this->range];
	}

	/**
	 * @function  boolean  if email
	 */
	public function _validation_email($val) {
		return filter_var($val, FILTER_VALIDATE_EMAIL);
	}

	/**
	 * @function  boolean  if date
	 */
	public function _validation_date($val) {
		return strtotime($val);
	}

	/**
	 * @function  boolean  if cyrillic
	 */
	public function _validation_cyrillic($val) {
		return preg_match('/^[\p{Cyrillic}\p{Common}]+$/u', $val);
	}

	/**
	 * @function  boolean  not contains any value (string) or (int)
	 */
	public function _validation_not($val) {
		return (string)$val != (string)$this->range or (int) $val != (int)$this->range;
	}

	/**
	 * @function  boolean  if contains any value (string) or (int)
	 */
	public function _validation_equal($val) {
		return $val == $this->range or (int) $val == $this->range;
	}

	/**
	 * @function  boolean  check if value exists in a database, ex.: = (string), (int)
	 */
	public function _validation_table_has($val, $table, $column) {
		$query = sprintf("SELECT COUNT(*) as counter FROM %s WHERE %s = '%s'", mysql_escape_string($table), mysql_escape_string($column), mysql_escape_string($val));
		$result = Registry()->db->query($query);
		return $result->counter > 0;
	}

	/**
	 * @function  boolean  check if value doesn't exists in a database, ex.: <> (string), (int)
	 */
	public function _validation_table_hasnt($val, $table, $column) {
		$query = sprintf("SELECT COUNT(*) as counter FROM %s WHERE %s = '%s'", mysql_escape_string($table), mysql_escape_string($column), mysql_escape_string($val));
		$result = Registry()->db->query($query);
		return $result->counter == 0;
	}

	/**
	 * Sanitizes an array data
	 *
	 * @param string $array
	 * @return array
	 */
	public static function sanitize(&$data) {
		array_walk($data, function(&$value, $key) {
			if (is_array($value)) {
				Validation::sanitize($value);
			} else {
				$value = mysql_escape_string(strip_tags(trim($value)));
			}
		});
		return $data;
	}
}

function d($what) {
	print '<pre>';
	print_r($what);
	print '</pre>';
}

?>