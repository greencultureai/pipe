<?php
/**
 * List of All Inventory Adjustments
*/

use Edoceo\Radix\DB\SQL;

$obj_name = 'lot_delta';

$out_detail = array();
$out_result = array();

$age = CRE_Sync::age($obj_name);


// Load Cache Data
$sql = "SELECT guid, hash FROM {$obj_name}";
$res_cached = SQL::fetch_mix($sql);


// Load Fresh Data?
if ($age >= CRE_Sync::MAX_AGE) {

	$cre = \CRE::factory($_SESSION['cre']);

	// Load Inventory
	$out_detail[] = 'Loading Inventory History';
	$res_source = $cre->sync_inventory_adjust(array(
		'min' => intval($_GET['min']),
		'max' => intval($_GET['max']),
	));

	if (1 == $res_source['success']) {
		foreach ($res_source['inventory_adjust'] as $src) {

			$guid = sprintf('%s-%s', $src['transactionid_original'], $src['inventoryid']);
			$hash = _hash_obj($src);

			if ($hash != $res_cached[ $guid ]) {

				$idx_update++;

				CRE_Sync::save($obj_name, $guid, $hash, $src);

			}
		}
	} else {
		$out_detail[] = $res_source['error'];
	}

	CRE_Sync::age($obj_name, time());
}


// Now Fetch all from DB and Send Back
$sql = "SELECT guid, hash, meta FROM {$obj_name} ORDER BY guid DESC";
$res_source = SQL::fetch_all($sql);

foreach ($res_source as $src) {

	$add_source = false;

	$out = array(
		'guid' => $src['guid'],
		'hash' => $src['hash'],
	);

	if ($out['hash'] != $res_cached[ $out['guid'] ]) {

		$add_source = true;
		$out['_hash0'] = $res_cached[ $out['guid'] ];
		$out['_hash1'] = $out['hash'];
		$out['_updated'] = 1;

	}

	if (!empty($_GET['f-source'])) {
		$add_source = true;
	}

	if ($add_source) {
		$out['_source'] = json_decode($src['meta'], true);
	}

	$out_result[] = $out;

}

// $RES = $RES->withHeader('x-openthc-update', $idx_update);

$ret_code = ($idx_update ? 200 : 203);

return $RES->withJSON(array(
	'status' => 'success',
	'detail' => $out_detail,
	'result' => $out_result,
), $ret_code, JSON_PRETTY_PRINT);
