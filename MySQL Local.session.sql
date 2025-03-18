CREATE TABLE IF NOT EXISTS user (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL
);

INSERT INTO user (username, email) VALUES 
('john_doe', 'johnexample.com'),
('jane_doe', 'janeexample.com'),
('jim_doe', 'jimexample.com');

SELECT * FROM user;

INSERT INTO user ( username, email)
VALUES (
    'username:varchar',
    'xyz@gmail.com'
  );