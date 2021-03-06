<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

include 'src/admin_tools.php';

final class AdminTest extends TestCase {

  public function generateUser($db): void {
    $chars = "0123456789abcdef";
    $rand_string = "";
    for ($i = 0; $i < 32; $i++) {
        $rand_string .= $chars[rand(0, strlen($chars) - 1)];
    }
    $hash = password_hash($rand_string, PASSWORD_DEFAULT);

    $db->query(
      "INSERT INTO `hrpg`"
      . " (`nom`, `mdp`, `carac2`, `carac1`, `hp`, `leader`, `traitre`, `vote`, `log`, `lastlog`, `active`)"
      . " VALUES ('" . $hash . "', '', '1', '1', '1', '0', '0', '0', NULL, NULL, 1)"
    );

  }

  public static function getSQLCount($db, $of) {
    $r = $db->query('SELECT count(*) as total FROM ' . $of);
    $lines = $r->fetchAll();
    $r->closeCursor();
    return $lines[0]['total'];
  }

  public function testMakeElectionLeader(): void {
    $_SESSION = [];
    include 'src/connexion.php';
    $this->generateUser($db);

    $post = [
      'name' => 'leader',
    ];
    make_election($db, $post);

    $this->assertEquals($this->getSQLCount($db, 'hrpg WHERE leader=1'), 1);
  }

  public function testMakeElectionTraitor(): void {
    $_SESSION = [];
    include 'src/connexion.php';
    $this->generateUser($db);

    $post = [
      'name' => 'traitre',
    ];
    make_election($db, $post);

    $this->assertEquals($this->getSQLCount($db, 'hrpg WHERE traitre=1'), 1);
  }

  public function testUpdateAdventurePoll(): void {
    $_SESSION = [];
    include 'src/connexion.php';

    $post = [
      'choix' => 'ghn',
      'c1' => '',
      'c2' => 'zrth',
      'c3' => '',
      'c4' => 'rth',
      'c5' => '',
      'c6' => '',
      'c7' => '',
      'c8' => '',
      'c9' => '',
      'c10' => '',
      'choixtag' => '',
    ];
    poll_update($db, $post);

    $r = $db->query('SELECT * FROM sondage LIMIT 1');
    $lines = $r->fetchAll();
    $r->closeCursor();

    $this->assertNotEquals($lines[0], $post);
  }

  public function testAddAdventureLoots(): void {
    $_SESSION = [];
    include 'src/connexion.php';
    $this->generateUser($db);

    $previous_add_count_loots = $this->getSQLCount($db, 'loot');
    $previous_add_count_logs = $this->getSQLCount($db, 'hrpg WHERE log <> ""');

    $post = [
      'loot' => 'srtjhrth',
      'propriete' => 'hp',
      'bonus' => 2,
      'qui' => 'all',
      'qui_multiple' => '',
    ];
    update_loot($db, $post);

    $after_add_count_loots = $this->getSQLCount($db, 'loot');
    $after_add_count_logs = $this->getSQLCount($db, 'hrpg WHERE log <> ""');

    $this->assertNotEquals($previous_add_count_logs, $after_add_count_logs);
    $this->assertNotEquals($previous_add_count_loots, $after_add_count_loots);
  }

  public function testAddAdventureEvents(): void {
    $_SESSION = [];
    include 'src/connexion.php';

    $post = [
      'type' => 'carac1',
      'difficulte' => 0,
      'penalite_type' => 'hp',
      'penalite' => '',
      'victime' => 'all',
      'victime_multiple' => '',
      'victimetag' => '',
    ];
    ob_start();
    $sanction = update_events($db, $post);
    ob_end_clean();
    $this->assertNotEquals($sanction, NULL);
  }

  public function testAddAdventureTags(): void {
    $_SESSION = [];
    include 'src/connexion.php';
    $this->generateUser($db);

    $previous_add_count = $this->getSQLCount($db, 'hrpg WHERE log <> ""');

    $post = [
      'tag1' => '[{"value":"tagA1"},{"value":"tagA2"}]',
      'tag3' => '[{"value":"tagB1"},{"value":"tagB2"},{"value":"tagB3"}]',
    ];
    add_new_tags($db, $post);

    $after_add_count = $this->getSQLCount($db, 'hrpg WHERE log <> ""');

    $this->assertNotEquals($previous_add_count, $after_add_count);
  }

  public function testEditAdventureSettings(): void {
    $_SESSION = [];
    include 'src/connexion.php';
    $post = [
      'adventure_name' => 'azegerg',
      'adventure_guide' => 'azegerg',
      'carac1_name' => 'azegerg',
      'carac1_group' => 'azegerg',
      'carac2_name' => 'azegerg',
      'carac2_group' => 'azegerg',
    ];
    save_new_settings($post, $tmp_path);
    $this->assertTrue(file_exists($tmp_path . '/settings.txt'));
    $this->assertTrue(file_exists($tmp_path . '/settings_timestamp.txt'));
  }

  public function testDeleteAdventureInitPoll(): void {
    $_SESSION = [];
    include 'src/connexion.php';
    delete_adventure($db, $tmp_path);

    $r = $db->query('SELECT * FROM sondage');
    $lines = $r->fetchAll();
    $r->closeCursor();
    $this->assertEquals(count($lines), 1, 'Not exactly one poll found after delete adventure');
    foreach ($lines[0] as $field => $value) {
      $this->assertEquals($value, '', 'Not empty field found in default poll after delete adventure (field=' . $field . ', value=' . $value . ')');
    }
  }

  public function testDeleteAdventureInitUsers(): void {
    $_SESSION = [];
    include 'src/connexion.php';
    delete_adventure($db, $tmp_path);

    $r = $db->query('SELECT * FROM hrpg');
    $lines = $r->fetchAll();
    $r->closeCursor();

    if (count($lines) < 1) {
      return;
    }

    $this->assertEquals(count($lines), 1, 'Not exactly one user found after delete adventure');
    $this->assertEquals($lines[0]['id'], 1, 'User keep is not admin');
  }

  public function testDeleteAdventureInitLoot(): void {
    $_SESSION = [];
    include 'src/connexion.php';
    delete_adventure($db, $tmp_path);

    $this->assertEquals($this->getSQLCount($db, 'loot'), 0, 'Loot table not empty');
  }

  public function testCleanAdventureVotes(): void {
    $_SESSION = [];
    include 'src/connexion.php';
    clean_adventure($db, $tmp_path);

    $this->assertEquals($this->getSQLCount($db, 'hrpg WHERE vote!="0"'), 0, 'Votes not reset');
  }

}