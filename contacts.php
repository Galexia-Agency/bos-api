<?php
/* CONTACTS */
// Import classes from the Psr library (standardised HTTP requests and responses)
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use RapidWeb\GoogleOAuth2Handler\GoogleOAuth2Handler;
use RapidWeb\GooglePeopleAPI\GooglePeople;
use RapidWeb\GooglePeopleAPI\Contact;

$clientId     = $_ENV['GOOGLE_CLIENT_ID'];
$clientSecret = $_ENV['GOOGLE_CLIENT_SECRET'];
$refreshToken = $_ENV['GOOGLE_REFRESH_TOKEN'];
$scopes       = ['https://www.googleapis.com/auth/userinfo.profile', 'https://www.googleapis.com/auth/contacts', 'https://www.googleapis.com/auth/contacts.readonly'];
$googleOAuth2Handler = new GoogleOAuth2Handler($clientId, $clientSecret, $scopes, $refreshToken);
$people = new GooglePeople($googleOAuth2Handler);

function selectContactById ($conn, $id) {
    $stmt = $conn->prepare("SELECT * FROM contacts WHERE id = ?");
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

// $app->get('/contacts', function (Request $req, Response $res, array $args) use($conn) {
//     $stmt = $conn->prepare("SELECT * FROM contacts");
//     $stmt->execute();
//     $result = $stmt->get_result();
//     $contacts = array();
//     while($row = $result->fetch_assoc()) {
//         if($row) {
//             $contacts[] = $row;
//         }
//         else {
//             return $res->withJson(null);
//         }
//     };
//     $stmt->close();

//     return $res->withJson($contacts);
// });

// $app->get('/google-contacts', function (Request $req, Response $res, array $args) use($conn) {
//     global $people;
//     return $res->withJson($people->all());
// });

$app->put('/contacts', function (Request $req, Response $res) use($conn) {
    global $people;

    $post = $req->getParsedBody();

    $contact = new Contact($people);
    
    require('components/contact.php');

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
    global $people;
    $post = $req->getParsedBody();

    // Fetch the project by id from the database
    $response = selectContactById($conn, $post["id"]);

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

    try {
        $contact = $people->get($post["google_contact_id"]);
    } catch (exception $e) {
        $contact = new Contact($people);
    }
    
    require('components/contact.php');

    $stmt = $conn->prepare("UPDATE contacts SET f_name = ?, l_name = ?, tel = ?, email = ?, role = ?, facebook = ?, updated_at = ?, google_contact_id = ?, title = ? WHERE id = ?");
    $stmt->bind_param("sssssssssi", $post["f_name"], $post["l_name"], $post["tel"], $post["email"], $post["role"], $post["facebook"], date("Y-m-d H:i:s"), $contact->resourceName, $post["title"], $post["id"]);
    $stmt->execute();
    $stmt->close();

    return $res->withJson(selectContactById($conn, $post["id"]));
});
