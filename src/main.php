<?php
session_start();
$page_id = 'page-character';
$_SESSION['current_timestamp'] = 0;
include 'header.php';

$cleanPost = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
isset($cleanPost['choix']) ? $choix = $cleanPost['choix'] : $choix = "";
isset($cleanPost['lead']) ? $lead = $cleanPost['lead'] : $lead = "";
isset($cleanPost['traitre']) ? $traitre = $cleanPost['traitre'] : $traitre = "";

$id = $_SESSION['id'];
if ($choix != "") {
  
  // here we check if a vote is running to prevent submitting after the poll has closed
   $verif = $db->prepare("SELECT choix FROM sondage");
   $verif->execute();
   $row = $verif->fetch();
   $valide = $row[0];
  if ($valide != "") {
    // here we do the vote
    $stmt = $db->prepare("UPDATE hrpg SET vote=:choix,active=:active WHERE id=:id");
    $stmt->execute([
      ':choix' => $choix,
      ':id' => $id,
      ':active' => 1
    ]);
  }
}

if ($lead == 1) {
  $stmt = $db->prepare("UPDATE hrpg SET leader='2' WHERE id=:id");
  $stmt->execute([
    ':id' => $id,
  ]);
}

if ($traitre == 1) {
  $stmt = $db->prepare("UPDATE hrpg SET traitre='2' WHERE id=:id");
  $stmt->execute([
    ':id' => $id,
  ]);
}

?>
<div id="loader" class="active"></div>
<div id="character-wrapper"><?php print _("Nous récupérons votre personnage."); ?></div>
<script src="js/jquery-3.5.1.min.js"></script>
<script src="js/ajax_pj.js"></script>
<?php
include "footer.php";
?>
