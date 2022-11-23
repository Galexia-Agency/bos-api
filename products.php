<?php
/* PRODUCTS */
// Import classes from the Psr library (standardised HTTP requests and responses)
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

function selectProductById ($conn, $id) {
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
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

$app->put('/products', function (Request $req, Response $res) use($conn) {
    $post = $req->getParsedBody();

    $stmt = $conn->prepare("INSERT into products (name, type, price, created_at, updated_at) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $post["name"], $post["type"], $post["price"], date("Y-m-d H:i:s"), date("Y-m-d H:i:s"));
    $stmt->execute();
    $stmt->close();

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

    return $res->withJson($products);
});

$app->post('/products', function (Request $req, Response $res) use($conn) {
    $post = $req->getParsedBody();

    // Fetch the product by id from the database
    $response = selectProductById($conn, $post["id"]);

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

    $stmt = $conn->prepare("UPDATE products SET name = ?, type = ?, price = ?, updated_at = ? WHERE id = ?");
    $stmt->bind_param("ssssi", $post["name"], $post["type"], $post["price"], date("Y-m-d H:i:s"), $post["id"]);
    $stmt->execute();
    $stmt->close();

    return $res->withJson(selectProductById($conn, $post["id"]));
});
