<?php

  error_reporting(E_ALL); ini_set('display_errors', '1');

  $DEBUG = false;
//  $DEBUG = true;

  /*************************//*
    Gets count of rows from 
    main table
  /*************************/
  function getCount ($conn) {
    $sql = "SELECT COUNT(id) FROM main";
    $stmt = $conn->prepare($sql);
    $result = $stmt->execute();
    while ($row = $stmt->fetch()) {
      return $row[0];
    }
  }

  /*************************//*
    Converts offset based id 
    to sql based id
  /*************************/
  function convertId ($conn, $id) {
    $sql = "SELECT id
      FROM main
      ORDER BY id ASC 
      LIMIT 1 OFFSET " . $id;
    $stmt = $conn->prepare($sql);
    $result = $stmt->execute();
    while ($row = $stmt->fetch()) {
      return $row[0];
    }
  }

  /*************************//*
    Gets 'likes' data given
    sql based id
  /*************************/
  function getLikes ($conn, $id) {
    $sql = "SELECT likes FROM main WHERE ( id = :id )";
    $stmt = $conn->prepare($sql);
    $stmt->bindValue(':id', $id);
    $result = $stmt->execute();
    $result = $stmt->setFetchMode(PDO::FETCH_ASSOC);

    while ($row = $stmt->fetch()) {
      return json_encode($row);
    }
  }

  /*************************//*
    Add quote to database given
    user data
  /*************************/
  function addquote ($conn, $body) {
    if (trim($body['author']) == '' or trim($body['quote']) == '') {
      echo "Empty strings detected.  Not cool.";
      return;
    }
    $sql = "INSERT INTO main (author, quote)
      VALUES (:author, :quote)
      ";

    $stmt = $conn->prepare($sql);
    $stmt->bindValue(':author', $body['author']);
    $stmt->bindValue(':quote', $body['quote']);
    $result = $stmt->execute();

    if ($result) {
      echo "OK";
    } else {
      echo $stmt->errorCode();
    }
  }

  /*************************//*
    Get quote, offset based id is optional
    If given offset based id, return subsequent row
    quote using sorted sql based id
    If not given offset based id, returns
    random row.
  /*************************/
  function getquote ($conn, $id) {
    if (!is_numeric($id)) {
      $id = -100;
    } else {
      $id = intval($id);
    }
    $id += 1;
    debug("getQuote() id: $id");
    $count = getCount($conn);
    debug("getQuote() count: $count");
    if ($id < 0) {
      $id = rand(1,$count-1);
    } else if ($id > $count - 1) {
      $id = 0;
    }

    $sql = "SELECT id, author, quote, likes
      FROM main
      ORDER BY id ASC 
      LIMIT 1 OFFSET " . $id;

    $stmt = $conn->prepare($sql);
    $result = $stmt->execute();
    $result = $stmt->setFetchMode(PDO::FETCH_ASSOC);
    while ($row = $stmt->fetch()) {
      $row['id'] = $id;
      echo json_encode($row);
    }

  }

  /*************************//*
    Increment or decrement 'likes'
    based on offset id and
    delta (+1 or -1, typically)
  /*************************/
  function incdec ($conn, $id, $op) {
    if (!is_numeric($id)) {
      echo "BAD NUMBER";
      die;
    }

    $id = convertId($conn, $id);

    $sql = "UPDATE main
      SET likes = likes " . $op . "
      WHERE ( id = :id )
      ";

    $stmt = $conn->prepare($sql);
    $stmt->bindValue(':id', $id);
    $result = $stmt->execute();

    if (!$result) {
      echo $stmt->errorCode();
      die;
    }

    echo getLikes($conn, $id);
  }
  
  /*************************//*
    for caveman debugging
  
  /*************************/
  function debug ($str) {
    global $DEBUG;
    if ($DEBUG) {
      echo "$str\n<br><br>\n";
    }
  }

/**************************/
/**************************/
/**************************/

  $entityBody = file_get_contents('php://input');
  $body = NULL;
  if ($entityBody) {
    $body = json_decode($entityBody, TRUE);
  }
  debug("entityBody $entityBody");
  
  // supported URL params
  $supported = [
    "dbg" => NULL,
    "getquote" => NULL,
    "newquote" => NULL,
    "incrementlikes" => NULL,
    "decrementlikes" => NULL,
    ];

  $_GET += $supported; // predefine fields
  debug("supported " . print_r($_GET, true));

  $cmd = NULL;
  $param = NULL;
  // break once non null URL param is found
  foreach ($supported as $s => $val) {
    $val = htmlspecialchars($_GET[$s]);
    if ($val != NULL) {
      $cmd = $s;
      $param = $val;
      break;
    }
  }


  // get db info from super secret credential file
  $dbhost = 'localhost';
  $mycreds = fopen('../.creds.quotes', 'r') or die("unable to retrieve credentials");
  $dbuser = trim(fgets($mycreds));
  $dbpass = trim(fgets($mycreds));
  $dbname = trim(fgets($mycreds));
  fclose($mycreds); // clean up after yourself
  
  // setup PDO SQL thing
  try {
    $conn = new PDO("mysql:host=$dbhost;dbname=$dbname", $dbuser, $dbpass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  } catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
  }

  debug("$cmd => $param");

  // Do stuff based on non null URL param
  $sql = NULL;
  switch ($cmd) {
    case 'getquote':
      getquote($conn, $param);
      break;
    case 'newquote':
      addquote($conn, $body);
      break;
    case 'decrementlikes':
      incdec($conn, $param, ' - 1');
      break;
    case 'incrementlikes':
      incdec($conn, $param, ' + 1');
      break;
    case 'dbg':
      convertId($conn, $param);
      break;
    default:
      debug("no support");
  }
  
  $conn = null;
  /**/
?>
