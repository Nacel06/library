
# Library Management System

This is a RESTful API for a Library Management System built with PHP, the Slim Framework, and JWT (JSON Web Token) for authentication. It provides endpoints for user registration, authentication, and managing authors, books, and relationships between them.

## Features

- **User Registration**: Create a new user account.
- **User Authentication**: Authenticate users and generate JWT tokens for secure access.
- **Token Middleware**: Protect routes with JWT authentication, handle token regeneration, and blacklist expired tokens.
- **CRUD Operations**:
  - Add and list authors.
  - Add and list books.
  - Manage relationships between books and authors.

## Technology Stack

- **PHP**: Server-side scripting.
- **Slim Framework**: Micro-framework for building REST APIs.
- **JWT (Firebase/JWT)**: Library for JWT handling.
- **MySQL**: Database for storing user, author, and book information.
- **SQLyog**: Database management tool for MySQL.



## API Endpoints

### 1. **User Endpoints**

#### **Register a User**
- **Method**: `POST`
- **Endpoint**: `/user/register`
- **Request Body**:
  ```json
  {
    "username": "yourUsername",
    "password": "yourPassword"
  }
- **Request Body**:
  ```json
  {
  "status": "success",
  "token": null,
  "data": null
  }
- **Request Body**:
  ```json
  {
  "status": "fail",
  "data": {
    "title": "Username already exists!"
  }
  }

#### **Authenticate a User**
- **Method**: `POST`
- **Endpoint**: '/user/authenticate`
- **Request Body**:
  ```json
  {
  "username": "yourUsername",
  "password": "yourPassword"
  }
- **Request Body**:
  ```json
  {
  "status": "success",
  "token": "your_jwt_token",
  "data": null
  }
- **Request Body**:
  ```json
  {
  "status": "fail",
  "token": null,
  "data": {
    "title": "Authentication Failed!"
  }
  }

### 2. **Author Endpoints**
#### Add a new Author
- **Method**: `POST`
- **Endpoint**: '/authors/add`
- **Request Body**:
  ```json
  {
  "name": "Author Name"
  }
- **Request Body**:
  ```json
  {
  "status": "success",
  "token": "new_jwt_token",
  "data": null
  }
### Get lists of Authors
- **Method**: `GET`
- **Endpoint**: '/authors'
- **Request Body**:
  ```json
  {
  "status": "success",
  "token": "new_jwt_token",
  "data": [
    {
      "author_id": 1,
      "name": "Author Name"
    },
    {
      "author_id": 2,
      "name": "Another Author"
    }
  ]
  }

### 3. **Book Endpoints**
#### Add a new Book
- **Method**: `POST`
- **Endpoint**: '/books/add`
- **Request Body**:
  ```json
  {
  "title": "Book Title",
  "author_id": 1
  }
#### Get lists of book
- **Method**: `POST`
- **Endpoint**: `/books'
- **Request Body**:
  ```json
  {
  "status": "success",
  "token": "new_jwt_token",
  "data": [
    {
      "book_id": 1,
      "title": "Book Title",
      "author_id": 1
    },
    {
      "book_id": 2,
      "title": "Another Book",
      "author_id": 2
    }
  ]
  }
### 4. **Book-Author Relationship Endpoints**
#### Add a Relationship Between a Book and an Author
- **Method**: `POST`
- **Endpoint**: `/books/authors/add'
- **Request Body**:
  ```json
  {
  "book_id": 1,
  "author_id": 1
  }
- **Request Body**:
  ```json
  {
  "status": "success",
  "token": "new_jwt_token",
  "data": null
  }
 #### Get List of Book-Author Relationships
- **Method**: `GET`
- **Endpoint**: `/books/authors'
- **Request Body**:
  ```json
  {
  "status": "success",
  "token": "new_jwt_token",
  "data": [
    {
      "book_id": 1,
      "author_id": 1
    },
    {
      "book_id": 2,
      "author_id": 2
    }
  ]
  }

### 5. **Authorization**
- **Request Body**:
  ```json
  {
  Authorization: Bearer {your_jwt_token}
  }


### How it works:

1. **Headers**: The endpoint title (e.g., `### 1. User Endpoints`) is the section header.
2. **Endpoint Method and URL**: For each endpoint, you describe the HTTP method (e.g., `POST`, `GET`) and the URL (e.g., `/user/register`).
3. **Request Body**: You format the expected request payload using `json` syntax inside code blocks (triple backticks).
4. **Response**: Similarly, provide the expected responses (both success and failure), formatted in JSON inside code blocks.
5. **Authorization**: At the end, provide any required information on how users need to authenticate, such as how to include the JWT in the `Authorization` header.






  


  
