// utils
import { object } from 'yup';
///- utils

// fields mapped with API payload
export const FIELDS_MAPPING = {
  CITY: 'city',
  LINE1: 'line1',
  POSTAL_CODE: 'zipcode',
  COUNTRY: 'country',
  STATE: 'state',
  NAME: 'name',
};

export const BUYER_ADDRESS_INITIAL_STATE = {
  [FIELDS_MAPPING.CITY]: '',
  [FIELDS_MAPPING.NAME]: '',
  [FIELDS_MAPPING.LINE1]: '',
  [FIELDS_MAPPING.POSTAL_CODE]: '',
  [FIELDS_MAPPING.COUNTRY]: '',
  [FIELDS_MAPPING.STATE]: '',
};

export const BUYER_ADDRESS_FIELDS_CONFIG: {
  label: string;
  name: string;
  type: string;
  placeholder: string;
  required: boolean;
  alert?: string;
}[] = [];

export const BUYER_ADDRESS_VALIDATION_SCHEMA = object().shape({});

export const ALLOWED_COUNTRIES: string[] = [];
