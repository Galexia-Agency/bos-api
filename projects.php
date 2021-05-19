<?php
/* PROJECTS */
// Import classes from the Psr library (standardised HTTP requests and responses)
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

$app->put('/projects', function (Request $req, Response $res) use($conn) {
    $post = $req->getParsedBody();
    $stmt = $conn->prepare("INSERT into projects (client_id, name, status, hosting, github_url, project_url, project_login_url, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issssssss", $post["client_id"], $post["name"], $post["status"], $post["hosting"], $post["github_url"], $post['project_url'], $post['project_login_url'], date("Y-m-d H:i:s"), date("Y-m-d H:i:s"));
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare("SELECT * FROM projects");
    $stmt->execute();
    $result = $stmt->get_result();
    $projects = array();
    while($row = $result->fetch_assoc()) {
        if($row) {
            $projects[] = $row;
        }
        else {
            return $res->withJson(null);
        }
    };
    $stmt->close();

    return $res->withJson($projects);
});

$app->post('/projects', function (Request $req, Response $res) use($conn) {
    $post = $req->getParsedBody();
    $stmt = $conn->prepare("UPDATE projects SET name = ?, status = ?, hosting = ?, github_url = ?, project_url = ?, project_login_url = ?, updated_at = ? WHERE id = ?");
    $stmt->bind_param("sssssssi", $post["name"], $post["status"], $post["hosting"], $post["github_url"], $post['project_url'], $post['project_login_url'], date("Y-m-d H:i:s"), $post["id"]);
    $stmt->execute();
    $stmt->close();
    
    $stmt = $conn->prepare("SELECT * FROM projects WHERE id = ?");
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

$app->post('/projects/lists', function (Request $req, Response $res) use($conn) {
    $post = $req->getParsedBody();
    $stmt = $conn->prepare("UPDATE projects SET lists = ?, updated_at = ? WHERE id = ?");
    $stmt->bind_param("ssi", $post["lists"], date("Y-m-d H:i:s"), $post["id"]);
    $stmt->execute();
    
    $stmt->close();
    $stmt = $conn->prepare("SELECT * FROM projects WHERE id = ?");
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
