import * as Yup from 'yup';

//components
import Instruments from './Instruments';
import Details from './Details';
import Ownership from './Ownership';

//constants
export const INSTRUMENTS = 'instruments';
export const MERCHANT_INFO = 'merchant_info';
export const OWNER_DETAILS = 'owner_details';

export const INSTRUMENT_LIST = [
  {
    key: 'trustly',
    description: 'Accept EUR and GBP transfers',
  },
  {
    key: 'poli',
    description: 'Accept AUD transfers',
  },
  {
    key: 'giropay',
    description: 'Accept EUR transfers',
  },
  {
    key: 'sofort',
    description: 'Accept EUR transfers',
  },
];

export const SPECIAL_PURPOSE_CODES = ['P0103', 'P0807'];

export const regex = {
  num: /^\d+$/,
  registrationNumber: /^[ulUL][0-9]{5}[A-Za-z]{2}[0-9]{4}[A-Za-z]{3}[0-9]{6}$/,
  passportNumber: /^[A-Za-z]{1}[0-9]{7}$/,
  aadhaarNumber: /^[2-9]{1}[0-9]{3}\s{0,1}[0-9]{4}\s{0,1}[0-9]{4}$/,
  panNumber: /^[A-Za-z]{5}[0-9]{4}[A-Za-z]{1}$/,
  numDecimal: /^(?:100|\d{1,2})(?:\.\d{1,2})?$/,
};

//constants for analytics
export const apmOnboarding = 'apm onboarding';
export const apmPopup = `${apmOnboarding} popup`;
export const apmPopupButton = `${apmOnboarding} popup button`;
export const apmSaveData = `${apmOnboarding} save data`;
export const apmInstruments = `${apmOnboarding} instruments`;

//form initial values
export const instrumentFormValues = {
  [INSTRUMENTS]: [],
};

export const detailsFormValues = {
  service_offered: '',
  physical_delivery: '',
  average_delivery_in_days: '',
  purpose_code: '',
  iec_code: '',
  address_line1: '',
  address_line2: '',
  city: '',
  state: '',
  zipcode: '',
  country: '',
  date_of_incorporation: '',
  registration_number: '',
  gst_number: '',
  gst_certificate: '',
};

export const ownershipFormValues = {
  first_name: '',
  last_name: '',
  position: '',
  ownership_percentage: '',
  date_of_birth: '',
  passport_number: '',
  aadhaar_number: '',
  pan_number: '',
  address_line1: '',
  address_line2: '',
  city: '',
  state: '',
  zipcode: '',
  country: '',
  proof_of_ownership: '',
  aadhaar: '',
  passport: '',
  pancard: '',
};

export const formInitialValues = {
  ...instrumentFormValues,
  [MERCHANT_INFO]: { ...detailsFormValues },
  [OWNER_DETAILS]: [...ownershipFormValues],
};

//side navigation tabs
export const tabs = [
  {
    name: 'Instruments / Details',
    description:
      'Select the payment instruments to enable on your checkout. These instruments will be included under the ‘Instant Bank Transfers’ section of the payment methods on checkout.',
    errorMessage: 'Please select atleast one payment method to continue or switch tabs',
    component: Instruments,
    formValues: instrumentFormValues,
    dataKey: INSTRUMENTS,
  },
  {
    name: 'Details Required',
    description:
      'International payments are associated with a higher risk of frauds and chargeback, hence it is governed by strict risk evaluations policies laid down by our banking partners',
    errorMessage: 'Please enter all the required and valid details to continue or switch tabs',
    component: Details,
    formValues: detailsFormValues,
    dataKey: MERCHANT_INFO,
  },
  {
    name: 'Management / Ownership',
    description:
      'International payments are associated with a higher risk of frauds and chargeback, hence it is governed by strict risk evaluations policies laid down by our banking partners',
    errorMessage:
      'Please enter all the required and valid details to add owner or submit the form or switch tabs',
    component: Ownership,
    formValues: ownershipFormValues,
    dataKey: OWNER_DETAILS,
  },
];

