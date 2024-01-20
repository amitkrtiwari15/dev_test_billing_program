-- Table for customer information
CREATE TABLE IF NOT EXISTS customers (
    id_customer INT(11) AUTO_INCREMENT PRIMARY KEY,
    customer_name VARCHAR(255) NOT NULL,
    contact_number VARCHAR(10) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    city int(11) NOT NULL
);

-- Table for billing summary
CREATE TABLE IF NOT EXISTS billing_summary (
    id_billing_summary INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    order_id VARCHAR(20) NOT NULL,
    discount DECIMAL(10, 2) NOT NULL,
    gst DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (customer_id) REFERENCES customers(id_customer)
);
-- Table for billing products
CREATE TABLE IF NOT EXISTS billing_products (
    id_billing_products INT AUTO_INCREMENT PRIMARY KEY,
    billing_id INT NOT NULL,
    product_description TEXT,
    quantity INT NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (billing_id) REFERENCES billing_summary(id_billing_summary)
);
-- Table for cities
CREATE TABLE IF NOT EXISTS cities (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    city_name VARCHAR(255) NOT NULL
);
-- Insert some values on cities
INSERT INTO cities (city_name) VALUES
    ('Noida'),
    ('Lucknow'),
    ('Delhi'),
    ('Kanpur'),
    ('Hyderabad');
