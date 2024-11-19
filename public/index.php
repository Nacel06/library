<?php  

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

require '../src/vendor/autoload.php';

$app = new \Slim\App;

$key = 'server_hack'; // Secret key for JWT

// Database connection function
function getDBConnection() {
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "library";
    return new PDO("mysql:host=$servername;dbname=$dbname", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
}

// Middleware for token authentication and regeneration
$authenticate = function ($request, $response, $next) use ($key) {
    $authHeader = $request->getHeader('Authorization');

    if (!$authHeader) {
        return $response->withStatus(401)->write(json_encode([
            "status" => "fail",
            "token" => null,
            "data" => ["title" => "Missing Authorization Header"]
        ]));
    }

    $token = explode(' ', $authHeader[0])[1]; // Extract the token

    try {
        $decoded = JWT::decode($token, new Key($key, 'HS256'));

        // Check if the token is blacklisted
        $conn = getDBConnection();
        $stmt = $conn->prepare("SELECT * FROM token_blacklist WHERE token = :token");
        $stmt->execute(['token' => $token]);

        if ($stmt->rowCount() > 0) {
            return $response->withStatus(401)->write(json_encode([
                "status" => "fail",
                "token" => null,
                "data" => ["title" => "Token has been revoked"]
            ]));
        }

        // Proceed with the request
        $request = $request->withAttribute('user', $decoded->data);
        $response = $next($request, $response);

        // Regenerate a new token after processing the request
        $iat = time();
        $newPayload = [
            'iss' => 'http://library.org',
            'aud' => 'http://library.com',
            'iat' => $iat,
            'exp' => $iat + 3600, // Token valid for 1 hour
            'data' => $decoded->data
        ];
        $newToken = JWT::encode($newPayload, $key, 'HS256');

        // Add the old token to the blacklist
        $stmt = $conn->prepare("INSERT INTO token_blacklist (token) VALUES (:token)");
        $stmt->execute(['token' => $token]);

        // Include the new token in the response body
        $responseBody = json_decode($response->getBody()->__toString(), true);
        $responseBody['token'] = $newToken;

        return $response->withJson($responseBody);

    } catch (Exception $e) {
        return $response->withStatus(401)->write(json_encode([
            "status" => "fail",
            "token" => null,
            "data" => ["title" => "Invalid Token"]
        ]));
    }
};

// User registration
$app->post('/user/register', function (Request $request, Response $response) {
    $data = json_decode($request->getBody());
    $usr = $data->username;
    $pass = $data->password;

    try {
        $conn = getDBConnection();

        // Check if the username already exists
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = :username");
        $stmt->execute(['username' => $usr]);

        if ($stmt->rowCount() > 0) {
            // If username exists, return an error message
            $response->getBody()->write(json_encode([
                "status" => "fail",
                "data" => ["title" => "Username already exists!"]
            ]));
            return $response->withStatus(400);
        }

        // Proceed to create the user if username does not exist
        $sql = "INSERT INTO users (username, password) VALUES (:username, :password)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            'username' => $usr,
            'password' => hash('SHA256', $pass),
        ]);

        $response->getBody()->write(json_encode([
            "status" => "success",
            "token" => null, // Token will be replaced in middleware
            "data" => null
        ]));
    } catch (PDOException $e) {
        $response->getBody()->write(json_encode([
            "status" => "fail",
            "token" => null,
            "data" => ["title" => $e->getMessage()]
        ]));
    }

    return $response;
});

// User authentication (Generate new token)
$app->post('/user/authenticate', function (Request $request, Response $response) use ($key) {
    $data = json_decode($request->getBody());
    $usr = $data->username;
    $pass = $data->password;

    try {
        $conn = getDBConnection();
        $stmt = $conn->prepare(
            "SELECT * FROM users WHERE username = :username AND password = :password"
        );
        $stmt->execute([
            'username' => $usr,
            'password' => hash('SHA256', $pass),
        ]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $iat = time();
            $payload = [
                'iss' => 'http://library.org',
                'aud' => 'http://library.com',
                'iat' => $iat,
                'exp' => $iat + 3600, // Token valid for 1 hour
                'data' => ["userid" => $user['userid']]
            ];
            $jwt = JWT::encode($payload, $key, 'HS256');

            $response->getBody()->write(json_encode([
                "status" => "success",
                "token" => $jwt,
                "data" => null
            ]));
        } else {
            $response->getBody()->write(json_encode([
                "status" => "fail",
                "token" => null,
                "data" => ["title" => "Authentication Failed!"]
            ]));
        }
    } catch (PDOException $e) {
        $response->getBody()->write(json_encode([
            "status" => "fail",
            "token" => null,
            "data" => ["title" => $e->getMessage()]
        ]));
    }

    return $response;
});

