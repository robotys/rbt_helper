<?php

// Check all the list below to ensure
// no bug come out from using this helper:
// - helpers: form, url, session
// - library: database

function dumper($multi){
	echo '<pre>';
	var_dump($multi);
	echo '</pre>';
}

function to_shout($message, $type = 'danger'){
	$CI=&get_instance();
	if($CI->session->userdata('to_shout')) $to_shout = $CI->session->userdata('to_shout');
	else $to_shout = array();

	$to_shout[$message] = $type;

	$CI->session->set_userdata('to_shout', $to_shout);
}

function shout(){
	$CI=&get_instance();
	if($CI->session->userdata('to_shout')){
		$to_shout = $CI->session->userdata('to_shout');

		foreach($to_shout as $message=>$type){
			echo '<div class="alert alert-'.$type.'">'.$message.'</div>';
		}

		$CI->session->set_userdata('to_shout', array());
	}
}

function form_make($inputs){
	$CI =& get_instance();

	echo form_open_multipart();
	echo validation_errors('<div class="alert alert-danger">','</div>');
	foreach($inputs as $name=>$input){
		if($name != 'submit'){

			if($input['type'] == 'upload'){
				if(strpos($input['rules'], 'required') !== FALSE) $req = '<small>(required)</small>';
				else $req = '';

				echo '<p><label>'.$input['label'].' '.$req.'</label><br/>'.form_upload(['name'=>$name, 'value'=>$value, 'class'=>'form-control']).'</p>';

			}elseif($input['type'] == 'hidden'){

				echo form_hidden($name, $input['value']);

			}else{
				$typer = 'form_'.$input['type'];
				if(strpos($input['rules'], 'required') !== FALSE) $req = '<small>(required)</small>';
				else $req = '';

				if(array_key_exists('value', $input) !== FALSE AND $CI->input->post() == FALSE){
					$value = $input['value'];
				}else{
					$value = set_value($name);
				}

				echo '<p><label>'.$input['label'].' '.$req.'</label><br/>'.$typer(['name'=>$name, 'value'=>$value, 'class'=>'form-control']).'</p>';
			}
		}
	}

	if(array_key_exists('submit', $inputs) !== FALSE){
		echo '<p>'.form_submit(['name'=>'', 'value'=>$inputs['submit']['label'], 'class'=>'btn btn-primary btn-lg btn-block']).'</p>';
	}else{
		echo '<p>'.form_submit(['name'=>'', 'value'=>'Submit', 'class'=>'btn btn-primary btn-lg btn-block']).'</p>';
	}

	echo form_close();
}

function valid_post($inputs){
	$CI =& get_instance();
	//set rules
	$CI->load->library('form_validation');
	// set rules
	
	if($CI->input->post()){

		$do_upload = [];

		foreach($inputs as $name=>$input){
			if($input['type'] == 'upload'){
				$CI->load->library('upload');
				break;
			}
		}


		foreach($inputs as $name=>$input){
			if($name !== 'submit' AND $input['type']!='upload' AND $input['type'] !== 'hidden' ) $CI->form_validation->set_rules($name, $input['label'], $input['rules']);



			if($input['type'] == 'upload'){

				// R1 U1 GO 
				// R0 U1 GO
				// R1 U0 Please Upload
				// R0 U0 Ignore

				// U OR (R)
				// dumper(strpos($input['rules'],'required') !== FALSE);
				// dumper($_FILES[$name]['size'] > 0);
				$is_required = (strpos($input['rules'],'required') !== FALSE);
				$is_uploaded = ($_FILES[$name]['size'] > 0);
				// dumper($is_required);
				if($is_uploaded){
				
					// kalau ada $_FILE name
					// dumper($_FILES);

					$confs = explode('|', $input['config']);
					foreach($confs as $conf){
						$exp  =explode(':', $conf);
						$config[$exp[0]] = str_replace(',','|', $exp[1]);
					}

					$CI->upload->initialize($config);
					
					if($CI->upload->do_upload($name)){
						// berjaya
						$data = $CI->upload->data();
						$_POST[$name] = $data['file_name'];
						$do_upload[] = true;
					}else{
						// tak
						$_POST[$name] = false;
						$do_upload[] = false;
						to_shout('Upload Error ('.$input['label'].'): '.$CI->upload->display_errors());

					}

				}elseif(!$is_uploaded AND $is_required){
					$_POST[$name] = false;
					$do_upload[] = false;
					to_shout('Please Upload a file on '.$input['label']);
					// dumper('Please Upload');
				}else{
					$_POST[$name] = "";
				}
			}
		}

		$upload_valid = (array_search(false, $do_upload) === FALSE);
		$post_valid = $CI->form_validation->run();

		return ($post_valid AND $upload_valid);
	}
}

function sendgrid($to, $subject, $message){
	$url = 'https://api.sendgrid.com/';
	$user = 'your_sendgrid_login_username';
	$pass = 'your_sendgrid_login_password';

	$params = array(
	    'api_user'  => $user,
	    'api_key'   => $pass,
	    'to'        => $to,
	    'subject'   => $subject,
	    // 'html'      => $message,
	    'text'      => $message,
	    'from'      => 'HiYezza@gmail.com',
	  );


	$request =  $url.'api/mail.send.json';

	// Generate curl request
	$session = curl_init($request);
	// Tell curl to use HTTP POST
	curl_setopt ($session, CURLOPT_POST, true);
	// Tell curl that this is the body of the POST
	curl_setopt ($session, CURLOPT_POSTFIELDS, $params);
	// Tell curl not to return headers, but do return the response
	curl_setopt($session, CURLOPT_HEADER, false);
	curl_setopt($session, CURLOPT_RETURNTRANSFER, true);

	// obtain response
	$response = curl_exec($session);
	curl_close($session);



	// print everything out
	return $response;
}
?>