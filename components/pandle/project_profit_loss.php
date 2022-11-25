<?php
  ini_set('display_errors', 1);
  ini_set('display_startup_errors', 1);
  error_reporting(E_ERROR | E_PARSE);
  date_default_timezone_set('UTC');

  require_once __DIR__ . '/../../vendor/autoload.php';

  $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
  $dotenv->load();
  $dotenv->required([
      'DATABASE_HOST',
      'DATABASE_NAME',
      'DATABASE_USER',
      'DATABASE_PASS',
      'PANDLE_USERNAME',
      'PANDLE_PASSWORD',
      'PANDLE_COMPANY_ID',
      'PANDLE_COMPANY_INCORPORATION'
  ])->notEmpty();

  $db_host = $_ENV['DATABASE_HOST'];
  $db_name = $_ENV['DATABASE_NAME'];
  $db_user = $_ENV['DATABASE_USER'];
  $db_pass = $_ENV['DATABASE_PASS'];

  mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

  // Connect to the database
  $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

  $conn->set_charset("utf8mb4");

  $PANDLE_COMPANY_ID = $_ENV['PANDLE_COMPANY_ID'];

  require_once('sign_in.php');

  $signIn = signIn();

  $stmt = $conn->prepare("SELECT * FROM projects");
  $stmt->execute();
  $result = $stmt->get_result();

  while ($row = $result->fetch_assoc()) {
    if ($row['pandle_id']) {
      $pandle_id = $row['pandle_id'];
      $ch = curl_init("https://my.pandle.com/api/v1/companies/$PANDLE_COMPANY_ID/projects/$pandle_id/income_transactions");
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_HTTPHEADER, array(
          "Accept: application/json",
          "Origin: https://api.galexia.agency",
          "access-token: " . $signIn['access-token'][0],
          "client: " . $signIn['client'][0],
          "uid: " . $signIn['uid'][0]
      ));
      $income_transactions = json_decode(curl_exec($ch), true);

      $income = 0;

      foreach ($income_transactions['data'] as $a) {
        if ($a['attributes']['total-amount']) {
          $income += $a['attributes']['total-amount'];
        }
      }

      $ch = curl_init("https://my.pandle.com/api/v1/companies/$PANDLE_COMPANY_ID/projects/$pandle_id/expense_transactions");
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_HTTPHEADER, array(
          "Accept: application/json",
          "Origin: https://api.galexia.agency",
          "access-token: " . $signIn['access-token'][0],
          "client: " . $signIn['client'][0],
          "uid: " . $signIn['uid'][0]
      ));
      $expense_transactions = json_decode(curl_exec($ch), true);

      $expenses = 0;

      foreach ($expense_transactions['data'] as $a) {
        if ($a['attributes']['total-amount']) {
          $expenses += $a['attributes']['total-amount'];
        }
      }

      $stmt = $conn->prepare("UPDATE projects SET pandle_income = ?, pandle_expenses = ?, updated_at = ? WHERE pandle_id = ?");
      $stmt->bind_param("ssss", $income, $expenses, date("Y-m-d H:i:s"), $pandle_id);
      $stmt->execute();
      $stmt->close();
    }
  }