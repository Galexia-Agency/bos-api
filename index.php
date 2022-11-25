<?php
if ($_SERVER['HTTP_ORIGIN']) {
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
} else {
    header("Access-Control-Allow-Origin: *");
}
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
header("Access-Control-Allow-Headers: Authorization, Origin, X-Requested-With, Content-Type, Accept, access-token, client, uid, Cache-Control, last-event-id");
header("Content-Type: application/json; charset=UTF-8");
header("X-Frame-Options: DENY");
header("Strict-Transport-Security: max-age=15552000; preload");
header("Content-Security-Policy: default-src 'self'");
header("Referrer-Policy: no-referrer");
header("X-Content-Type-Options: nosniff");

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ERROR | E_PARSE);
date_default_timezone_set('UTC');

require_once __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();
$dotenv->required([
    'CLIENT_ID',
    'ISSUER',
    'DATABASE_HOST',
    'DATABASE_NAME',
    'DATABASE_USER',
    'DATABASE_PASS',
    'GOOGLE_CLIENT_ID',
    'GOOGLE_CLIENT_SECRET',
    'GOOGLE_REFRESH_TOKEN',
    'PANDLE_USERNAME',
    'PANDLE_PASSWORD',
    'PANDLE_COMPANY_ID',
    'PANDLE_COMPANY_INCORPORATION'
])->notEmpty();

// Don't do anything for prefetch requests.
if ( $_SERVER['REQUEST_METHOD'] === 'OPTIONS' ) {
    return http_response_code( 200 );
    exit;
};

// Make sure the authorization header is available, if not return 401.
if (!isset( $_SERVER['HTTP_AUTHORIZATION'] ) ) {
    echo "no authorization";
    return http_response_code( 401 );
    exit;
} else {
    list( $authType, $authData ) = explode( " ", $_SERVER['HTTP_AUTHORIZATION'], 2 );
};

// If the Authorization Header is not a bearer type, return a 401.
if ( $authType != 'Bearer' ) {
    echo "not bearer";
    return http_response_code( 401 );
    exit;
};

$ISSUER = $_ENV['ISSUER'];
$CLIENT_ID = $_ENV['CLIENT_ID'];
$CLIENT_SECRET = $_ENV['CLIENT_SECRET'];
$ch = curl_init("$ISSUER/default/v1/introspect?token=$authData&token_type_hint=access_token");
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    "Accept: application/json",
    "Content-Type: application/x-www-form-urlencoded",
    "Origin: https://api.galexia.agency",
    "Authorization: Basic " . base64_encode("$CLIENT_ID:$CLIENT_SECRET")
));

// execute!
$response = json_decode(curl_exec($ch), true);

if (!curl_errno($ch)) {
    switch ($http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE)) {
        case 200:  # OK
            continue;
        default:
            return http_response_code( 401 );
            exit();
    }
} else {
    return http_response_code( 500 );
    exit();
}
if (!$response['active']) {
    return http_response_code( 401 );
    exit();
}

$email = $response['username'];

$db_host = $_ENV['DATABASE_HOST'];
$db_name = $_ENV['DATABASE_NAME'];
$db_user = $_ENV['DATABASE_USER'];
$db_pass = $_ENV['DATABASE_PASS'];

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Connect to the database
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

$conn->set_charset("utf8mb4");

// Import classes from the Psr library (standardised HTTP requests and responses)
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use Slim\Factory\AppFactory;

// Create our app.
$app = AppFactory::create();

// Add routing functionality to Slim. This is not included by default and
// must be turned on.
$app->addRoutingMiddleware();

// Add error handling functionality. The three 'true's indicate:
// - first argument: display full error details
// - second argument: call Slim error handler
// - third argument: log error details

$app->addErrorMiddleware(true, true, true);
 
// For the routes to work correctly, you must set your base path.
// This is the relative path of your webspace on the server, including the
// folder you're using but NOT public_html. Here we are assuming the Slim app
// is saved in the 'slimapp' folder within 'public_html' 
$app->setBasePath('');

// Create our PHP renderer object
$view = new \Slim\Views\PhpRenderer('views');

$app->get('/get', function (Request $req, Response $res, array $args) use($conn) {
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

    /* Contacts */
    $stmt = $conn->prepare("SELECT * FROM contacts WHERE client_id IN ($client_ids_to_search)");
    $stmt->execute();
    $result = $stmt->get_result();
    $contacts = array();
    while($row = $result->fetch_assoc()) {
        if($row) {
            $contacts[] = $row;
        }
        else {
            return $res->withJson(null);
        }
    };
    $stmt->close();

    /* Domains */
    $stmt = $conn->prepare("SELECT * FROM domains WHERE project_id IN ($project_ids_to_search)");
    $stmt->execute();
    $result = $stmt->get_result();
    $domains = array();
    while($row = $result->fetch_assoc()) {
        if($row) {
            $domains[] = $row;
        }
        else {
            return $res->withJson(null);
        }
    };
    $stmt->close();

    /* Products */
    $stmt = $conn->prepare("SELECT * FROM products");
    $stmt->execute();
    $result = $stmt->get_result();
    $products = array();
    while($row = $result->fetch_assoc()) {
        if($row) {
            $products[] = $row;
        }
        else {
            return $res->withJson(null);
        }
    };
    $stmt->close();

    /* Pandle Dashboard */
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

    $response = array();
    $response[0] = $clients;
    $response[1] = $contacts;
    $response[2] = $domains;
    $response[3] = $projects;
    $response[4] = $products;
    $response[5] = $pandleDashboard;

    return $res->withJson($response);
});

require_once('projects.php');

require_once('clients.php');

require_once('contacts.php');

require_once('domains.php');

require_once('products.php');

require_once('pandle.php');

$app->run();
