<?php
/**
	Return a List of Strains
*/

use Edoceo\Radix\DB\SQL;

$obj_name = 'strain';

$age = CRE_Sync::age($obj_name);
// If client requested no-cache
if (!empty($_SERVER['HTTP_CACHE_CONTROL'])) {
	if ('no-cache' == $_SERVER['HTTP_CACHE_CONTROL']) {
		$age = CRE_Sync::MAX_AGE + 1;
	}
}


// Load Cache Data
$sql = "SELECT guid, hash FROM {$obj_name}";
$res_cached = SQL::fetch_mix($sql);


// Load Fresh Data?
if ($age >= CRE_Sync::MAX_AGE) {

	$cre = \CRE::factory($_SESSION['cre']);

	$res_source = new CRE_Iterator_LeafData($cre->strain());

	foreach ($res_source as $src) {

		$guid = $src['global_id'];
		$hash = _hash_obj($src);

		if ($hash != $res_cached[ $guid ]) {

			$idx_update++;

			CRE_Sync::save($obj_name, $guid, $hash, $src);

		}
	}

	CRE_Sync::age($obj_name, time());

}


// Now Fetch all from DB and Send Back
$res_output = array();
$sql = "SELECT guid, hash, meta FROM {$obj_name} ORDER BY guid DESC";
$res_source = SQL::fetch_all($sql);

foreach ($res_source as $src) {

	$src['meta'] = json_decode($src['meta'], true);

	$out = array(
		'guid' => $src['guid'],
		'hash' => $src['hash'],
		'name' => $src['meta']['name'],
	);

	if ($out['hash'] != $res_cached[ $out['guid'] ]) {
		$out['_updated'] = 1;
		$out['_source'] = $out['meta'];
	}

	$res_output[] = $out;

}


$ret_code = ($idx_update ? 200 : 203);

$RES = $RES->withHeader('x-openthc-update', $idx_update);

return $RES->withJSON(array(
	'status' => 'success',
	'result' => $res_output,
), $ret_code, JSON_PRETTY_PRINT);
