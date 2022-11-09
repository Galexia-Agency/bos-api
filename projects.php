<?php
/* PROJECTS */
// Import classes from the Psr library (standardised HTTP requests and responses)
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

$app->put('/projects', function (Request $req, Response $res) use ($conn) {
    $post = $req->getParsedBody();
    $stmt = $conn->prepare("INSERT into projects (client_id, name, status, hosting, github_url, drive_url, project_url, project_login_url, created_at, updated_at, pandle_id, completion_amount, bb_revenue, bb_expenses, viewer, contributor, admin) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issssssssssiiisss", $post["client_id"], $post["name"], $post["status"], $post["hosting"], $post["github_url"], $post["drive_url"], $post['project_url'], $post['project_login_url'], date("Y-m-d H:i:s"), date("Y-m-d H:i:s"), $post["pandle_id"], $post["completion_amount"], $post["bb_revenue"], $post["bb_expenses"], $post["viewer"], $post["contributor"], $post["admin"]);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare("SELECT * FROM projects");
    $stmt->execute();
    $result = $stmt->get_result();
    $projects = array();
    while ($row = $result->fetch_assoc()) {
        if ($row) {
            $projects[] = $row;
        } else {
            return $res->withJson(null);
        }
    };
    $stmt->close();

    return $res->withJson($projects);
});

$app->post('/projects', function (Request $req, Response $res) use ($conn) {
    $post = $req->getParsedBody();
    $stmt = $conn->prepare("UPDATE projects SET name = ?, status = ?, hosting = ?, github_url = ?, drive_url = ?, project_url = ?, project_login_url = ?, updated_at = ?, pandle_id =?, completion_amount = ?, bb_revenue = ?, bb_expenses = ?, viewer = ?, contributor = ?, admin = ? WHERE id = ?");
    $stmt->bind_param("sssssssssiiisssi", $post["name"], $post["status"], $post["hosting"], $post["github_url"], $post["drive_url"], $post['project_url'], $post['project_login_url'], date("Y-m-d H:i:s"), $post["pandle_id"], $post["completion_amount"], $post["bb_revenue"], $post["bb_expenses"], $post["viewer"], $post["contributor"], $post["admin"], $post["id"]);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare("SELECT * FROM projects WHERE id = ?");
    $stmt->bind_param("i", $post["id"]);
    $stmt->execute();
    $result = $stmt->get_result();

    $response = array();

    while ($row = $result->fetch_assoc()) {
        if ($row) {
            $response[] = $row;
        } else {
            return $res->withJson(null);
        }
    };

    return $res->withJson($response);
});

$app->post('/projects/lists', function (Request $req, Response $res) use ($conn) {
    $post = $req->getParsedBody();
    // If we're forcing the change then skip the check for updated content
    if (!$post["force"] && $post["updated_at"]) {
        // Fetch the project by id from the database
        $stmt = $conn->prepare("SELECT * from projects WHERE id = ?");
        $stmt->bind_param("i", $post["id"]);
        $stmt->execute();
        $result = $stmt->get_result();

        $response = array();

        while ($row = $result->fetch_assoc()) {
            if ($row) {
                // If the database project is newer than the clients project then return the databases version with a 429 status code
                $response[] = $row;
                if ($post["updated_at"] < $row['updated_at']) {
                    return $res->withStatus(429)->withJson($response);
                }
            } else {
                return $res->withJson(null);
            }
        };
        $stmt->close();
    }

    $stmt = $conn->prepare("UPDATE projects SET lists = ?, updated_at = ? WHERE id = ?");
    $stmt->bind_param("ssi", $post["lists"], date("Y-m-d H:i:s"), $post["id"]);
    $stmt->execute();
    $stmt->close();
    $stmt = $conn->prepare("SELECT * FROM projects WHERE id = ?");
    $stmt->bind_param("i", $post["id"]);
    $stmt->execute();
    $result = $stmt->get_result();

    $response = array();

    while ($row = $result->fetch_assoc()) {
        if ($row) {
            $response[] = $row;
        } else {
            return $res->withJson(null);
        }
    };

    return $res->withJson($response);
});
