<?php
/* CLIENTS */
// Import classes from the Psr library (standardised HTTP requests and responses)
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

$app->put('/clients', function (Request $req, Response $res) use($conn) {
    // All client ids from the projects table
    $project_client_ids = array();
    // All client ids in general
    $all_client_ids = array();
    
    // Fetch all projects
    $stmt = $conn->prepare("SELECT * FROM projects");
    $stmt->execute();
    $result = $stmt->get_result();
    $allProjects = array();
    while($row = $result->fetch_assoc()) {
        if($row) {
            $allProjects[] = $row;
        }
        else {
            return $res->withJson(null);
        }
    };

    // Fetch all clients
    $stmt = $conn->prepare("SELECT * FROM clients");
    $stmt->execute();
    $result = $stmt->get_result();
    $allClients = array();
    while($row = $result->fetch_assoc()) {
        if($row) {
            $allClients[] = $row;
        }
        else {
            return $res->withJson(null);
        }
    };

    // Add all client ids to the client id array
    foreach($allClients as $client) {
        array_push($all_client_ids, $client['id']);
    }

    // Delete the client id from the all client id array if there is an associated project, this leaves just the clients with no projects
    foreach ($allProjects as $project) {
        if (($key = array_search($project['client_id'], $all_client_ids)) !== false) {
            unset($all_client_ids[$key]);
        }
    }

    // Get the email from Okta
    global $email;

    // Fetch all projects where the logged in user has some access
    $stmt = $conn->prepare("SELECT * FROM projects WHERE FIND_IN_SET('" . $email . "', viewer) OR FIND_IN_SET('" . $email . "', contributor) OR FIND_IN_SET('" . $email . "', admin)");
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

    // An array for all project ids
    $project_ids = array();

    // Loop through all projects
    foreach ($projects as $project) {
        // Add the client id from the project to the project client ids array
        array_push($project_client_ids, $project['client_id']);
        // Add the project id to the project id array
        array_push($project_ids, $project['id']);
    }

    // These are the client ids that we want to return to the user, we merge the clients with no projects and the clients with projects the user has access to
    $client_ids_to_search = implode(",",array_merge($project_client_ids, $all_client_ids));
    $project_ids_to_search = implode(",",$project_ids);

    $stmt->close();
    $stmt = $conn->prepare("SELECT * FROM clients WHERE id IN ($client_ids_to_search)");
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
    $stmt = $conn->prepare("UPDATE clients SET business_name = ?, business_shortname = ?, about = ?, address = ?, source = ?, updated_at = ?, pandle_id = ?, billing_email = ? WHERE id = ?");
    $stmt->bind_param("ssssssssi", $post["business_name"], $post["business_shortname"], $post['about'], $post["address"], $post["source"], $dateTime, $post["pandle_id"], $post["billing_email"], $post["id"]);
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
