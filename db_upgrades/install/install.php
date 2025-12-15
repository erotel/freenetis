<?php

/**
 * FreenetIS – DB installer
 * Spouštěj pouze jednou!
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

$dbHost = 'localhost';
$dbName = 'freenetis';
$dbUser = 'freenetis';
$dbPass = 'HESLO';
$sqlFile = __DIR__ . '/freenetis.sql';

if (!file_exists($sqlFile)) {
  die('SQL soubor nenalezen');
}

try {
  $pdo = new PDO(
    "mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4",
    $dbUser,
    $dbPass,
    [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::MYSQL_ATTR_MULTI_STATEMENTS => false,
    ]
  );

  echo "✔ Připojeno k databázi<br>";

  $sql = file_get_contents($sqlFile);

  // odstranění komentářů
  $sql = preg_replace('/^--.*$/m', '', $sql);
  $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);

  // rozdělení na dotazy
  $statements = array_filter(
    array_map('trim', explode(';', $sql))
  );

  $pdo->beginTransaction();

  foreach ($statements as $i => $statement) {
    if ($statement === '') {
      continue;
    }
    $pdo->exec($statement);
  }

  $pdo->commit();

  echo "✅ Instalace databáze dokončena<br>";
} catch (PDOException $e) {
  if ($pdo->inTransaction()) {
    $pdo->rollBack();
  }
  echo "<b>❌ Chyba:</b><br>";
  echo nl2br(htmlspecialchars($e->getMessage()));
  exit(1);
}
