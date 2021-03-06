<?php
//
// Description
// -----------
// This function will return the calendar options for the this module.
//
// Arguments
// ---------
// ciniki:
// settings:        The web settings structure.
// tnid:     The ID of the tenant to get courses for.
//
// args:            The possible arguments for posts
//
//
// Returns
// -------
//
function ciniki_courses_hooks_calendarsWebOptions(&$ciniki, $tnid, $args) {

    //
    // Check to make sure the module is enabled
    //
    if( !isset($ciniki['tenant']['modules']['ciniki.courses']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.117', 'msg'=>"I'm sorry, the page you requested does not exist."));
    }

    $settings = $args['settings'];

    $options = array();
    $options[] = array(
        'label'=>'Courses Title Prefix',
        'setting'=>'ciniki-courses-class-prefix',
        'type'=>'text',
        'value'=>(isset($settings['ciniki-courses-class-prefix'])?$settings['ciniki-courses-class-prefix']:''),
        );
    $options[] = array(
        'label'=>'Courses Legend Name',
        'setting'=>'ciniki-courses-legend-title',
        'type'=>'text',
        'value'=>(isset($settings['ciniki-courses-legend-title'])?$settings['ciniki-courses-legend-title']:''),
        );
    $options[] = array(
        'label'=>'Course Display Times',
        'setting'=>'ciniki-courses-display-times',
        'type'=>'toggle',
        'value'=>(isset($settings['ciniki-courses-display-times'])?$settings['ciniki-courses-display-times']:'no'),
        'toggles'=>array(
            array('value'=>'none', 'label'=>'None'),
            array('value'=>'start', 'label'=>'Start'),
            array('value'=>'startend', 'label'=>'Start - End'),
            ),
        );
    $options[] = array(
        'label'=>'Courses Background Colour',
        'setting'=>'ciniki-courses-colour-background', 
        'type'=>'colour',
        'value'=>(isset($settings['ciniki-courses-colour-background'])?$settings['ciniki-courses-colour-background']:'no'),
        );
    $options[] = array(
        'label'=>'Courses Border Colour',
        'setting'=>'ciniki-courses-colour-border', 
        'type'=>'colour',
        'value'=>(isset($settings['ciniki-courses-colour-border'])?$settings['ciniki-courses-colour-border']:'no'),
        );
    $options[] = array(
        'label'=>'Courses Font Colour',
        'setting'=>'ciniki-courses-colour-font', 
        'type'=>'colour',
        'value'=>(isset($settings['ciniki-courses-colour-font'])?$settings['ciniki-courses-colour-font']:'no'),
        );

    return array('stat'=>'ok', 'options'=>$options);
}
?>
