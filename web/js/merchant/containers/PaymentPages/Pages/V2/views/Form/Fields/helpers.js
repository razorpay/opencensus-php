import fUnits from './field_units';

/*
* A. Type: text
*    Validation: single line text(string), number, email, phone, url, large text area
*
* B. Type: Select
*     Validation: string
*
* */

export const TYPES = [
  {
    label: 'Text',
    options: [
      fUnits.str,
      fUnits.number,
      fUnits.email,
      fUnits.phone,
      fUnits.url,
      fUnits.textarea,
    ],
  },
  fUnits.dropdown,
];

export default function createShellField(type, validation) {
  return {
    name: '__0__', // Dummy quantum name
    label: '',
    type: 'string', // Default is type string
    require: false,
    description: '',
  };
}

export function createEmailField() {
  return {
    name: 'email',
    required: true,
    title: 'Email',
    ...fUnits.email,
  };
}

export function createPhoneField() {
  return {
    name: 'phone',
    title: 'Phone',
    required: true,
    ...fUnits.phone,
  };
}
