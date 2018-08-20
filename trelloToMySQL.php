<?php
date_default_timezone_set('Asia/Ho_Chi_Minh');

error_reporting(E_ALL);
ini_set('display_errors', 1);

require __DIR__ . '/vendor/autoload.php';
date_default_timezone_set('America/Vancouver');

use \Trello\Trello;

require_once(__DIR__.'/config.php');
$CARDS = [];

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


$cardsToKeep = '';

$trello = new Trello($key, null, $token);

$boardsOptions = array();
$boardsOptions['boards']='all';

$cardOptions = array();
$cardOptions['filter'] = 'all';
$cardOptions['fields'] = 'id,name,shortUrl,due,dateLastActivity,closed,desc,idBoard,idList,labels,pos,timeCreated';
$cardOptions['limit'] = $cardsLimit;


$labelOptions = array();
$labelOptions['limit']=1000;
$labelOptions['fields']='color,idBoard,name,uses';

$listOptions = array();
$listOptions['filter']=  'all';
$listOptions['fields'] = 'closed,idBoard,name,pos';

$orgOptions = array();
$orgOptions['filter']=  'all';
$orgOptions['fields'] = 'id,closed,idOrganization,name';


$me = $trello->members->get('me',$boardsOptions);

$partialCardsToKeep = '';

$boards = $trello->get('organizations/' . $orgId . '/boards', $orgOptions);


// foreach ($me->idBoards as $boardId) {
foreach ($boards as $b) {
      $boardId = $b->id;
      echo($boardId . "\n");

      $board = $trello->boards->get($boardId);
      saveBoard($board, $conn);
      $labels = $trello->get('boards/'.$boardId.'/labels',$labelOptions);
      saveLabels($labels, $conn);
      $lists = $trello->get('boards/'.$boardId.'/lists',$listOptions);
      saveLists($lists, $conn);

      $partialCardsToKeep = saveAllCards($trello, $conn, $board, $cardOptions);
      if ($partialCardsToKeep != '') {
          $cardsToKeep .= empty($cardsToKeep)? $partialCardsToKeep : ','.$partialCardsToKeep;
      }
}

// A simple check, if $cardsToKeep is empty, something probably went wrong
// if ($cardsToKeep != '')  {
//     // Removing all cards no longer in Trello
//     $strSQL = "DELETE FROM card WHERE id NOT IN ($cardsToKeep)";
//     $conn->query($strSQL);
// }

$conn->close();

function saveBoard($board, $conn) {
  // prepare and bind
  $id = $board->id;
  $closed = $board->closed;
  $idOrganization = $board->idOrganization;
  $name = $board->name;
  $pinned = $board->pinned;



  $board = addTimeCreated($board);
  // id,closed,idOrganization,name
  $stmt = $conn->prepare("INSERT INTO board (id, closed, idOrganization, name) VALUES (?, ?, ?, ?)");
  $stmt->bind_param("siss", $id, intval($closed), $idOrganization, $name);
  $stmt->execute();
  $stmt->close();
  // echo "BOARD bind_param($id, intval($closed), $idOrganization, $name,$pinned, $timeCreated)";
  echo "saveBoard done $board->id";
}

function saveLabels($labels, $conn) {
  //color,idBoard,name,uses
  $stmt = $conn->prepare("REPLACE INTO label (id, color, idBoard, name, uses, timeCreated) VALUES (?, ?, ?, ?, ?, ?)");
  $stmt->bind_param("ssssss", $id, $color, $idBoard, $name,$uses, $timeCreated);

  foreach ($labels as $object) {
    $object = addTimeCreated($object);

    $id = $object->id;
    $color = $object->color;
    $idBoard = $object->idBoard;
    $name = $object->name;
    // $uses = $object->uses ?: '';
    $uses = 'uses_fake';
    $timeCreated = $object->timeCreated;
    $stmt->execute();
  }
  $stmt->close();
}

function saveLists($lists, $conn) {
  //closed,idBoard,name,pos
  $stmt = $conn->prepare("REPLACE INTO list (id, closed, idBoard, name, pos, timeCreated) VALUES (?, ?, ?, ?, ?, ?)");
  $stmt->bind_param("ssssss", $id, $closed, $idBoard, $name,$pos, $timeCreated);

  foreach ($lists as $object) {
    $object = addTimeCreated($object);

    $id = $object->id;
    $closed = $object->closed;
    $idBoard = $object->idBoard;
    $name = $object->name;
    $pos = $object->pos;
    $timeCreated = $object->timeCreated;
    $stmt->execute();
  }
  $stmt->close();
}

/*
   Boards are limited to a 1000 max per request. We need to grab them
   using pagngination.

   Returns a string with comma delimited card id's to keep (we remove any card
   that was deleted from Trello - not simply archived but completely deleted)

   @return string
*/
function saveAllCards($trello, $conn, $board, $cardOptions) {
try {
      global $CARDS;
      $cardsToKeep = '';
      $cards = $trello->get('boards/'.$board->id.'/cards', $cardOptions);

      $CARDS = array_merge($CARDS, $cards);

      saveFoundCards($trello, $cards, $conn);

      return;

      // We will continue to pull cards as long as there are more than the limit
      // The first pass has to happen though.
      $continue = true;

      while ($continue) {
        $cardsToKeepFromSave = saveFoundCards($trello, $cards, $conn);

        if ($cardsToKeepFromSave!= '') {
          $cardsToKeep.= empty($cardsToKeep)? $cardsToKeepFromSave:','.$cardsToKeepFromSave;
        }

        // If the number of cards found is less than the limit, then we do can stop.
        if (sizeof($cards) < $cardOptions['limit']) {
            $continue = false;
        } else {
            $lastCardIdInFoundSet = $cards[0]->id;
            $cardOptions['before'] = $lastCardIdInFoundSet;
            $cards = $trello->get('boards/'.$board->id.'/cards', $cardOptions);
        }
      }

      return $cardsToKeep;
}   catch(PDOException $e)
    {
    echo "Error: " . $e->getMessage();
    }

}