//form fields
export const detailsFormFields = [
  {
    key: 'service_offered',
    label: 'Product/Service offered on website',
    placeholder: 'Electronics',
    type: null,
  },
  {
    key: 'physical_delivery',
    label: 'Physical delivery',
    options: [
      { label: '--Select--', name: '' },
      { label: 'Yes', name: 'yes' },
      { label: 'No', name: 'no' },
    ],
    type: 'Select',
  },
  {
    key: 'average_delivery_in_days',
    label: 'Average Delivery Timeframe (in days)',
    placeholder: '12',
    type: null,
  },
  {
    key: 'purpose_code',
    label: 'Purpose Code',
    placeholder: '--Select--',
    options: [],
    type: 'ReactPowerSelect',
  },
  {
    key: 'iec_code',
    label: 'IEC Code',
    placeholder: '123GE73GE73R',
    type: null,
  },
  {
    key: 'address_line1',
    label: 'Registered Business Address',
    placeholder: 'Line 1',
    type: null,
  },
  {
    key: 'address_line2',
    label: ' ',
    placeholder: 'Line 2',
    type: null,
  },
  {
    key: 'zipcode',
    label: ' ',
    placeholder: 'Zipcode',
    type: null,
  },
  {
    key: 'city',
    label: ' ',
    placeholder: 'City',
    type: null,
  },
  {
    key: 'state',
    label: ' ',
    placeholder: 'State',
    type: null,
  },
  {
    key: 'country',
    label: ' ',
    placeholder: 'Country',
    type: null,
  },
  {
    key: 'date_of_incorporation',
    label: 'Date of incorporation of the company',
    placeholder: 'YYYY-MM-DD',
    type: 'ToCalendar',
  },
  {
    key: 'registration_number',
    label: 'Registration Number',
    placeholder: 'U72200TN2013PTC123456',
    type: null,
  },
  {
    key: 'gst_number',
    label: 'GST Number',
    placeholder: '29GGGGG1315R9Z6',
    type: null,
  },
  {
    key: 'gst_certificate',
    label: 'GST Certificate',
    accept: ['png', 'jpg', 'pdf'],
    type: 'File',
  },
];

export const ownershipFormFields = [
  {
    key: 'first_name',
    label: 'First Name',
    placeholder: 'Ramesh',
    type: null,
  },
  {
    key: 'last_name',
    label: 'Last Name',
    placeholder: 'Kumar',
    type: null,
  },
  {
    key: 'position',
    label: 'Position',
    placeholder: 'Chief Executive Officer',
    type: null,
  },
  {
    key: 'ownership_percentage',
    label: '% Ownership',
    placeholder: '70',
    type: null,
  },
  {
    key: 'date_of_birth',
    label: 'Date of Birth',
    placeholder: 'YYYY-MM-DD',
    type: 'ToCalendar',
  },
  {
    key: 'passport_number',
    label: 'Passport Number',
    placeholder: 'J8259354',
    type: null,
  },
  {
    key: 'aadhaar_number',
    label: 'Aadhaar Number',
    placeholder: '12341234512345',
    type: null,
  },
  {
    key: 'pan_number',
    label: 'PAN Card Number',
    placeholder: 'ABCTY1234D',
    type: null,
  },
  {
    key: 'address_line1',
    label: 'Current Home Address',
    placeholder: 'Line 1',
    type: null,
  },
  {
    key: 'address_line2',
    label: ' ',
    placeholder: 'Line 2',
    type: null,
  },
  {
    key: 'zipcode',
    label: ' ',
    placeholder: 'Zipcode',
    type: null,
  },
  {
    key: 'city',
    label: ' ',
    placeholder: 'City',
    type: null,
  },
  {
    key: 'state',
    label: ' ',
    placeholder: 'State',
    type: null,
  },
  {
    key: 'country',
    label: ' ',
    placeholder: 'Country',
    type: null,
  },
  {
    key: 'proof_of_ownership',
    label: 'Proof of Ownership',
    accept: ['png', 'jpg', 'pdf'],
    type: 'File',
  },
  {
    key: 'aadhaar',
    label: 'Aadhaar',
    accept: ['png', 'jpg', 'pdf'],
    type: 'File',
  },
  {
    key: 'passport',
    label: 'Passport',
    accept: ['png', 'jpg', 'pdf'],
    type: 'File',
  },
  {
    key: 'pancard',
    label: 'Pan Card',
    accept: ['png', 'jpg', 'pdf'],
    type: 'File',
  },
];

