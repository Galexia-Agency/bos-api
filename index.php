<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
header("Access-Control-Allow-Headers: Authorization, Origin, X-Requested-With, Content-Type, Accept");
header("Content-Type: application/json; charset=UTF-8");
header("X-Frame-Options: DENY");
header("Strict-Transport-Security: max-age=15552000; preload");
header("Content-Security-Policy: default-src 'self'");
header("Referrer-Policy: no-referrer");
header("X-Content-Type-Options: nosniff");

error_reporting(E_ALL);

date_default_timezone_set('UTC');

require_once __DIR__ . '/vendor/autoload.php';

use RapidWeb\GoogleOAuth2Handler\GoogleOAuth2Handler;
use RapidWeb\GooglePeopleAPI\GooglePeople;
use RapidWeb\GooglePeopleAPI\Contact;

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();
$dotenv->required(['DATABASE_HOST', 'DATABASE_NAME', 'DATABASE_USER', 'DATABASE_PASS'])->notEmpty();

// Don't do anything for prefetch requests.
if ( $_SERVER['REQUEST_METHOD'] === 'OPTIONS' ) {
    return http_response_code( 200 );
};

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

$app->get('/', function (Request $req, Response $res, array $args) use($conn) {
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

    $stmt = $conn->prepare("SELECT * FROM contacts");
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

    $stmt = $conn->prepare("SELECT * FROM domains");
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

    $response = array();
    $response[0] = $clients;
    $response[1] = $contacts;
    $response[2] = $domains;
    $response[3] = $projects;

    return $res->withJson($response);
});

/* PROJECTS */

$app->post('/projects', function (Request $req, Response $res) use($conn) {
    $post = $req->getParsedBody();
    $stmt = $conn->prepare("UPDATE projects SET name = ?, status = ?, hosting = ?, github_url = ?, project_url = ?, project_login_url = ? WHERE id = ?");
    $stmt->bind_param("ssssssi", $post["name"], $post["status"], $post["hosting"], $post["github_url"], $post['project_url'], $post['project_login_url'], $post["id"]);
    $stmt->execute();
    $stmt->close();
    return $res->withJson('Updated successfuly.');
});

$app->post('/projects/lists', function (Request $req, Response $res) use($conn) {
    $post = $req->getParsedBody();
    $stmt = $conn->prepare("UPDATE projects SET lists = ? WHERE id = ?");
    $stmt->bind_param("si", $post["lists"], $post["id"]);
    $stmt->execute();
    $stmt->close();
    return $res->withJson('Updated successfuly.');
});

