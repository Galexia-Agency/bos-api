<?php
/* PRODUCTS */
// Import classes from the Psr library (standardised HTTP requests and responses)
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

function selectPandledashboardByMonth ($conn, $month) {
    $stmt = $conn->prepare("SELECT * FROM pandleDashboard WHERE month = ?");
    $stmt->bind_param("s", $month);
    $stmt->execute();
    $result = $stmt->get_result();

    $response = array();

    while ($row = $result->fetch_assoc()) {
        if ($row) {
            $response[] = $row;
        }
    };
    if (empty($response)) {
        return null;
    }
    return $response;
}

function signIn () {
  $PANDLE_USERNAME = $_ENV['PANDLE_USERNAME'];
  $PANDLE_PASSWORD = $_ENV['PANDLE_PASSWORD'];

  $ch = curl_init("https://my.pandle.com/api/v1/auth/sign_in");
  curl_setopt($ch, CURLOPT_POST, 1);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_POSTFIELDS, "email=$PANDLE_USERNAME&password=$PANDLE_PASSWORD");
  curl_setopt($ch, CURLOPT_HTTPHEADER, array(
      "Accept: application/json",
      "Content-Type: application/x-www-form-urlencoded",
      "Origin: https://api.galexia.agency"
  ));
  // this function is called by curl for each header received
  curl_setopt($ch, CURLOPT_HEADERFUNCTION,
    function($curl, $header) use (&$headers)
    {
      $len = strlen($header);
      $header = explode(':', $header, 2);
      if (count($header) < 2) // ignore invalid headers
        return $len;

      $headers[strtolower(trim($header[0]))][] = trim($header[1]);

      return $len;
    }
  );

  curl_exec($ch);
  return $headers;
}

$app->get('/monthly_stats', function (Request $req, Response $res) use($conn) {
  $PANDLE_COMPANY_ID = $_ENV['PANDLE_COMPANY_ID'];
  $PANDLE_COMPANY_INCORPORATION = $_ENV['PANDLE_COMPANY_INCORPORATION'];

  $signIn = signIn();

  $start = new DateTime($PANDLE_COMPANY_INCORPORATION);
  $interval = new DateInterval('P1M');
  $end = new DateTime();
  $period = new DatePeriod($start, $interval, $end);

  foreach ($period as $dt) {
    $date_start = $dt->format('Y/m/d');
    $date_end = $dt->add(new DateInterval("P1M"))->sub(new DateInterval("P1D"))->format('Y/m/d');

    $existingMonth = selectPandledashboardByMonth($conn, $date_start);

    $currentDate = new DateTime();

    $currentDateMinus4Months = $currentDate->sub(new DateInterval("P4M"));

    if (!$existingMonth || ($_GET['force'] && $_GET['force'] === 'true') || $dt > $currentDateMinus4Months) {
      $ch = curl_init("https://my.pandle.com/api/v1/companies/$PANDLE_COMPANY_ID/reports/profit_and_loss?date_start=$date_start&date_end=$date_end");
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_HTTPHEADER, array(
          "Accept: application/json",
          "Origin: https://api.galexia.agency",
          "access-token: " . $signIn['access-token'][0],
          "client: " . $signIn['client'][0],
          "uid: " . $signIn['uid'][0]
      ));
      $profit_loss = json_decode(curl_exec($ch), true);

      $ch = curl_init("https://my.pandle.com/api/v1/companies/$PANDLE_COMPANY_ID/reports/cash_flow?start=$date_start&end=$date_end");
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_HTTPHEADER, array(
          "Accept: application/json",
          "Origin: https://api.galexia.agency",
          "access-token: " . $signIn['access-token'][0],
          "client: " . $signIn['client'][0],
          "uid: " . $signIn['uid'][0]
      ));
      $cash_flow = json_decode(curl_exec($ch), true);

      $sales = $profit_loss['data']['attributes']['sales-total'];
      $expenses = $profit_loss['data']['attributes']['expenses-total'];
      $profit_loss = $profit_loss['data']['attributes']['net-profit'];
      $cash_flow = $cash_flow['data']['attributes']['net-cash-flow-balances']['total'];

      // Fix incorrect wages adjustments
      if (
        $date_start === '2021/04/02' ||
        $date_start === '2021/05/02' ||
        $date_start === '2021/06/02' ||
        $date_start === '2021/07/02' ||
        $date_start === '2021/08/02' ||
        $date_start === '2021/09/02'
      ) {
        $expenses -= 736.66;
        $profit_loss += 736.66;
      }
      if ($date_start === '2021/09/02') {
        $expenses += 4419.96;
        $profit_loss -= 4419.96;
      }

      if ($existingMonth) {
        $stmt = $conn->prepare("UPDATE pandleDashboard SET sales = ?, expenses = ?, profit_loss = ?, cash_flow = ?, updated_at = ? WHERE month = ?");
        $stmt->bind_param("ssssss", $sales, $expenses, $profit_loss, $cash_flow, date("Y-m-d H:i:s"), $date_start);
        $stmt->execute();
        $stmt->close();
      } else {
        $stmt = $conn->prepare("INSERT into pandleDashboard (month, sales, expenses profit_loss, cash_flow, updated_at) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $date_start, $sales, $expenses, $profit_loss, $cash_flow, date("Y-m-d H:i:s"));
        $stmt->execute();
        $stmt->close();
      }
    }
  }

  $stmt = $conn->prepare("SELECT * FROM pandleDashboard");
  $stmt->execute();
  $result = $stmt->get_result();
  $pandleDashboard = array();
  while($row = $result->fetch_assoc()) {
      if($row) {
          $pandleDashboard[] = $row;
      }
      else {
          return $res->withJson(null);
      }
  };
  $stmt->close();

  return $res->withJson($pandleDashboard);
});