//form validation
export const instrumentFormSchema = Yup.object().shape({
  instruments: Yup.array().min(1, 'This is a required field.'),
});

export const detailsFormSchema = (isSpecialPurposecode) =>
  Yup.object().shape({
    merchant_info: Yup.object().shape({
      service_offered: Yup.string().required('This is a required field.'),
      physical_delivery: Yup.string().required('This is a required field.'),
      average_delivery_in_days: Yup.string()
        .matches(regex.num, 'The field should have digits only')
        .required('This is a required field.'),
      purpose_code: Yup.string().required('This is a required field.'),
      address_line1: Yup.string().required('This is a required field.'),
      address_line2: Yup.string().required('This is a required field.'),
      city: Yup.string().required('This is a required field.'),
      state: Yup.string().required('This is a required field.'),
      zipcode: Yup.string()
        .max(15, 'This field cannot be more than 15 characters')
        .required('This is a required field.'),
      country: Yup.string().required('This is a required field.'),
      date_of_incorporation: Yup.string().required('This is a required field.'),
      registration_number: Yup.string()
        .matches(regex.registrationNumber, 'This field is not in a valid format')
        .required('This is a required field.'),
      gst_number: Yup.string()
        .min(15, 'This field must be exactly 15 character long')
        .max(15, 'This field must be exactly 15 character long')
        .required('This is a required field.'),
      gst_certificate: Yup.string().required('This is a required field.'),
      iec_code: (() => {
        let iec_code = Yup.string();
        if (isSpecialPurposecode) {
          iec_code = iec_code
            .max(20, 'This field must not be more than 20 character long')
            .required('This is a required field.');
        }
        return iec_code;
      })(),
    }),
  });

export const ownershipFormSchema = Yup.object().shape({
  owner_details: Yup.array().of(
    Yup.object().shape({
      first_name: Yup.string().required('This is a required field.'),
      last_name: Yup.string().required('This is a required field.'),
      position: Yup.string()
        .max(45, 'This field cannot be more than 45 characters')
        .required('This is a required field.'),
      ownership_percentage: Yup.string()
        .matches(regex.numDecimal, 'The field should have digits only')
        .required('This is a required field.'),
      date_of_birth: Yup.string().required('This is a required field.'),
      passport_number: Yup.string()
        .matches(regex.passportNumber, 'This field is not in a valid format')
        .required('This is a required field.'),
      aadhaar_number: Yup.string()
        .matches(regex.aadhaarNumber, 'This field is not in a valid format')
        .required('This is a required field.'),
      pan_number: Yup.string()
        .matches(regex.panNumber, 'This field is not in a valid format')
        .required('This is a required field.'),
      address_line1: Yup.string().required('This is a required field.'),
      address_line2: Yup.string().required('This is a required field.'),
      city: Yup.string().required('This is a required field.'),
      state: Yup.string().required('This is a required field.'),
      zipcode: Yup.string()
        .max(15, 'This field cannot be more than 15 characters')
        .required('This is a required field.'),
      country: Yup.string().required('This is a required field.'),
      proof_of_ownership: Yup.string().required('This is a required field.'),
      aadhaar: Yup.string().required('This is a required field.'),
      passport: Yup.string().required('This is a required field.'),
      pancard: Yup.string().required('This is a required field.'),
    }),
  ),
});
