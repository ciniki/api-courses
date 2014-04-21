<?php
//
// Description
// ===========
// This function will search the courses for the ciniki.sapos module.
//
// Arguments
// =========
// 
// Returns
// =======
//
function ciniki_courses_sapos_itemSearch($ciniki, $business_id, $start_needle, $limit) {

	if( $start_needle == '' ) {
		return array('stat'=>'ok', 'items'=>array());
	}

	ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
	$date_format = ciniki_users_dateFormat($ciniki);

	//
	// Prepare the query
	//
	$strsql = "SELECT ciniki_course_offerings.id, "
		. "ciniki_courses.code AS course_code, "
		. "ciniki_courses.name AS course_name, "
		. "ciniki_course_offerings.name AS offering_name, "
//		. "CONCAT_WS(' - ', ciniki_courses.code, ciniki_courses.name, ciniki_course_offerings.name) AS name, "
		. "ciniki_course_offering_prices.id AS price_id, "
		. "ciniki_course_offering_prices.name AS price_name, "
		. "ciniki_course_offering_prices.unit_amount, "
		. "ciniki_course_offering_prices.unit_discount_amount, "
		. "ciniki_course_offering_prices.unit_discount_percentage, "
		. "ciniki_course_offering_prices.taxtype_id, "
		. "UNIX_TIMESTAMP(MIN(ciniki_course_offering_classes.class_date)) AS start_date_ts, "
		. "UNIX_TIMESTAMP(MAX(ciniki_course_offering_classes.class_date)) AS end_date_ts "
		. "FROM ciniki_course_offerings "
		. "LEFT JOIN ciniki_course_offering_prices ON (ciniki_course_offerings.id = ciniki_course_offering_prices.offering_id "
			. "AND ciniki_course_offering_prices.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. ") "
		. "LEFT JOIN ciniki_courses ON (ciniki_course_offerings.course_id = ciniki_courses.id "
			. "AND ciniki_courses.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. ") "
		. "LEFT JOIN ciniki_course_offering_classes ON (ciniki_course_offerings.id = ciniki_course_offering_classes.offering_id "
			. "AND ciniki_course_offering_classes.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. ") "
		. "WHERE ciniki_course_offerings.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "AND (ciniki_course_offerings.reg_flags&0x03) > 0 "
		. "AND (ciniki_courses.name LIKE '" . ciniki_core_dbQuote($ciniki, $start_needle) . "%' "
			. "OR ciniki_courses.name LIKE '% " . ciniki_core_dbQuote($ciniki, $start_needle) . "%' "
			. "OR ciniki_courses.code LIKE '" . ciniki_core_dbQuote($ciniki, $start_needle) . "%' "
			. "OR ciniki_courses.code LIKE '% " . ciniki_core_dbQuote($ciniki, $start_needle) . "%' "
			. ") "
		. "GROUP BY ciniki_course_offerings.id, ciniki_course_offering_prices.id "
		. "HAVING end_date_ts >= UNIX_TIMESTAMP(UTC_TIMESTAMP()) "
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
	$rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.courses', array(
		array('container'=>'courses', 'fname'=>'id',
			'fields'=>array('id', 'course_code', 'course_name', 'offering_name')),
		array('container'=>'prices', 'fname'=>'price_id',
			'fields'=>array('id'=>'price_id', 'name'=>'price_name', 'unit_amount'=>'unit_amount', 
				'unit_discount_amount', 'unit_discount_percentage',
				'taxtype_id')),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( isset($rc['courses']) ) {
		$courses = $rc['courses'];
	} else {
		return array('stat'=>'ok', 'items'=>array());
	}

	$items = array();
	foreach($courses as $cid => $course) {
		if( $course['course_code'] != '' ) {
			$course['course_name'] = $course['course_code'] . ' - ' . $course['course_name'];
		} 
		if( $course['offering_name'] != '' ) {
			$course['course_name'] .= ' - ' . $course['offering_name'];
		}
		if( isset($course['prices']) && count($course['prices']) > 1 ) {
			foreach($course['prices'] as $pid => $price) {
				$details = array(
					'status'=>0,
					'object'=>'ciniki.courses.offering',
					'object_id'=>$course['id'],
					'description'=>$course['course_name'],
					'quantity'=>1,
					'unit_amount'=>$price['unit_amount'],
					'unit_discount_amount'=>$price['unit_discount_amount'],
					'unit_discount_percentage'=>$price['unit_discount_percentage'],
					'taxtype_id'=>$price['taxtype_id'], 
					'notes'=>'',
					);
				if( $price['name'] != '' ) {
					$details['description'] .= ' - ' . $price['name'];
				}
				$items[] = array('item'=>$details);
			}
		} else {
			$details = array(
				'status'=>0,
				'object'=>'ciniki.courses.offering',
				'object_id'=>$course['id'],
				'description'=>$course['name'],
				'quantity'=>1,
				'unit_amount'=>0,
				'unit_discount_amount'=>0,
				'unit_discount_percentage'=>0,
				'taxtype_id'=>0, 
				'notes'=>'',
				);
			if( isset($course['prices']) && count($course['prices']) == 1 ) {
				$price = array_pop($course['prices']);
				if( isset($price['name']) && $price['name'] != '' ) {
					$details['description'] .= ' - ' . $price['name'];
				}
				if( isset($price['unit_amount']) && $price['unit_amount'] != '' ) {
					$details['unit_amount'] = $price['unit_amount'];
				}
				if( isset($price['unit_discount_amount']) && $price['unit_discount_amount'] != '' ) {
					$details['unit_discount_amount'] = $price['unit_discount_amount'];
				}
				if( isset($price['unit_discount_percentage']) && $price['unit_discount_percentage'] != '' ) {
					$details['unit_discount_percentage'] = $price['unit_discount_percentage'];
				}
				if( isset($price['taxtype_id']) && $price['taxtype_id'] != '' ) {
					$details['taxtype_id'] = $price['taxtype_id'];
				}
			}
			$items[] = array('item'=>$details);
		}
	}

	return array('stat'=>'ok', 'items'=>$items);		
}
?>