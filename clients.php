<?php
/* CLIENTS */
// Import classes from the Psr library (standardised HTTP requests and responses)
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

$app->put('/clients', function (Request $req, Response $res) use($conn) {
    $post = $req->getParsedBody();
    $stmt = $conn->prepare("INSERT into clients (business_name, business_shortname, address, source) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $post["business_name"], $post["business_shortname"], $post["address"], $post["source"]);
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
    $stmt = $conn->prepare("UPDATE clients SET business_name = ?, business_shortname = ?, address = ?, source = ? WHERE id = ?");
    $stmt->bind_param("ssssi", $post["business_name"], $post["business_shortname"], $post["address"], $post["source"], $post["id"]);
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
