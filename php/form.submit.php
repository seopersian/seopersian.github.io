<?php
	
	session_start();
	
	$email_to = 'email@example.com';
	
	if($_POST) {
		
		// include validation helper
		include_once('helper.validation.php');
		
		$val = Validation::forge('my_validation');
		
		$val->add_rule(
			array(
				'name' => array('not_empty'),
				'email' => array('email'),
				'message' => array('not_empty'),
				'captcha' => array('captcha:captcha'),
			)
		);
		
		if($val->run()) {
			unset($_SESSION['captcha']);
			echo json_encode(array('success' => true));
			
			// send email
			$subject = 'Contact form | ' . $_SERVER['SERVER_NAME'];
			$headers = 'From: ' . $_SERVER['SERVER_NAME'];
			$message = '';
			foreach($_POST as $field => $value) {
				if($field !== 'captcha')
					$message .= $field . ': ' . $value . "\r\n";
			}

			mail($email_to, $subject, $message, $headers);
			
		}else{
			$errors = array();
			foreach($val->get_errors() as $field => $error) {
				$errors[] = $field;
			}
			echo json_encode($errors);
		}
    }
	
?>