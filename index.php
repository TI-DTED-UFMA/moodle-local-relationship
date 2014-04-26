<?php

require('../../config.php');
require($CFG->dirroot.'/local/relationship/locallib.php');

require_login();
$contextid = required_param('contextid', PARAM_INT);
$context = context::instance_by_id($contextid, MUST_EXIST);
require_capability('local/relationship:view', $context);

$page = optional_param('page', 0, PARAM_INT);
$searchquery = optional_param('searchquery', '', PARAM_RAW);
$params = array('page'=>$page, 'contextid'=>$contextid);
if ($searchquery) {
    $params['searchquery'] = $searchquery;
}
$baseurl = new moodle_url('/local/relationship/index.php', $params);

relationship_set_header($context, $baseurl);
relationship_set_title();

$manager = has_capability('local/relationship:manage', $context);

$relationships = relationship_search_relationships($contextid, $page, 25, $searchquery);
$count = '';
if ($relationships['allrelationships'] > 0) {
    if ($searchquery === '') {
        $count = ' ('.$relationships['allrelationships'].')';
    } else {
        $count = ' ('.$relationships['totalrelationships'].'/'.$relationships['allrelationships'].')';
    }
}

// Add search form.
$search  = html_writer::start_tag('form', array('id'=>'searchrelationshipquery', 'method'=>'get'));
$search .= html_writer::start_tag('div');
$search .= html_writer::label(get_string('searchrelationship', 'local_relationship'), 'relationship_search_q');
$search .= html_writer::empty_tag('input', array('id'=>'relationship_search_q', 'type'=>'text', 'name'=>'searchquery', 'value'=>$searchquery));
$search .= html_writer::empty_tag('input', array('type'=>'submit', 'value'=>get_string('search', 'local_relationship')));
$search .= html_writer::empty_tag('input', array('type'=>'hidden', 'name'=>'contextid', 'value'=>$contextid));
$search .= html_writer::end_tag('div');
$search .= html_writer::end_tag('form');
echo $search;

echo $OUTPUT->paging_bar($relationships['totalrelationships'], $page, 25, $baseurl);

$data = array();
foreach($relationships['relationships'] as $relationship) {
    $line = array();

    $line[] = format_string($relationship->name);

    $sql = "SELECT count(DISTINCT rm.userid)
              FROM relationship_groups rg
              JOIN relationship_members rm ON (rm.relationshipgroupid = rg.id)
             WHERE rg.relationshipid = :relationshipid";
    $line[] = $DB->count_records_sql($sql, array('relationshipid'=>$relationship->id));
    $line[] = implode(', ', $relationship->tags);

    $line[] = empty($relationship->component) ? get_string('nocomponent', 'local_relationship') : get_string('pluginname', $relationship->component);

    $buttons = array();
    if (empty($relationship->component)) {
        if ($manager) {
            if(!$DB->record_exists('enrol', array('enrol'=>'relationship', 'customint1'=>$relationship->id))) {
                $buttons[] = html_writer::link(new moodle_url('/local/relationship/edit.php', array('relationshipid'=>$relationship->id, 'delete'=>1)),
                    html_writer::empty_tag('img', array('src'=>$OUTPUT->pix_url('t/delete'), 'alt'=>get_string('delete'), 'title'=>get_string('delete'), 'class'=>'iconsmall')));
            }
            $buttons[] = html_writer::link(new moodle_url('/local/relationship/edit.php', array('relationshipid'=>$relationship->id)),
                html_writer::empty_tag('img', array('src'=>$OUTPUT->pix_url('t/edit'), 'alt'=>get_string('edit'), 'title'=>get_string('edit'), 'class'=>'iconsmall')));
        }
    }
    $buttons[] = html_writer::link(new moodle_url('/local/relationship/cohorts.php', array('relationshipid'=>$relationship->id)),
        html_writer::empty_tag('img', array('src'=>$OUTPUT->pix_url('t/cohort'), 'alt'=>get_string('cohorts', 'local_relationship'), 'title'=>get_string('cohorts', 'local_relationship'), 'class'=>'iconsmall')));
    $buttons[] = html_writer::link(new moodle_url('/local/relationship/groups.php', array('relationshipid'=>$relationship->id)),
        html_writer::empty_tag('img', array('src'=>$OUTPUT->pix_url('t/groups'), 'alt'=>get_string('groups'), 'title'=>get_string('groups'), 'class'=>'iconsmall')));
    $line[] = implode(' ', $buttons);

    $data[] = $line;
}

echo $OUTPUT->box_start('generalbox boxaligncenter boxwidthwide');
echo $OUTPUT->heading(get_string('relationships', 'local_relationship', $count), 3, 'main');

$table = new html_table();
$table->head  = array(get_string('name', 'local_relationship'),
                      get_string('memberscount', 'local_relationship'),
                      get_string('tags', 'tag'),
                      get_string('component', 'local_relationship'),
                      get_string('edit'));
$table->colclasses = array('leftalign name', 'leftalign description', 'leftalign size', 'centeralign source', 'centeralign action');
$table->id = 'relationships';
$table->attributes['class'] = 'admintable generaltable';
$table->data  = $data;
echo html_writer::table($table);

if ($manager) {
    echo $OUTPUT->single_button(new moodle_url('/local/relationship/edit.php', array('contextid'=>$context->id)), get_string('add'));
}
echo $OUTPUT->box_end();

echo $OUTPUT->paging_bar($relationships['totalrelationships'], $page, 25, $baseurl);

echo $OUTPUT->footer();
