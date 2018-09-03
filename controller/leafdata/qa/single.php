<?php
/**

*/

$rce = \RCE::factory($_SESSION['rce']);

$obj = $rce->qa()->one($ARG['guid']);
if (empty($obj)) {
	return $RES->withJSON(array(
		'status' => 'failure',
		'detail' => 'QA Result not found',
	), 404);
}

$obj = RBE_LeafData::de_fuck($obj);

return $RES->withJSON(array(
	'status' => 'success',
	'result' => $obj,
));