$app->put('/projects', function (Request $req, Response $res) use($conn) {
    $post = $req->getParsedBody();
    $stmt = $conn->prepare("INSERT into projects (client_id, name, status, project_url, project_login_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issssss", $post["client_id"], $post["name"], $post["status"], $post["hosting"], $post["github_url"], $post['project_url'], $post['project_login_url']);
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

/* CLIENTS */

$app->put('/clients', function (Request $req, Response $res) use($conn) {
    $post = $req->getParsedBody();
    $stmt = $conn->prepare("INSERT into clients (business_name, business_shortname, address) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $post["business_name"], $post["business_shortname"], $post["address"]);
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
    $stmt = $conn->prepare("UPDATE clients SET business_name = ?, business_shortname = ?, address = ? WHERE id = ?");
    $stmt->bind_param("sssi", $post["business_name"], $post["business_shortname"], $post["address"], $post["id"]);
    $stmt->execute();
    $stmt->close();
    return $res->withJson('Updated successfuly.');
});

/* CONTACTS */

$app->get('/contacts', function (Request $req, Response $res, array $args) use($conn) {
    $stmt = $conn->prepare("SELECT * FROM contacts");
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

    return $res->withJson($contacts);
});

$app->get('/google-contacts', function (Request $req, Response $res, array $args) use($conn) {
    $clientId     = '206947755814-ptg3rokaucqcefc1ccjf1io7cs7e3vj2.apps.googleusercontent.com';
    $clientSecret = 'XGzosEZeqZb9vRpwC_Orqqkw';
    $refreshToken = '1//03VgN58Cnvh26CgYIARAAGAMSNwF-L9Ir1OvrlqJc61US9Ctt_6dr-egcagUMi9IBcQAIBgL26qLsQmI4DBzVtUtr8AbPtf779ck';
    $scopes       = ['https://www.googleapis.com/auth/userinfo.profile', 'https://www.googleapis.com/auth/contacts', 'https://www.googleapis.com/auth/contacts.readonly'];
    $googleOAuth2Handler = new GoogleOAuth2Handler($clientId, $clientSecret, $scopes, $refreshToken);
    $people = new GooglePeople($googleOAuth2Handler);
    
    return $res->withJson($people->all());
});

$app->put('/contacts', function (Request $req, Response $res) use($conn) {
    $post = $req->getParsedBody();

    $clientId     = '206947755814-ptg3rokaucqcefc1ccjf1io7cs7e3vj2.apps.googleusercontent.com';
    $clientSecret = 'XGzosEZeqZb9vRpwC_Orqqkw';
    $refreshToken = '1//03VgN58Cnvh26CgYIARAAGAMSNwF-L9Ir1OvrlqJc61US9Ctt_6dr-egcagUMi9IBcQAIBgL26qLsQmI4DBzVtUtr8AbPtf779ck';
    $scopes       = ['https://www.googleapis.com/auth/userinfo.profile', 'https://www.googleapis.com/auth/contacts', 'https://www.googleapis.com/auth/contacts.readonly'];
    $googleOAuth2Handler = new GoogleOAuth2Handler($clientId, $clientSecret, $scopes, $refreshToken);
    $people = new GooglePeople($googleOAuth2Handler);

    $contact = new Contact($people);
    
    require('contact.php');

    $stmt = $conn->prepare("INSERT into contacts (client_id, f_name, l_name, tel, email, role, facebook, created_at, updated_at, google_contact_id, title) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issssssssss", $post["client_id"], $post["f_name"], $post["l_name"], $post["tel"], $post["email"], $post["role"], $post["facebook"], date("Y-m-d H:i:s"), date("Y-m-d H:i:s"), $contact->resourceName, $post["title"]);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare("SELECT * FROM contacts");
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

    return $res->withJson($contacts);
});

$app->post('/contacts', function (Request $req, Response $res) use($conn) {
    $post = $req->getParsedBody();

    $clientId     = '206947755814-ptg3rokaucqcefc1ccjf1io7cs7e3vj2.apps.googleusercontent.com';
    $clientSecret = 'XGzosEZeqZb9vRpwC_Orqqkw';
    $refreshToken = '1//03VgN58Cnvh26CgYIARAAGAMSNwF-L9Ir1OvrlqJc61US9Ctt_6dr-egcagUMi9IBcQAIBgL26qLsQmI4DBzVtUtr8AbPtf779ck';
    $scopes       = ['https://www.googleapis.com/auth/userinfo.profile', 'https://www.googleapis.com/auth/contacts', 'https://www.googleapis.com/auth/contacts.readonly'];
    $googleOAuth2Handler = new GoogleOAuth2Handler($clientId, $clientSecret, $scopes, $refreshToken);
    $people = new GooglePeople($googleOAuth2Handler);

    try {
        $people->get($post["google_contact_id"]);
        $contact = $people->get($post["google_contact_id"]);
    } catch (exception $e) {
        $contact = new Contact($people);
    }
    
    require('contact.php');

    $stmt = $conn->prepare("UPDATE contacts SET f_name = ?, l_name = ?, tel = ?, email = ?, role = ?, facebook = ?, updated_at = ?, google_contact_id = ?, title = ? WHERE id = ?");
    $stmt->bind_param("sssssssssi", $post["f_name"], $post["l_name"], $post["tel"], $post["email"], $post["role"], $post["facebook"], date("Y-m-d H:i:s"), $contact->resourceName, $post["title"], $post["id"]);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare("SELECT * FROM contacts WHERE id = ?");
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

$app->run();
