<?php
require_once(__DIR__.'/../../config.php');
$tmpName = $_FILES['csv']['tmp_name'];
$relationshipid = $_POST['relationshipid'];

$csv_data = array_map('str_getcsv', file($tmpName));
//$csv_data = array_map('trim', $csv_data);
$data = [];
foreach($csv_data as $row){
  $data[] = array_map('trim', $row);
}
var_dump($data);
$csv_data = $data;
array_walk($csv_data , function(&$x) use ($csv_data) {
  $x = array_combine($csv_data[0], $x);
});

/** 
*
* array_shift = remove first value of array 
* in csv file header was the first value
* 
*/

array_shift($csv_data);

// Print Result Data
echo 'relationship id is' .$relationshipid.'<pre/>';
print_r($csv_data);
//verificar se o grupo já foi inserido com o mesmo nome
//verificar se aluno já foi inserido no grupo
foreach($csv_data as $row){
  //se grupo não existir
  $sql = "SELECT * FROM mdl_relationship_groups rg WHERE rg.relationshipid = :relationshipid AND rg.name = :group_name";
  $group = $DB->get_record_sql($sql, ['relationshipid' => $relationshipid, 'group_name' => $row['grupo']]);
  print_r($result);
  //Verifica grupo e cria um novo se necessário
  //$group_id = null;
  echo 'resultado do grupo';
  var_dump($group);
  if($group == FALSE){
    $group = createGroup($relationshipid, $row['grupo']);
  }
  $group_id = $group->id;
  echo 'gruyop id é';
  var_dump($group_id);
  //Verifica se aluno já está no grupo
  //pega o userid do aluno pelo username
  $sql = "SELECT * FROM mdl_user u WHERE u.username = :username";
  $result = $DB->get_record_sql($sql, ['username' => trim($row['username'])]);
  if($result != FALSE){
    $user_id = $result->id;
    echo 'Aluno'.$row['username'].' encontrado com id' .$user_id. '<br/>';
  }
  else{
    echo 'Aluno'.$row['username'].' não encontrado';
    continue;
  }
  // verifica o usuário esta no cohort
  $sql = "SELECT * FROM mdl_relationship_cohorts rc JOIN mdl_cohort_members cm ON rc.cohortid = cm.cohortid WHERE rc.relationshipid = :relationshipid AND cm.userid = :userid";

  $result = $DB->get_record_sql($sql, ['relationshipid' => $relationshipid, 'userid' => $user_id]);

  if($result == FALSE){
    echo 'Aluno'.$row['username'].' não está no cohort';
    continue;
  } else {
    $relationshipcohortid = $result->id;
    //insere o usuário no relationship_members
    $result = insertMember($group_id, $relationshipcohortid, $user_id);
    echo 'Aluno'.$row['username'].' inserido no grupo com id' .$result->id. '<br/>';
  }





  //verifica se o aluno já está no grupo
 
  //print_r($result);


}


function createGroup($relationshipid, $group_name ){
  global $DB;
  $group = new stdClass();
  $group->relationshipid = $relationshipid;
  $group->name = $group_name;
  $group->description = '';
  $group->timecreated = time();
  $group->timemodified = time();
  $group->id = $DB->insert_record('relationship_groups', $group);
  return $group;
}

function insertMember($relationshipgroupid, $relationshipcohortid, $userid){
  global $DB;
  $member = new stdClass();
  $member->relationshipgroupid = $relationshipgroupid;
  $member->relationshipcohortid = $relationshipcohortid;
  $member->userid = $userid;
  $member->timeadded = time();
  $member->id = $DB->insert_record('relationship_members', $member);
  return $member;
}