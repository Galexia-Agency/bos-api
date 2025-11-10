<?php
require_once __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();
$dotenv->required([
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
    'PANDLE_COMPANY_INCORPORATION',
    'ALLOWED_ORIGINS'
])->notEmpty();

$allowedOrigins = explode(',', getenv('ALLOWED_ORIGINS'));

$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';

if (in_array($origin, $allowedOrigins)) {
    header("Access-Control-Allow-Origin: " . $origin);
} else {
    // Handle unauthorized origin here
    header("HTTP/1.1 403 Forbidden");
    exit;
}
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
header("Access-Control-Allow-Headers: Authorization, Origin, X-Requested-With, Content-Type, Accept, access-token, client, uid, Cache-Control, last-event-id, CF-Access-Jwt-Assertion");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json; charset=UTF-8");
// deepcode ignore TooPermissiveXFrameOptions: False Positive
header("X-Frame-Options: DENY");
header("Strict-Transport-Security: max-age=15552000; preload");
header("Content-Security-Policy: default-src 'self'");
header("Referrer-Policy: no-referrer");
header("X-Content-Type-Options: nosniff");

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ERROR | E_PARSE);
date_default_timezone_set('UTC');

// Don't do anything for prefetch requests.
if ( $_SERVER['REQUEST_METHOD'] === 'OPTIONS' ) {
    return http_response_code( 200 );
};

require_once('redis.php');

$db_host = $_ENV['DATABASE_HOST'];
$db_name = $_ENV['DATABASE_NAME'];
$db_user = $_ENV['DATABASE_USER'];
$db_pass = $_ENV['DATABASE_PASS'];

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Connect to the database
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

$conn->set_charset("utf8mb4");

function fetchAllFromTable($conn, $table)
{
    $allowedTables = array(
        'clients',
        'contacts',
        'domains',
        'projects',
        'products',
        'pandleDashboard'
    );

    if (!in_array($table, $allowedTables, true)) {
        return array();
    }

    $query = sprintf('SELECT * FROM %s', $table);
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $result = $stmt->get_result();

    $rows = array();
    while ($row = $result->fetch_assoc()) {
        if ($row) {
            $rows[] = $row;
        }
    }

    $stmt->close();

    return $rows;
}

function getDashboardData($conn)
{
    $clients = fetchAllFromTable($conn, 'clients');
    $contacts = fetchAllFromTable($conn, 'contacts');
    $domains = fetchAllFromTable($conn, 'domains');
    $projects = fetchAllFromTable($conn, 'projects');
    $products = fetchAllFromTable($conn, 'products');
    $pandleDashboard = fetchAllFromTable($conn, 'pandleDashboard');

    $response = array();
    $response[0] = $clients;
    $response[1] = $contacts;
    $response[2] = $domains;
    $response[3] = $projects;
    $response[4] = $products;
    $response[5] = $pandleDashboard;

    return $response;
}

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
    $response = getDashboardData($conn);

    return $res->withJson($response);
});

require_once('projects.php');

require_once('clients.php');

require_once('contacts.php');

require_once('domains.php');

require_once('products.php');

require_once('pandle.php');

$app->run();
