----------------------------------------
# Billing Program
------------------------------------------
### Customer Information
- Customer Name
 **No numbers or special characters**
- Contact No.
**Numbers only**
**10 digits only**
- Email Address
**Unique**
- City
**City Selector**
**Create a list of cities in the database**
**Load data from Database (in JSON format)**
### Billing Summary
- Product Description
**Alphabets and Numbers**
- Qty
**Cannot be less than 1**
- Price
**Numeric only (decimal)**
- “Add” Button
- Add New row of the product
- Show Sub Total 
- Show total GST (18%) on the Sub Total after discount
- Show Grand Total
### Discount
- Numbers only
- Cannot be less than 0 (zero)
### Submit Button
- On Submit, all the data will be saved in the database
- A unique order ID will be generated and displayed with appropriate message
- Note: All input fields are required. Input data should not contain any leading or trailing white 
spaces.
### Please refer to the Doc

---------------------------------------------------------------------------

### Installation :
- Create Database `billing`
- Create table using `config/db.sql` script
- Do server and Db configuration in `config/DbConnection.php`
