<?php
//
// Description
// ===========
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:			The ID of the business to add the course to.
//
// name:				The name of the course.
// webflags:			(optional) How the course is shared with the public and customers.  
//						The default is the course is public.
//
//						0x01 - Hidden, unavailable on the website
//
// short_description:	The short description of the course, for use in lists.
// long_description:	The long description of the course, for use in the details page.
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_courses_offeringAdd(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'course_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Course'), 
        'name'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Name'), 
        'status'=>array('required'=>'no', 'default'=>'10', 'blank'=>'no', 'validlist'=>array('10', '60'), 'name'=>'Status'), 
        'webflags'=>array('required'=>'no', 'default'=>'0', 'blank'=>'no', 'name'=>'Web Flags'), 
		'class_date'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'date', 'name'=>'Class Date'),
		'start_time'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'time', 'name'=>'Start Time'),
		'end_time'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'time', 'name'=>'End Time'),
		'num_weeks'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Num Weeks'),
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];

	$name = $args['name'];
	$args['permalink'] = preg_replace('/ /', '-', preg_replace('/[^a-z0-9 ]/', '', strtolower($name)));

    //  
    // Make sure this module is activated, and
    // check permission to run this function for this business
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'courses', 'private', 'checkAccess');
    $rc = ciniki_courses_checkAccess($ciniki, $args['business_id'], 'ciniki.courses.offeringAdd'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
	$modules = $rc['modules'];

	//
	// Check the permalink doesn't already exist
	//
	$strsql = "SELECT id, name, permalink FROM ciniki_course_offerings "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND course_id = '" . ciniki_core_dbQuote($ciniki, $args['course_id']) . "' "
		. "AND permalink = '" . ciniki_core_dbQuote($ciniki, $args['permalink']) . "' "
		. "";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.courses', 'course');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( $rc['num_rows'] > 0 ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1111', 'msg'=>'You already have a course with this name, please choose another name'));
	}

	//  
	// Turn off autocommit
	//  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
	$rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.courses');
	if( $rc['stat'] != 'ok' ) { 
		return $rc;
	}   

	//
	// Add the offering
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
	$rc = ciniki_core_objectAdd($ciniki, $args['business_id'], 'ciniki.courses.offering', $args, 0x04);
	if( $rc['stat'] != 'ok' ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.courses');
		return $rc;
	}
	$offering_id = $rc['id'];

	//
	// Check if we should add some dates
	//
	if( isset($args['class_date']) && $args['class_date'] != '' ) {
		if( isset($args['num_weeks']) && $args['num_weeks'] != '' && $args['num_weeks'] > 1 ) {
			$repeat = $args['num_weeks'];
		} else {
			$repeat = 1;
		}
		$cur_date = date_create('@' . strtotime($args['class_date']));
		for($i=0;$i<$repeat;$i++) {
			$rc = ciniki_core_objectAdd($ciniki, $args['business_id'], 'ciniki.courses.offering_class', $args, 0x04);
			if( $rc['stat'] != 'ok' ) {
				ciniki_core_dbTransactionRollback($ciniki, 'ciniki.courses');
				return $rc;
			}

			//
			// Calculate next class date
			//
			$cur_date = date_add($cur_date, new DateInterval('P7D'));
		}
		//
		// Update the condensed date
		//
		ciniki_core_loadMethod($ciniki, 'ciniki', 'courses', 'private', 'updateCondensedDate');
		$rc = ciniki_courses_updateCondensedDate($ciniki, $args['business_id'], $offering_id);
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
	}

	//
	// Commit the database changes
	//
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.courses');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	//
	// Update the last_change date in the business modules
	// Ignore the result, as we don't want to stop user updates if this fails.
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'updateModuleChangeDate');
	ciniki_businesses_updateModuleChangeDate($ciniki, $args['business_id'], 'ciniki', 'courses');

	return array('stat'=>'ok', 'id'=>$offering_id);
}
?>
