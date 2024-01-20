$(document).ready(function() {
    // Fetch cities
    $.ajax({
        url: 'billing.php?action=get_cities',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.cities) {
                var citySelector = $('#city');
                $.each(response.cities, function(index, city) {
                    citySelector.append('<option value="' + index + '">' + city + '</option>');
                });
            }
        },
        error: function(error) {
            console.error('Error fetching cities:', error);
        }
    });

    // Calculate and update totals on discount change
    $('#discount').on('input', function() {
        calculateTotals();
    });

    // Submit form using AJAX
    $('#billingForm').submit(function(event) {
        event.preventDefault();
        $('.billing_results').html('').hide()
        $.ajax({
            url: 'billing.php?action=form_submit',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.order_id) {
                    let html = '<div class="billing_results alert alert-success alert-dismissible show" role="alert" id="successAlert">';
                        html += '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>';
                        html += '<h2>Order info added successfully</h2>';
                        html += 'Order ID #';
                        html += response.order_id;
                        html += '</div>';
                    $('.billing_results').html(html).show();
                    $("#billingForm").get(0).reset();
                    $('.product-row').remove();
                }
            },
            error: function(error) {
                console.error('Error submitting form:', error);
            }
        });
    });
    // Add items
    $("#addRow").on("click", function () {
        // Js Validation
        if (validateProductName($(this).closest('.product-body').find('#productDescription')) 
            && validateQuantity($(this).closest('.product-body').find('#quantity')) 
            && validatePrice($(this).closest('.product-body').find('#price'))
        ) {
            addProductRow($(this).closest('.product-body').find('#productDescription').val(), $(this).closest('.product-body').find('#quantity').val(), $(this).closest('.product-body').find('#price').val());
            $(this).closest('.product-body').find('#productDescription').val('');
            $(this).closest('.product-body').find('#quantity').val('');
            $(this).closest('.product-body').find('#price').val('')
        }
    });
    // Remove items
    $(document).on("click", ".bill-close-icon", function () {
        $(this).closest('tr').remove();
        calculateTotals();
    });

    $('#customerName').on('blur', function() {
        validateCustomerName($(this));
    });
    $('#contactNumber').on('blur', function() {
        validateMobileNumber($(this));
    });
    $('#email').on('blur', function() {
        if (validateEmail($(this))) {
            $.ajax({
                url: 'billing.php?action=validate_email',
                type: 'POST',
                data:{
                    email: $(this).val()
                },
                dataType: 'json',
                success: function(response) {
                    if (response.msg != 0) {
                        $('#email').addClass('is-invalid');
                        $('.email-msg').text(response.msg).show();
                    } else {
                        $('.email-msg').text('').hide();
                        $('#email').removeClass('is-invalid');
                    }
                },
                error: function(error) {
                    console.error('Some error occurred.', error);
                }
            });
        } else {
            $('.email-msg').text('').hide();
        }
    });
});

function addProductRow($name, $qty, $price) {
    var $totoal = parseInt($qty) * ($price); 
    var productRow = '<tr class="product-row"><td>' +
                        '<i class="material-icons bill-close-icon">close</i>' +
                        '</td>' +
                        '<td>' + $name +
                        '<input type="hidden" name="productDesc[]" value="'+$name+'">' +
                        '</td>' +
                        '<td>' + $qty +
                        '<input type="hidden" class="form-control" name="qty[]" value="'+$qty+'" >' +
                        '</td>' +
                        '<td>' + $price +
                        '<input type="hidden" name="priceAmount[]" value="'+$price+'">' +
                        '<td>' + $totoal +
                        '<input type="hidden" class="form-control" name="total[]" value="'+$totoal.toFixed(2)+'" >' +
                        '</td>' +
                        '</td></tr>';
    
    $('.product-body').after(productRow);
    calculateTotals();
}

function calculateTotals() {
    let subTotal = 0;

    $('[name^="priceAmount"]').each(function(index, element) {
        let quantity = $('[name^="qty"]').eq(index).val();
        let price = $(element).val();
        subTotal += quantity * price;
    });

    let discountedAmount = subTotal - $('#discount').val();
    let gstAmount = (discountedAmount * 0.18).toFixed(2); // Assuming GST is 18%
    let grandTotal = parseFloat(discountedAmount) + parseFloat(gstAmount);

    $('#subTotal').val(subTotal.toFixed(2));
    $('#gstAmount').val(gstAmount);
    $('#grandTotal').val(grandTotal.toFixed(2));
}

function validateProductName(element) {
    let productName = element.val();
    productName = productName.trim();
    let isValidProductName = /[a-zA-Z]/.test(productName) || /\d/.test(productName);

    if (!isValidProductName) {
        element.addClass('is-invalid');
        return false;
    } else {
        element.removeClass('is-invalid');
        return true;
    }
}

function validateQuantity(element) {
    let quantity = element.val();
    quantity = quantity.trim();
    // Check if the input is a positive integer
    let isPositiveInteger = /^\d+$/.test(quantity) && parseInt(quantity) > 0;
    if (!isPositiveInteger) {
        element.addClass('is-invalid');
        return false;
    } else {
        element.removeClass('is-invalid');
        return true;
    }
}

function validatePrice(element) {
    let price = element.val();

    if (isNaN(price) || price <= 0) {
        element.addClass('is-invalid');
        return false;
    } else {
        element.removeClass('is-invalid');
        return true;
    }
}

function validateCustomerName(element) {
    let name = element.val();
    name = name.trim();

    // Check if the input contains only alphabets and spaces
    let isValidName = /^[a-zA-Z\s]+$/.test(name);
    if (!isValidName) {
        element.addClass('is-invalid');
        return false;
    } else {
        element.removeClass('is-invalid');
        return true;
    }
}

function validateEmail(element) {
    let email = element.val();
    email = email.trim();

    // Regular expression for basic email validation
    let emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    // Check if the input matches the email pattern
    let isValidEmail = emailRegex.test(email);

    if (!isValidEmail) {
        element.addClass('is-invalid');
        return false;
    } else {
        element.removeClass('is-invalid');
        return true;
    }
}

function validateMobileNumber(element) {
    let mobileNumber = element.val();
    // Remove non-numeric characters
    let numericMobileNumber = mobileNumber.replace(/\D/g, '');
    // Check if the numeric mobile number has exactly 10 digits
    let isValidMobileNumber = /^\d{10}$/.test(numericMobileNumber);

    if (!isValidMobileNumber) {
        element.addClass('is-invalid');
        return false;
    } else {
        element.removeClass('is-invalid');
        return true;
    }
}
