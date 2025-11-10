<?php
/* CLIENTS */
// Import classes from the Psr library (standardised HTTP requests and responses)
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

$app->put('/clients', function (Request $req, Response $res) use($conn) {
    $post = $req->getParsedBody();
    $stmt = $conn->prepare("
        INSERT into clients (
            business_name,
            business_shortname,
            about,
            address,
            address_line_1,
            address_line_2,
            address_line_3,
            address_town,
            address_county,
            address_postcode,
            address_country,
            source,
            created_at,
            updated_at,
            pandle_id,
            billing_email
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param(
        "ssssssssssssssss",
        $post["business_name"],
        $post["business_shortname"],
        $post['about'],
        $post["address"],
        $post["address_line_1"],
        $post["address_line_2"],
        $post["address_line_3"],
        $post["address_town"],
        $post["address_county"],
        $post["address_postcode"],
        $post["address_country"],
        $post["source"],
        date("Y-m-d H:i:s"),
        date("Y-m-d H:i:s"),
        $post["pandle_id"],
        $post["billing_email"]
    );
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

function selectClientById ($conn, $id) {
    $stmt = $conn->prepare("SELECT * FROM clients WHERE id = ?");
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

$app->post('/clients', function (Request $req, Response $res) use($conn) {
    $post = $req->getParsedBody();

    // Fetch the project by id from the database
    $response = selectClientById($conn, $post["id"]);

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

    $stmt = $conn->prepare("
        UPDATE clients
        SET
            business_name = ?,
            business_shortname = ?,
            about = ?,
            address = ?,
            address_line_1 = ?,
            address_line_2 = ?,
            address_line_3 = ?,
            address_town = ?,
            address_county = ?,
            address_postcode = ?,
            address_country = ?,
            source = ?,
            updated_at = ?,
            pandle_id = ?,
            billing_email = ?
        WHERE id = ?
    ");
    $stmt->bind_param(
        "sssssssssssssssi",
        $post["business_name"],
        $post["business_shortname"],
        $post['about'],
        // ToDo
        // Remove this when migrated
        $post["address"],
        $post["address_line_1"],
        $post["address_line_2"],
        $post["address_line_3"],
        $post["address_town"],
        $post["address_county"],
        $post["address_postcode"],
        $post["address_country"],
        $post["source"],
        date("Y-m-d H:i:s"),
        $post["pandle_id"],
        $post["billing_email"],
        $post["id"]
    );
    $stmt->execute();
    $stmt->close();

    // Fetch the project by id from the database
    $response = selectClientById($conn, $post["id"]);

    return $res->withJson($response);
});

?>
