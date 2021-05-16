<?php
/* CONTACTS */
// Import classes from the Psr library (standardised HTTP requests and responses)
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use RapidWeb\GoogleOAuth2Handler\GoogleOAuth2Handler;
use RapidWeb\GooglePeopleAPI\GooglePeople;
use RapidWeb\GooglePeopleAPI\Contact;

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
    
    require('components/contact.php');

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

?>