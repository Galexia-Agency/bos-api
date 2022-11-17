<?php
/* PROJECTS */
// Import classes from the Psr library (standardised HTTP requests and responses)
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

function selectProjectById($conn, $id)
{
    $stmt = $conn->prepare("SELECT * FROM projects WHERE id = ?");
    $stmt->bind_param("i", $id);
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

$app->put('/projects', function (Request $req, Response $res) use ($conn) {
    $post = $req->getParsedBody();
    $stmt = $conn->prepare("INSERT into projects (client_id, name, status, hosting, php, github_url, drive_url, project_url, project_login_url, created_at, updated_at, pandle_id, completion_amount, bb_revenue, bb_expenses, viewer, contributor, admin, enquiry_date, start_date, ongoing, completion_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssssssssssiiisssssis", $post["client_id"], $post["name"], $post["status"], $post["hosting"], $post["php"], $post["github_url"], $post["drive_url"], $post['project_url'], $post['project_login_url'], date("Y-m-d H:i:s"), date("Y-m-d H:i:s"), $post["pandle_id"], $post["completion_amount"], $post["bb_revenue"], $post["bb_expenses"], $post["viewer"], $post["contributor"], $post["admin"], $post["enquiry_date"], $post["start_date"], $post["ongoing"], $post["completion_date"]);
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

    // Fetch the project by id from the database
    $response = selectProjectById($conn, $post["id"]);

    // If we're forcing the change then skip the check for updated content
    if (!$post["force"] && $post["updated_at"]) {
        if ($response) {
            if ($post["updated_at"] < $response[0]['updated_at']) {
                return $res->withStatus(429)->withJson($response);
            }
        } else {
            return $res->withJson(null);
        }
    }

    $stmt = $conn->prepare("UPDATE projects SET name = ?, status = ?, hosting = ?, php = ?, github_url = ?, drive_url = ?, project_url = ?, project_login_url = ?, updated_at = ?, pandle_id =?, completion_amount = ?, bb_revenue = ?, bb_expenses = ?, viewer = ?, contributor = ?, admin = ?, enquiry_date = ?, start_date = ?, ongoing = ?, completion_date = ? WHERE id = ?");
    $stmt->bind_param("ssssssssssiiisssssisi", $post["name"], $post["status"], $post["hosting"], $post["php"], $post["github_url"], $post["drive_url"], $post['project_url'], $post['project_login_url'], date("Y-m-d H:i:s"), $post["pandle_id"], $post["completion_amount"], $post["bb_revenue"], $post["bb_expenses"], $post["viewer"], $post["contributor"], $post["admin"], $post["enquiry_date"], $post["start_date"], $post["ongoing"], $post["completion_date"], $post["id"]);
    $stmt->execute();
    $stmt->close();

    // Fetch the project by id from the database
    $response = selectProjectById($conn, $post["id"]);

    return $res->withJson($response);
});

$app->post('/projects/lists', function (Request $req, Response $res) use ($conn) {
    $post = $req->getParsedBody();

    // Fetch the project by id from the database
    $response = selectProjectById($conn, $post["id"]);

    // If we're forcing the change then skip the check for updated content
    if (!$post["force"] && $post["updated_at"]) {
        if ($response) {
            if ($post["updated_at"] < $response[0]['updated_at']) {
                return $res->withStatus(429)->withJson($response);
            }
        } else {
            return $res->withJson(null);
        }
    }

    $stmt = $conn->prepare("UPDATE projects SET lists = ?, updated_at = ? WHERE id = ?");
    $stmt->bind_param("ssi", $post["lists"], date("Y-m-d H:i:s"), $post["id"]);
    $stmt->execute();
    $stmt->close();

    // Fetch the project by id from the database
    $response = selectProjectById($conn, $post["id"]);

    return $res->withJson($response);
});

$app->get('/projects/sse', function (Request $req, Response $res) use ($conn) {
    // If this isn't the first request, return a response after 10 seconds
    if ($_SERVER['HTTP_LAST_EVENT_ID']) {
        sleep(10);
    }

    // If the id parameter is missing
    if (!$_GET['id']) {
        return $res->withStatus(400)->withJson("Bad request - missing id parameter");
    }

    // Fetch the data
    $response = selectProjectById($conn, $_GET['id']);

    // Write the data
    return $res
        ->withHeader('Content-Type', 'text/event-stream')
        ->withHeader('Cache-Control', 'no-cache')
        ->withHeader('Connection', 'keep-alive')
        ->withHeader('X-Accel-Buffering', 'no')
        ->write("event: " . $response[0]['id'] . "\n")
        ->write("id: " . $response[0]['updated_at'] . "\n")
        ->write("data: " . json_encode($response) . "\n\n");
});
