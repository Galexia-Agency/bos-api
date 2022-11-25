<?php
/* PRODUCTS */
// Import classes from the Psr library (standardised HTTP requests and responses)
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

$app->get('/monthly_stats', function (Request $req, Response $res) use($conn) {
  require_once('components/pandle/monthly_stats.php');

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

$app->get('/project_profit_loss', function (Request $req, Response $res) use($conn) {
  require_once('components/pandle/project_profit_loss.php');

  return $res->withStatus(200);
});
