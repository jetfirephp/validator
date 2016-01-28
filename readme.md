## JetFire PHP Validator

A simple php validator inspired by laravel form request validation and [Respect/Validation](https://github.com/Respect/Validation).

### Installation

Via [composer](https://getcomposer.org)

```bash
composer require jetfirephp/validator
```

### Usage

Simple usage of `\JetFire\Validator\Validator` :

```php
require_once __DIR__ . '/vendor/autoload.php';

$response = \JetFire\Validator\Validator::isAlpha('Peter Parker');

// if not valid
if(!$response){
    // your code
    // ...
}
```

If you want to validate multiple values you can do it like this :

```php
require_once __DIR__ . '/vendor/autoload.php';

$response = \JetFire\Validator\Validator::validate([
    'Peter Parker 1'                  => 'alpha|length:<60',
    '20'                              => 'int|max:40|min:10',
    'peter.parker@spiderman.com'      => 'email|noWhitespace',
    '+347123456789'                   => 'phone',
    '11375'                           => 'postalCode:US'
]);
if($response['valid']){
    // Continue your code
    // ...
}else{
    // $response['message'] return an array of messages for each value and rule
    // $response['message']['_field_']['_rule_']
    // for example the first validation will return an error because 'Petet Parker 1' doesn't contain only letters
    // to get the return message : $response['message']['Peter Parker 1']['alpha']
}
```

As you can see it's not very nice to set the value as a key.
If you want to add a key for your values you have to add your key before the value, like this :

```php
$response = \JetFire\Validator\Validator::validate([
    'name::Peter Parker 1'                    => 'alpha|length:<60',
    'age::20'                                 => 'int|max:40|min:10',
    'email::peter.parker@spiderman.com'       => 'email|noWhitespace',
    'phone::+347123456789'                    => 'phone',
    'code::11375'                             => 'postalCode:US'
]);
// You can get now the error message for 'Peter Parker 1' like this $response['message']['name']['alpha']
```

### $_POST & $_GET validation

For `$_POST` validation you have to specify the input name like this :

```php
$response = \JetFire\Validator\Validator::validatePost([
    'firstName|lastName'               => 'alpha|length:<60',
    'age'                              => 'int|max:40|min:10',
    'email'                            => 'email|noWhitespace',
    'phone'                            => 'phone',
    'postal_code'                      => 'postalCode:US'
]);
```

For `$_GET` validation you have to use `\JetFire\Validator\Validator::validateGet`.

### Custom Message

You can set your custom messages for validation. You have to pass an array in second argument of `\JetFire\Validator\Validator::validate` :

```php
$response = \JetFire\Validator\Validator::validatePost([
    'firstName|lastName'               => 'alpha|length:<60',
],[
    'alpha:firstName'                  => 'FirstName must contain only letters', // only applied for firstName not for lastName
    'length'                           => ':field must not exceed 60 characters', // :field is replaced by field name (firstName or lastName) 
]);
```

### Rules

Here are the list of available rules :

#### regex

Evaluates a regex on the input and validates if matches

```php
    'Peter Parker' => 'regex:/[a-zA-Z]/', // true
```
#### alpha

Validates characters from a-Z

```php
    'Peter Parker' => 'alpha', // true
    'Peter - Parker' => 'alpha', // false
```
#### alnum

Validates alphanumeric characters from a-Z and 0-9.

```php
    'Peter Parker 20' => 'alnum', // true
    'Peter Parker - 20' => 'alnum', // false
```

#### string

Validates if the input is an string.

```php
    // for $_POST and $_GET validation
    // type string
    'firstName' => 'string', // true
```

#### int

Validates if the input is an integer.

```php
    // for $_POST and $_GET validation
    // type int
    'age' => 'int', // true
```

#### numeric

Validates on any numeric value.

```php
    '20' => 'numeric', // true
    '-20' => 'numeric', // true
    '2.0' => 'numeric', // true
```

#### max & min

Validates if the input doesn't exceed the maximum value or if is greater than the minimum value.

```php
    '20' => 'max:30|min:10', // true
    '-20' => 'max:1|min:-30', // true
```

#### url

Validates if input is an URL:

```php
    'http://example.com' => 'url', // true
    'https://www.youtube.com/watch?v=6FOUqQt3Kg0' => 'url', // true
```

#### boolean

Validates if the input is a boolean value:

```php
    '1' => 'boolean', // true
    'true' => 'boolean', // true
```

#### date

Validates if input is a date:

```php
    '20-01-2016' => 'date:d-m-y', // true
    '2016-01-20' => 'date:y-m-d', // true
```


#### lowercase

Validates if string characters are lowercase in the input:

```php
    'peter parker 20' => 'lowercase', // true
```

#### uppercase

Validates if string characters are uppercase in the input:

```php
    'PETER PARKER' => 'uppercase', // true
```

#### noWhitespace

Validates if a string contains no whitespace (spaces, tabs and line breaks);

```php
    'Peter Parker' => 'noWhitespace', // false
    'Peter-Parker' => 'noWhitespace', // true
```

#### email

Validates an email address.

```php
    'peter.parker@spiderman.com' => 'email', // true
```

#### phone

Validates a valid 7, 10, 11 digit phone number (North America, Europe and most Asian and Middle East countries), supporting country and area codes (in dot, space or dashed notations) such as:

```php
    '(555)555-5555' => 'phone', // true
    // other phone numbers accepted
    (555)555-5555
    555 555 5555
    +5(555)555.5555
    33(1)22 22 22 22
    +33(1)22 22 22 22
    +33(020)7777 7777
    03-6106666
```

#### postalCode

Validates a postal code according to the given country code.

```php
   '75000' => 'postalCode:FR', // true
   '02179000' => 'postalCode:BR', // true
   '02179-000' => 'postalCode:BR', // true
   '02179-000' => 'postalCode:US', // false
   '55372' => 'postalCode:US', // true
```

#### equal

Validates if the input is equal to some value.

```php
    'Peter Parker' => 'equal:Peter Parker', // true
    // you can pass php function to check a value like this :
    'password' => 'equal:password_verify,your_password', 
    'password' => 'equal:md5,your_password', 
```

#### values

Validates if the input contain one of the following values. 

```php
    'Peter' => 'values:Peter,Parker,Spiderman', // true
    'Peter' => 'values:Parker,Spiderman', // false
```

#### same

Validates if the input is equal to another input. 

```php
    // for $_POST and $_GET validation
    // firstName = Peter
    'firstName' => 'alpha', // true 
    // lastName = Peter 
    'lastName' => 'same:firstName', // true
```

#### length

Validates lengths.

```php
    'Peter' => 'length:5', // true
    'Peter' => 'length:>4', // true
    'Peter' => 'length:<6', // true
    'Peter' => 'length:4,5', // true
```

#### image

Validates if the file is a valid image by checking its MIME type.

```php
    'image.jpg' => 'image', // true
    // other supported MIME ['png', 'jpeg', 'gif', 'svg', 'bmp']
```

All the validations above must return false if the input is not a valid file or of the MIME doesn't match with the file extension.

#### mimes

Validates if the file extension matches the expected one:

```php
    'image.jpg' => 'mimes:jpg', // true
```

#### size

Validates file sizes:

```php
    // size in octet
    'image.jpg' => 'size:10000', // true
    'image.jpg' => 'size:<10000', // true
    'image.jpg' => 'size:>10000', // true
    'image.jpg' => 'size:10000,20000', // true
```

#### height & width

Validates image height or width:

```php
    // height and width in px
    'image.jpg' => 'height:200|width:200', // true
    'image.jpg' => 'height:<200|width:<200', // true
    'image.jpg' => 'height:>200|width:>200', // true
    'image.jpg' => 'height:200,300|width:200,300', // true
```

#### set

Validates if the input is set.

```php
    // for $_POST & $_GET
    'firstName => 'set', // true
    // php : if(isset($_POST['firstName'])) or if(isset($_GET['firstName'])) 
```

#### required 

Check if the input is set and not empty

```php
    // for $_POST & $_GET
    'firstName => 'required', // true
```

#### requiredIf
 
The field is required if it valid some condition

```php
    // for $_POST & $_GET
    'firstName' => 'required', // true
    
    // lastName is required if value1 is equal to value2
    'lastName' => 'requiredIf:value1,value2', 
    
    // lastName is required if firstName is set and not empty
    'lastName => 'requiredIf:field,firstName', 
    
    // lastName is required if firstName is set and empty
    'lastName' => 'requiredIf:empty_field,firstName', 
    
    // lastName is required if firstName is set
    'lastName' => 'requiredIf:field_set,firstName', 
    
    // lastName is required if firstName is not set
    'lastName' => 'requiredIf:field_not_set,firstName', 
    
    // lastName is required if firstName value is equal to Peter
    'lastName' => 'requiredIf:field_value,firstName,Peter', 
    
    // lastName is required if firstName value is not equal to Peter
    'lastName' => 'requiredIf:field_value_not,firstName,Peter', 
       
```

#### requiredWith

The input is required with other inputs

```php
    'firstName1::Peter|lastName1::Parker' => '', 
    'firstName2::|lastName2::Parker' => '', 
    
    // age is required with firstName and lastName
    'age1::20' => 'requiredWith:firstName1,lastName1',  // true
    'age2::20' => 'requiredWith:firstName2,lastName2',  // false firstName1 is empty
    'age3::' => 'requiredWith:firstName1,lastName1',  // false age is empty
```

#### requiredOneOf

The input is required with one of the following inputs

```php
    'firstName1::Peter|lastName1::Parker' => '', 
    'firstName2::Peter|lastName2::' => '', 
    'firstName3::|lastName3::' => '', 
    
    // age is required with one of the following inputs
    'age1::20' => 'requiredOneOf:firstName1,lastName1',  // true
    'age2::20' => 'requiredOneOf:firstName2,lastName2',  // true
    'age3::20' => 'requiredOneOf:firstName3,lastName3',  // false firstName3 or lastName3 must not be empty
    'age4::'   => 'requiredOneOf:firstName1,lastName1',  // false age is required
```

#### with

The input is optional but the followings input must not be empty

```php
    'firstName1::Peter|lastName1::Parker' => '', 
    'firstName2::Peter|lastName2::' => '', 
    
    // age is optional but the followings input must not be empty
    'age1::20' => 'with:firstName1,lastName1', // true
    'age1::'   => 'with:firstName1,lastName1', // true
    'age2::20' => 'with:firstName2,lastName2', // false
```

#### oneOf

The input is optional but one of the following input must not be empty

```php
    'firstName1::Peter|lastName1::Parker' => '', 
    'firstName2::|lastName2::' => '', 
    
    // age is optional but one of the following input must not be empty
    'age1::20' => 'with:firstName1,lastName1', // true
    'age1::'   => 'with:firstName1,lastName1', // true
    'age2::20' => 'with:firstName2,lastName2', // false
```

#### optional

The input is optional and the following rules are not execute if the input is empty

```php
    'firstName::' => 'optional|alpha|length:<20', // true
    'firstName::Peter' => 'optional|alpha|length:<20', // true
    'firstName::Peter 2' => 'optional|alpha|length:<20', // false
```

#### optionalIf

The input is optional if it valid some condition

```php
    'firstName::Peter' => '',
    
    // lastName is optional if value1 is equal to value2
    'lastName' => 'optionalIf:value1,value2', 
        
    // lastName is optional if firstName is set and not empty
    'lastName => 'optionalIf:field,firstName', 
    
    // lastName is optional if firstName is set and empty
    'lastName' => 'optionalIf:empty_field,firstName', 
    
    // lastName is optional if firstName is set
    'lastName' => 'optionalIf:field_set,firstName', 
    
    // lastName is optional if firstName is not set
    'lastName' => 'optionalIf:field_not_set,firstName', 
    
    // lastName is optional if firstName value is equal to Peter
    'lastName' => 'optionalIf:field_value,firstName,Peter', 
    
    // lastName is optional if firstName value is not equal to Peter
    'lastName' => 'optionalIf:field_value_not,firstName,Peter', 
   ```

#### skipIf

Skip the input rules if it valid some condition

```php
    'firstName::Peter' => '',
        
    // skip lastName if value1 is equal to value2
    'lastName' => 'skipIf:value1,value2', 
        
    // skip lastName  if firstName is set and not empty
    'lastName => 'skipIf:field,firstName', 
    
    // skip lastName  if firstName is set and empty
    'lastName' => 'skipIf:empty_field,firstName', 
    
    // skip lastName  if firstName is set
    'lastName' => 'skipIf:field_set,firstName', 
    
    // skip lastName  if firstName is not set
    'lastName' => 'skipIf:field_not_set,firstName', 
    
    // skip lastName  if firstName value is equal to Peter
    'lastName' => 'skipIf:field_value,firstName,Peter', 
    
    // skip lastName  if firstName value is not equal to Peter
    'lastName' => 'skipIf:field_value_not,firstName,Peter', 
```

### Assignation

#### add

```php
     'name1::Peter' => 'add:end,Parker', // name1 = Peter Parker
     'name2::Parker' => 'add:begin,Peter', // name2 = Peter Parker
 ```
 
#### assign

```php
     // you can modify your input value with a crypt function
     'password::Peter' => 'assign:crypt,password_hash', // password = password_hash('Peter', PASSWORD_BCRYPT);
     // you can use other crypt function like md5, sha5 ..
        
     
     // change your input value 
     'name::Parker' => 'assign:Peter', // name = Peter
     // or
     'name::Parker' => 'assign:value,Peter', // name = Peter
     
     // change your input value with another input value
     'firstName::Peter' => '',
     'lastName::Parker' => 'assign:field,firstName', // lastName = Peter
     
     // assign the file name to file input 
     'file' => 'assign:file' 
     
     // assign this input value to another input
     'firstName::Peter' => '',
     'lastName::Parker' => 'assign:this,firstName', // firstName = Parker
           
 ```

### License

The JetFire Validator is released under the MIT public license : http://www.opensource.org/licenses/MIT. 

