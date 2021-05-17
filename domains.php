<?php
/* DOMAINS */
// Import classes from the Psr library (standardised HTTP requests and responses)
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

$app->put('/domains', function (Request $req, Response $res) use($conn) {
    $post = $req->getParsedBody();
    $stmt = $conn->prepare("INSERT into domains (location, url, project_id, renewal, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $post["location"], $post["url"], $post["project_id"], $post["renewal"], date("Y-m-d H:i:s"), date("Y-m-d H:i:s"));
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare("SELECT * FROM domains");
    $stmt->execute();
    $result = $stmt->get_result();
    domains = array();
    while($row = $result->fetch_assoc()) {
        if($row) {
            $projects[] = $row;
        }
        else {
            return $res->withJson(null);
        }
    };
    $stmt->close();

    return $res->withJson(domains);
});

$app->post('/domains', function (Request $req, Response $res) use($conn) {
    $post = $req->getParsedBody();
    $stmt = $conn->prepare("UPDATE domains SET location = ?, url = ?, renewal = ?, updated_at = ? WHERE id = ?");
    $stmt->bind_param("sssssssi", $post["location"], $post["url"], $post["renewal"], date("Y-m-d H:i:s"), $post["id"]);
    $stmt->execute();
    $stmt->close();
  
    $stmt = $conn->prepare("SELECT * FROM domains WHERE id = ?");
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