// Add a new author (protected route, requires authentication)
$app->post('/authors/add', function (Request $request, Response $response) {
    $data = json_decode($request->getBody());
    $name = $data->name;

    try {
        $conn = getDBConnection();
        $sql = "INSERT INTO authors (name) VALUES (:name)";
        $stmt = $conn->prepare($sql);
        $stmt->execute(['name' => $name]);

        $response->getBody()->write(json_encode([
            "status" => "success",
            "token" => null, // Token will be replaced in middleware
            "data" => null
        ]));
    } catch (PDOException $e) {
        $response->getBody()->write(json_encode([
            "status" => "fail",
            "token" => null,
            "data" => ["title" => $e->getMessage()]
        ]));
    }

    return $response;
})->add($authenticate); 

// Get list of authors (protected route, requires authentication)
$app->get('/authors', function (Request $request, Response $response) {
    try {
        $conn = getDBConnection();
        $stmt = $conn->prepare("SELECT * FROM authors");
        $stmt->execute();
        $authors = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $response->getBody()->write(json_encode([
            "status" => "success",
            "token" => null, // Token will be replaced in middleware
            "data" => $authors
        ]));
    } catch (PDOException $e) {
        $response->getBody()->write(json_encode([
            "status" => "fail",
            "token" => null,
            "data" => ["title" => $e->getMessage()]
        ]));
    }

    return $response;
})->add($authenticate); 

// Add a new book (protected route, requires authentication)
$app->post('/books/add', function (Request $request, Response $response) {
    $data = json_decode($request->getBody());
    $title = $data->title;
    $author_id = $data->author_id;

    try {
        $conn = getDBConnection();
        $sql = "INSERT INTO books (title, author_id) VALUES (:title, :author_id)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            'title' => $title,
            'author_id' => $author_id
        ]);

        $response->getBody()->write(json_encode([
            "status" => "success",
            "token" => null, // Token will be replaced in middleware
            "data" => null
        ]));
    } catch (PDOException $e) {
        $response->getBody()->write(json_encode([
            "status" => "fail",
            "token" => null,
            "data" => ["title" => $e->getMessage()]
        ]));
    }

    return $response;
})->add($authenticate); 

// Get list of books (protected route, requires authentication)
$app->get('/books', function (Request $request, Response $response) {
    try {
        $conn = getDBConnection();
        $stmt = $conn->prepare("SELECT * FROM books");
        $stmt->execute();
        $books = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $response->getBody()->write(json_encode([
            "status" => "success",
            "token" => null, // Token will be replaced in middleware
            "data" => $books
        ]));
    } catch (PDOException $e) {
        $response->getBody()->write(json_encode([
            "status" => "fail",
            "token" => null,
            "data" => ["title" => $e->getMessage()]
        ]));
    }

    return $response;
})->add($authenticate); 

// Add relationship between books and authors
$app->post('/books/authors/add', function (Request $request, Response $response) {
    $data = json_decode($request->getBody());
    $book_id = $data->book_id;
    $author_id = $data->author_id;

    try {
        $conn = getDBConnection();
        $sql = "INSERT INTO books_authors (book_id, author_id) VALUES (:book_id, :author_id)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            'book_id' => $book_id,
            'author_id' => $author_id
        ]);

        $response->getBody()->write(json_encode([
            "status" => "success",
            "token" => null, // Token will be replaced in middleware
            "data" => null
        ]));
    } catch (PDOException $e) {
        $response->getBody()->write(json_encode([
            "status" => "fail",
            "token" => null,
            "data" => ["title" => $e->getMessage()]
        ]));
    }

    return $response;
})->add($authenticate);

// Get list of entries in books_authors (protected route, requires authentication)
$app->get('/books_authors', function (Request $request, Response $response, array $args) {
    try {
        $conn = getDBConnection();
        $sql = "SELECT * FROM books_authors";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $entries = $stmt->fetchAll();

        $response->getBody()->write(json_encode(array("status" => "success", "data" => $entries)));
    } catch (PDOException $e) {
        $response->getBody()->write(json_encode(array("status" => "fail", "data" => array("title" => $e->getMessage()))));
    }

    return $response;
})->add($authenticate); 

// Run the application