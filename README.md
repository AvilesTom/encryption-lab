# Application Layer Encryption
This is an example of how to implement encryption between application and database. 
## Functions
### Creates Customers:
- Name
- Email
- Tax ID: This is the field that we are going to be encrypting before saving it in the database
### Gets a customer:
Search an user/customer based on the id.
### Displays database:
We can see the actual database with the encrypted values saved in it 
## Notes
The secret to ecnrypt is in the .env file, this is just an example and the key is only used for encrypt/decrypt the data that it's going to be stored in the database. 