function saveFoundCards($trello, $cards, $conn) {
  debug_array($cards, 'saveFoundCards');
  $cardsToKeep = '';

  try {

  // $stmt = $conn->prepare("REPLACE INTO card (id, closed, idBoard, name, pos,shortUrl,due,dateLastActivity,`desc`, idList,labels, timeCreated) VALUES (?, ?, ?, ?, ?, ?,?, ?, ?, ?, ?, ?)");
  $closed = false;
  $dateLastActivity = NULL;
  $deadline = null;

  $stmt = $conn->prepare("REPLACE INTO card (id, closed, idBoard, name, pos, shortUrl, due) VALUES (?, ?, ?, ?, ?, ?, ?)");
  $stmt->bind_param("sssssss",
    $id, intval($closed), $idBoard,
    $name,$pos, $shortUrl,
    $deadline
    // $dateLastActivity
    // $desc, $idList
  );

  foreach ($cards as $object) {
    $object = addTimeCreated($object);
    $id = $object->id;
    $closed = $object->closed ?: false;
    $idBoard = $object->idBoard;
    $name = $object->name;
    $pos = $object->pos;
    $shortUrl = $object->shortUrl;
    // $due = $object->due ?: 0;
    $deadline = 0;
    $dateLastActivity = $object->dateLastActivity ?: null;
    echo "$id | $object->dateLastActivity | $dateLastActivity \n";
    $desc = $object->desc;
    $idList = $object->idList;

    $labels = '';
    foreach ($object->labels as $labelObj) {
      $labels .= empty($labels) ? $labelObj->name : ','.$labelObj->name;
    }

    $timeCreated = $object->timeCreated;


    // echo "SINGLE::saveFoundCards $id, $closed, $idBoard, $name, $pos, $shortUrl, $due, $dateLastActivity, 'desc hidden', $idList, $labels, $timeCreated ";

    $stmt->execute();
    // echo("DUMP");
    // $stmt->debugDumpParams();
    // echo("---");

    // saveDetailedLabels($object->id, $object->labels, $conn);
    $cardsToKeep.=empty($cardsToKeep)? "'{$object->id}'":",'{$object->id}'";
    echo $object->id.','.$object->timeCreated."\n";
  }
  $stmt->close();


    // --- Actions for the above cards
    // echo "line 236";
    // $stmtAction = $conn->prepare("REPLACE INTO cardAction (id, idCard, data, type, `date` ,memberCreator) VALUES (?, ?, ?, ?, ?, ?)");
    //
    // $cardActionOptions = array();
    // $cardActionOptions['filter'] = 'all';

    // foreach ($cards as $object) {
    //     $id = $object->id;
    //
    //     $cardActions = $trello->get('cards/' . $id . '/actions', $cardActionOptions);
    //
    //     if (sizeof($cardActions)) {
    //
    //         foreach ($cardActions as $cardAction) {
    //             $idAction = $cardAction->id;
    //             $data = json_encode($cardAction->data);
    //             $type = $cardAction->type;
    //             $date = $cardAction->date;
    //             $memberCreator = json_encode($cardAction->memberCreator);
    //             $stmtAction->bind_param("ssssss", $idAction, $id, $data, $type, $date, $memberCreator);
    //
    //             $stmtAction->execute();
    //         }
    //
    //     }
    // }


    // $stmtAction->close();

    echo "before return cardsToKeep";
  }catch(PDOException $e)
       {
       echo "Error: " . $e->getMessage();
       }

  return $cardsToKeep;

}

function saveDetailedLabels($idCard, $labels, $conn) {
  return;
  echo "start saveDetailedLabels";
  $sql = "DELETE FROM cardLabel WHERE idCard = '$idCard'";
  $conn->query($sql);

  $stmt = $conn->prepare("INSERT INTO cardLabel (idCard, idLabel ) VALUES (?, ?)");
  $stmt->bind_param("ss", $idCard, $idLabel);

  foreach($labels as $label) {
    $idLabel = $label->id;
    $stmt->execute();
  }

  echo "end saveDetailedLabels";

  $stmt->close();
}

function addTimeCreated($object) {
  $id = $object->id;
  $createdDate = date('c', hexdec( substr( $id  , 0, 8 ) ) );


  $dateObj = new DateTime($createdDate);

  $object->timeCreated = $dateObj->format('Y-m-d H:i:s');

  return $object;
}


function debug_log($obj, $MESSAGE = 'LOG') {
  error_log(date('h:i:s A') . ' | ' . $MESSAGE . ":\n" . json_encode($obj) . "\n");
}

function debug_array($obj, $MESSAGE = 'LOG') {
  $N = count($obj);
  error_log(date('h:i:s A') . ' | ' . $MESSAGE . ": $N \n" . json_encode($obj[0]) . "\n");
}
