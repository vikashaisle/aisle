CREATE DATABASE IF NOT EXISTS user_management;
USE user_management;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL,
    phone VARCHAR(20),
    city VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO users (name, email, phone, city) VALUES
('John Smith','john@example.com','9876543210','New York'),
('Emma Johnson','emma@example.com','9876543211','Chicago'),
('Michael Brown','michael@example.com','9876543212','Dallas'),
('Sophia Davis','sophia@example.com','9876543213','Boston'),
('James Wilson','james@example.com','9876543214','Houston'),
('Olivia Taylor','olivia@example.com','9876543215','Seattle'),
('William Anderson','william@example.com','9876543216','Denver'),
('Ava Thomas','ava@example.com','9876543217','Austin'),
('Benjamin Jackson','ben@example.com','9876543218','Miami'),
('Charlotte White','charlotte@example.com','9876543219','Atlanta'),
('Lucas Harris','lucas@example.com','9876543220','San Diego'),
('Mia Martin','mia@example.com','9876543221','Phoenix'),
('Henry Thompson','henry@example.com','9876543222','Las Vegas'),
('Amelia Garcia','amelia@example.com','9876543223','Portland'),
('Daniel Martinez','daniel@example.com','9876543224','Detroit'),
('Harper Robinson','harper@example.com','9876543225','Orlando'),
('Matthew Clark','matthew@example.com','9876543226','Nashville'),
('Evelyn Lewis','evelyn@example.com','9876543227','Columbus'),
('David Walker','david@example.com','9876543228','Cleveland'),
('Ella Hall','ella@example.com','9876543229','Kansas City'),
('Joseph Allen','joseph@example.com','9876543230','Raleigh'),
('Scarlett Young','scarlett@example.com','9876543231','Charlotte'),
('Andrew King','andrew@example.com','9876543232','Memphis'),
('Grace Scott','grace@example.com','9876543233','Indianapolis'),
('Samuel Green','samuel@example.com','9876543234','Tampa');