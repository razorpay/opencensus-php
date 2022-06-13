export const NOTIFICATION_MESSAGES = {
  DELETE_SUCCESSFUL: 'Item deleted successfully',
  DELETE_ERROR: 'Something went wrong, please try again',
  UPLOAD_SUCCESSFUL: 'Upload Successful',
  UPLOAD_ERROR: 'Something went wrong, please try again',
  FETCH_ERROR: 'Unable to fetch',
};

export const VALIDATE = {
  ZIPCODE_REGEX: new RegExp(/^[1-9][0-9]{5}$/i),
  EMAIL_REGEX: new RegExp(/^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}$/i),
  IP_REGEX: new RegExp(/^([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})$/i),
  PHONE_REGEX: new RegExp(/^(\+91)[6-9][0-9]{9}$/i),
};

export const ERRORS = {
  TYPE: 'Please select an appropiate type',
  VALUE: 'Required',
  COMMA: 'Please remove trailing comma',
  LENGTH: {
    EMAIL: 'Please enter only 20 Email ids at a time',
    ZIPCODE: 'Please enter only 20 Zipcodes at a time',
    PHONE: 'Please enter only 20 Phone Numbers at a time',
    IP: 'Please enter only 20 IP Addresses at a time',
  },
  EMAIL_PLURAL:
    ' are not valid email addresses. Please enter email address in a proper format. e.g. abc@gmail.com',
  EMAIL:
    'is not a valid email address. Please enter email address in a proper format. e.g. abc@gmail.com',
  ZIPCODE_PLURAL: ' are not valid zipcodes. Please enter a valid 6 digit zipcode',
  ZIPCODE: 'is not a valid zipcode. Please enter a valid 6 digit zipcode',
  PHONE_PLURAL:
    ' are not valid phone numbers. Please enter valid phone numbers with country code. e.g. +919988776655',
  PHONE:
    'is not a valid phone number. Please enter valid phone numbers with country code. e.g. +919988776655',
  IP_PLURAL: ' are not valid IP addresses. Please enter a valid IP Address, e.g. 123.123.123.123',
  IP: 'is not a valid IP address. Please enter a valid IP Address, e.g. 123.123.123.123',
};

export const ATTRIBUTE_TYPE = {
  ip: 'IP Address',
  zipcode: 'Zipcode',
  email: 'Email',
  phone: 'Phone',
};
