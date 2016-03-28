<?php

	/// V1

	$f3->route('GET /',
		function($f3) {
			$r = array(
				'message'=>'Hello',
				'recipient'=>'Jason Isaacs'
			);
			header("Content-Type: application/json", true);
			echo json_encode($r);
		}
	);

	$f3->route('POST /demographics',
		function($f3){
			$post = $f3->get('POST');
			$response = new \Wittr\Response();
			$good = true;
			if(!isset($post['userID']) or !isset($post['deviceID'])){
				$good = false;
				$response->error = "Missing details";
			}

			$fields = array('pipeSmoker','clergyCorner','ltl','ceramicistsCorner','norwegianBranch','colonialCommoner','cravateer','diafls','aals','pils','hils','ncg','iji','niji','battenberg');
			foreach($fields as $field){
				if(!isset($field)){
					$post[$field] = 0;
				}
			}

			if($good){
				$sql = "UPDATE
					`wittee`
				SET
					`pipe_smoker` = ".intval($post['pipeSmoker']).",
					`ltl` = ".intval($post['ltl']).",
					`clergy_corner` = ".intval($post['clergyCorner']).",
					`ceramicists_corner` = ".intval($post['ceramicistsCorner']).",
					`norwegian_branch` = ".intval($post['norwegianBranch']).",
					`colonial_commoner` = ".intval($post['colonialCommoner']).",
					`cravateer` = ".intval($post['cravateer']).",
					`diafls` = ".intval($post['diafls']).",
					`aals` = ".intval($post['aals']).",
					`pils` = ".intval($post['pils']).",
					`hils` = ".intval($post['hils']).",
					`ncg` = ".intval($post['ncg']).",
					`iji` = " . intval($post['iji']) . ",
					`niji` = " . intval($post['niji']) . ",
					`battenberg` = " . intval($post['battenberg']) . "
				WHERE
					`user` = '".\Wittr\DB::escape_string($post['userID'])."' AND
					`device` = '".\Wittr\DB::escape_string($post['deviceID'])."' LIMIT 1";
				\Wittr\DB::query($sql);
				$response->success = true;

			}

			header("Content-Type: application/json", true);
			echo json_encode($response);
		}
	);

	$f3->route('POST /wittees',
		function($f3){

			$post = $f3->get('POST');

			$response = new \Wittr\Wittees();

			$good = true;

			if(!isset($post['userID']) or !isset($post['deviceID']) or !isset($post['longitude']) or !isset($post['latitude'])){
				$good = false;
				$response->error = "Missing details";
			}

			if($good){
				if($post['userID']=="" or $post['deviceID']=="" or $post['longitude']=="" or $post['latitude']==""){
					$response->error = "Missing required details";
					$good = false;
				}
			}

			if($good){
				$sql = "SELECT * FROM `wittee` WHERE `user` = '".\Wittr\DB::escape_string($post['userID'])."' AND `device` = '".\Wittr\DB::escape_string($post['deviceID'])."' LIMIT 1";
				$result = \Wittr\DB::query($sql);
				if($result->num_rows == 1){
					$good = true;
				}else{
					$good = false;
					$response->error = "Invalid attempt";
				}
			}

			if($good){
				$distance = 500;
				$limit = 500;
				$sql = "SELECT * FROM `wittee` WHERE ( 3959 * acos( cos( radians( latitude) ) * cos( radians( '" . doubleval($post['latitude']) . "' ) ) * cos( radians( '" . doubleval($post['longitude']) . "' ) - radians( longitude ) ) + sin( radians( latitude) ) * sin( radians( '" . doubleval($post['latitude']) . "' ) ) ) ) < ".$distance." AND `user` != '".\Wittr\DB::escape_string($post['userID'])."' AND `device` != '".\Wittr\DB::escape_string($post['deviceID'])."'  ORDER BY ( 3959 * acos( cos( radians( latitude) ) * cos( radians( '" . doubleval($post['latitude']) . "' ) ) * cos( radians( '" . doubleval($post['longitude']) . "' ) - radians( longitude ) ) + sin( radians( latitude) ) * sin( radians( '" . doubleval($post['latitude']) . "' ) ) ) ) ASC LIMIT ".$limit;
				$result = \Wittr\DB::query($sql);
				while($row = $result->fetch_array()){
					$r = array(
						'latitude'=>$row['latitude'],
						'longitude'=>$row['longitude'],
						'hash'=>$row['hash'],
						'bb'=>false
					);
					$response->wittees[] = $r;
				}
				$response->success = true;
			}


			header("Content-Type: application/json", true);
			echo json_encode($response);

		}
	);

	$f3->route('POST /locate',
		function ($f3) {

			$post = $f3->get('POST');

			$response = new \Wittr\Response();

			$good = true;

			if(!isset($post['userID']) or !isset($post['deviceID']) or !isset($post['longitude']) or !isset($post['latitude'])){
				$response->error = "Missing required details";
				$good = false;
			}

			if($good){
				if($post['userID']=="" or $post['deviceID']=="" or $post['longitude']=="" or $post['latitude']==""){
					$response->error = "Missing required details";
					$good = false;
				}
			}

			if($good){
				$sql = "SELECT * FROM `wittee` WHERE `user` = '".\Wittr\DB::escape_string($post['userID'])."' AND `device` = '".\Wittr\DB::escape_string($post['deviceID'])."' LIMIT 1";
				$result = \Wittr\DB::query($sql);
				if($result->num_rows == 1){
					$sql = "UPDATE `wittee` SET `latitude` = '".doubleval($post['latitude'])."', `longitude` = '".doubleval($post['longitude'])."' , `when` = NOW() WHERE `user` = '".\Wittr\DB::escape_string($post['userID'])."' AND `device` = '".\Wittr\DB::escape_string($post['deviceID'])."' LIMIT 1";
					if(\Wittr\DB::query($sql)) {
						$response->success = true;
					}else{
						$response->success = false;
						$response->error = "Error updating record";
					};

				}else{

					$hash = md5("WIT".$post['deviceID']."2".$post['userID']."TEE");

					$sql = "INSERT INTO `wittee` (`device`,`user`,`latitude`,`longitude`,`when`,`hash`) VALUES ( '".\Wittr\DB::escape_string($post['deviceID'])."', '".\Wittr\DB::escape_string($post['userID'])."', '".doubleval($post['latitude'])."' , '".doubleval($post['longitude'])."', NOW(), '".$hash."')";
					if(\Wittr\DB::query($sql)){
						$response->success = true;
					}else{
						$response->success = false;
						$response->error = "Error adding record";

					}

				}
			}




			header("Content-Type: application/json", true);
			echo json_encode($response);
		}
	);

