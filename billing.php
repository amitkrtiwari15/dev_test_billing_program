<?php

require_once 'config/DbConnection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_GET['action']) && $_GET['action'] == 'form_submit') {
    $data = [
        'customerName' => mysqli_real_escape_string($conn, $_POST['customerName']),
        'contactNumber' => $_POST['contactNumber'],
        'email' => $_POST['email'],
        'city' => $_POST['city'],
        'productDescription' => $_POST['productDesc'],
        'quantity' => $_POST['qty'],
        'price' => $_POST['priceAmount'],
        'discount' => $_POST['discount'],
        'gst' => 18,
    ];

    // Server-side validation
    $errors = validateData($data);
    if (empty($errors)) {
        // Process billing and calculate totals
        $orderId = generateOrderNumber();
        $billingId = processBilling($orderId, $data);

        if ($billingId) {
            $totals = calculateTotals($billingId, $data['discount']);
            // Return the order ID, Subtotal, GST, and Grand Total to the AJAX request
            die(json_encode([
                'order_id' => $orderId,  // For order ID
                'subTotal' => $totals['subTotal'],
                'gstAmount' => $totals['gstAmount'],
                'grandTotal' => $totals['grandTotal'],
            ]));
        } else {
            // Unable to process billing
            http_response_code(500);
            echo json_encode(['error' => 'Unable to process billing.']);
        }
    } else {
        // Validation errors
        http_response_code(400);
        die(json_encode(['errors' => $errors]));
    }
} elseif ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['action']) && $_GET['action'] == 'get_cities') {
    $cities = getCities($conn);
    die(json_encode(['cities' => $cities]));
} elseif ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_GET['action']) && $_GET['action'] == 'validate_email') {
    $email = $_POST['email'];
    $error = 0;
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
       $error = "Please enter a valid email address.";
    } else {
        // Check for unique email (adjust the table and column names accordingly)
        $result = $GLOBALS['conn']->query("SELECT * FROM customers WHERE email = '$email'");
        if ($result->num_rows > 0) {
           $error = "Email address is already in use.";
        }
    }
    die(json_encode(['msg' => $error]));
}

/**
 * Validate data
 *
 * @param array $data
 * @return array
 */
function validateData($data) {
    $errors = [];

    // Validate customer name (No numbers or special characters)
    if (!preg_match('/^[a-zA-Z\s]+$/', $data['customerName'])) {
        $errors['customerName'] = "Only alphabets and spaces are allowed.";
    }

    // Validate contact number (Numbers only, 10 digits)
    if (!preg_match('/^[0-9]{10}$/', $data['contactNumber'])) {
        $errors['contactNumber'] = "Please enter a valid 10-digit contact number.";
    }

    // Validate email (Unique)
    $email = $data['email'];
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Please enter a valid email address.";
    } else {
        // Check for unique email (adjust the table and column names accordingly)
        $result = $GLOBALS['conn']->query("SELECT * FROM customers WHERE email = '$email'");
        if ($result->num_rows > 0) {
            $errors['email'] = "Email address is already in use.";
        }
    }

    // Validate city
    if (empty($data['city'])) {
        $errors['city'] = "Please select a city.";
    }

    // Validate product quantity (Cannot be less than 1)
    foreach ($data['quantity'] as $quantity) {
        if ($quantity < 1) {
            $errors['quantity'] = "Quantity must be at least 1.";
            break;
        }
    }

    // Validate product price (Numeric only)
    foreach ($data['price'] as $price) {
        if (!is_numeric($price) || $price < 0) {
            $errors['price'] = "Please enter a valid numeric price.";
            break;
        }
    }

    // Validate discount (Numbers only, Cannot be less than 0)
    if (!is_numeric($data['discount']) || $data['discount'] < 0) {
        $errors['discount'] = "Discount must be a valid number and cannot be less than 0.";
    }

    return $errors;
}

/**
 * Get cities
 *
 * @param object $conn
 * @return array
 */
function getCities($conn) {
    // Fetch cities from the 'cities' table
    $getCitiesQuery = "SELECT id, city_name FROM cities";
    $result = mysqli_query($conn, $getCitiesQuery);

    $cities = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $cities[$row['id']] = $row['city_name'];
    }

    // Return fetched cities
    return $cities;
}

/**
 * Process billing data
 *
 * @param int $idOrder
 * @param array $data
 * @return bool
 */
function processBilling($idOrder, $data) {
    $stmt = $GLOBALS['conn']->prepare("INSERT INTO customers (customer_name,contact_number,email,city) VALUES (?,?,?,?)");
    $stmt->bind_param("sssd", $data['customerName'],$data['contactNumber'],$data['email'],$data['city']);
    if ($stmt->execute()) {
        $customerId = $GLOBALS['conn']->insert_id;
        if ($customerId) {
            return processOrder($customerId, $idOrder, $data);
        }
    } else {
        return false;
    }
}

/**
 * Create Order
 *
 * @param int $idCustomer
 * @param int $idCustomer
 * @param array $data
 * @return bool
 */
function processOrder($idCustomer, $idOrder, $data) {
    $stmt = $GLOBALS['conn']->prepare("INSERT INTO billing_summary (customer_id,order_id,discount,gst) VALUES (?,?,?,?)");
    $stmt->bind_param("isdd", $idCustomer, $idOrder, $data['discount'], $data['gst']);
    if ($stmt->execute()) {
        $orderId = $GLOBALS['conn']->insert_id;
        return insertBillingSummary($orderId, $data);
    } else {
        return false;
    }
}

/**
 * Add billing summary
 *
 * @param int $orderId
 * @param array $data
 * @return int|bool
 */
function insertBillingSummary($orderId, $data) {
    $stmt = $GLOBALS['conn']->prepare("INSERT INTO billing_products (billing_id, product_description, quantity, price) VALUES (?, ?, ?, ?)");
    foreach ($data['productDescription'] as $key => $productDescription) {
        $quantity = $data['quantity'][$key];
        $price = $data['price'][$key];

        $stmt->bind_param("isid", $orderId, $productDescription, $quantity, $price);

        if (!$stmt->execute()) {
            return false;  // Unable to insert billing summary data
        }
    }

    return $orderId;
}

/**
 * Calculate Total
 *
 * @param int $billingId
 * @param float $discountAmount
 * @return array
 */
function calculateTotals($billingId, $discountAmount) {
    $result = $GLOBALS['conn']->query("SELECT quantity, price FROM billing_products WHERE billing_id = '$billingId'");
    $totalAmount = 0;

    while ($row = $result->fetch_assoc()) {
        $totalAmount += $row['quantity'] * $row['price'];
    }

    $subTotal = $totalAmount - $discountAmount;
    $gstAmount = $subTotal * 0.18;  // Assuming GST is 18%
    $grandTotal = $subTotal + $gstAmount;

    return [
        'subTotal' => $subTotal,
        'gstAmount' => $gstAmount,
        'grandTotal' => $grandTotal,
    ];
}

function generateOrderNumber() {
    $prefix = substr(uniqid(), -5);
    $suffix = rand(10000, 99999);

    $orderNumber = strtoupper($prefix . $suffix);

    return $orderNumber;
}