<?php
/* CLIENTS */
// Import classes from the Psr library (standardised HTTP requests and responses)
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

$app->put('/clients', function (Request $req, Response $res) use($conn) {
    $post = $req->getParsedBody();
    $stmt = $conn->prepare("INSERT into clients (business_name, business_shortname, address, source, created_at, updated_at, pandle_id, billing_email) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssss", $post["business_name"], $post["business_shortname"], $post["address"], $post["source"], $dateTime, $dateTime, $post["pandle_id"], $post["billing_email"],);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare("SELECT * FROM clients");
    $stmt->execute();
    $result = $stmt->get_result();
    $clients = array();
    while($row = $result->fetch_assoc()) {
        if($row) {
            $clients[] = $row;
        }
        else {
            return $res->withJson(null);
        }
    };
    $stmt->close();

    return $res->withJson($clients);
});

$app->post('/clients', function (Request $req, Response $res) use($conn) {
    $post = $req->getParsedBody();
    $stmt = $conn->prepare("UPDATE clients SET business_name = ?, business_shortname = ?, address = ?, source = ?, updated_at = ?, pandle_id = ?, billing_email = ? WHERE id = ?");
    $stmt->bind_param("sssssssi", $post["business_name"], $post["business_shortname"], $post["address"], $post["source"], $dateTime, $post["pandle_id"], $post["billing_email"], $post["id"]);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare("SELECT * FROM clients WHERE id = ?");
    $stmt->bind_param("i", $post["id"]);
    $stmt->execute();
    $result = $stmt->get_result();

    $response = array();

    while($row = $result->fetch_assoc()) {
        if($row) {
            $response[] = $row;
        }
        else {
            return $res->withJson(null);
        }
    };

    return $res->withJson($response);
});

?>
