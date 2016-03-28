<?php
	
	/// V2

	$f3->route('GET /av2',
		function($f3) {
			$r = array(
				'message'=>'Hello again',
				'recipient'=>'Jason Isaacs'
			);
			header("Content-Type: application/json", true);
			echo json_encode($r);
		}
	);

	$f3->route('POST /av2/id',
		function($f3) {

			$post = $f3->get('POST');

			$r = new \Wittr\ID();
			$r->success = true;
			$r->id = findUUID(true,$post);

			header("Content-Type: application/json", true);
			echo json_encode($r);
		}
	);

	$f3->route('POST /av2/demographics',
		function($f3){
			$post = $f3->get('POST');
			$response = new \Wittr\Response();
			$good = true;
			if(!isset($post['uuid'])){
				$good = false;
			}

			if($good){
				$good = uuidExists($post['uuid']);
			}

			if($good){
				$fields = array('pipeSmoker','clergyCorner','ltl','ceramicistsCorner','norwegianBranch','colonialCommoner','cravateer','diafls','aals','pils','hils','ncg','iji','niji','battenberg');
				foreach($fields as $field){
					if(!isset($field)){
						$post[$field] = 0;
					}
				}

				if($good) {
					$sql = "UPDATE
						`wittee`
					SET
						`pipe_smoker` = " . intval($post['pipeSmoker']) . ",
						`ltl` = " . intval($post['ltl']) . ",
						`clergy_corner` = " . intval($post['clergyCorner']) . ",
						`ceramicists_corner` = " . intval($post['ceramicistsCorner']) . ",
						`norwegian_branch` = " . intval($post['norwegianBranch']) . ",
						`colonial_commoner` = " . intval($post['colonialCommoner']) . ",
						`cravateer` = " . intval($post['cravateer']) . ",
						`diafls` = " . intval($post['diafls']) . ",
						`aals` = " . intval($post['aals']) . ",
						`pils` = " . intval($post['pils']) . ",
						`hils` = " . intval($post['hils']) . ",
						`ncg` = " . intval($post['ncg']) . ",
						`iji` = " . intval($post['iji']) . ",
						`niji` = " . intval($post['niji']) . ",
						`battenberg` = " . intval($post['battenberg']) . "
					WHERE
						`uuid` = '" . \Wittr\DB::escape_string($post['uuid']) . "' LIMIT 1";
					\Wittr\DB::query($sql);
					$response->success = true;
				}
			}else{
				$response->error = "Missing UUID";
			}

			header("Content-Type: application/json", true);
			echo json_encode($response);
		}
	);

	$f3->route('POST /av2/wittees',
		function($f3){

			$post = $f3->get('POST');

			$response = new \Wittr\Wittees();

			$good = true;

			if(!isset($post['uuid'])){
				$good = false;
			}

			if($good){
				$good = uuidExists($post['uuid']);
			}

			if($good){

				updateWittee($post);

				if(!isset($post['longitude']) or !isset($post['latitude'])){
					$good = false;
				}
			}


			if($good){
				if($post['longitude']=="" or $post['latitude']==""){
					$good = false;
				}
			}

			if($good){
				$distance = 500;
				$limit = 500;
				$sql = "SELECT * FROM `wittee` WHERE ( 3959 * acos( cos( radians( latitude) ) * cos( radians( '" . doubleval($post['latitude']) . "' ) ) * cos( radians( '" . doubleval($post['longitude']) . "' ) - radians( longitude ) ) + sin( radians( latitude) ) * sin( radians( '" . doubleval($post['latitude']) . "' ) ) ) ) < ".$distance." AND `uuid` != '".\Wittr\DB::escape_string($post['uuid'])."' ORDER BY ( 3959 * acos( cos( radians( latitude) ) * cos( radians( '" . doubleval($post['latitude']) . "' ) ) * cos( radians( '" . doubleval($post['longitude']) . "' ) - radians( longitude ) ) + sin( radians( latitude) ) * sin( radians( '" . doubleval($post['latitude']) . "' ) ) ) ) ASC LIMIT ".$limit;
				$result = \Wittr\DB::query($sql);
				while($row = $result->fetch_array()){
					$r = array(
						'latitude'=>$row['latitude'],
						'longitude'=>$row['longitude'],
						'hash'=>($row['uuid']!='' ? $row['uuid'] : $row['hash']),
						'bb'=>($row['battenberg']==1)
					);
					$response->wittees[] = $r;
				}
				$response->success = true;
			}else{
				$response->error = "Missing Details";
			}


			header("Content-Type: application/json", true);
			echo json_encode($response);

		}
	);

	$f3->route('POST /av2/locate',
		function ($f3) {

			$post = $f3->get('POST');

			$response = new \Wittr\Response();

			$good = true;

			if(!isset($post['uuid'])){
				$good = false;
			}

			if($good){
				$good = uuidExists($post['uuid']);
			}

			if($good){

				updateWittee($post);

				if(!isset($post['longitude']) or !isset($post['latitude'])){
					$good = false;
				}
			}


			if($good){
				if($post['longitude']=="" or $post['latitude']==""){
					$good = false;
				}
			}

			if($good){
				$sql = "UPDATE `wittee` SET `latitude` = '".doubleval($post['latitude'])."', `longitude` = '".doubleval($post['longitude'])."' , `when` = NOW() WHERE `uuid` = '".\Wittr\DB::escape_string($post['uuid'])."' LIMIT 1";
				\Wittr\DB::query($sql);
				$response->success = true;
			}else{
				$response->error = "Missing Details";
			}

			header("Content-Type: application/json", true);
			echo json_encode($response);
		}
	);

	$f3->route('GET /av2/cull',
		function ($f3) {
			$oneMonthAgo = date("Y-m-d H:i:s",strtotime("now -1 month"));
			$sql = "DELETE FROM `wittee` WHERE `when` < '".$oneMonthAgo."'";
			\Wittr\DB::query($sql);
			echo \Wittr\DB::affected_rows()." old records deleted";
		}
	);
