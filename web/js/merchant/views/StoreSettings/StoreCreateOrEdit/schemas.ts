import * as Yup from 'yup';
import { useStoresCreateStore } from './stores/storesCreateFormStore';

export const StoreCreateSchema = Yup.object().shape({
  storeType: Yup.string().required('Store Type is required'),
  storeName: Yup.string()
    .max(200, 'Store Name cannot be more than 200 characters')
    .required('Store Name is required'),
  storeCode: Yup.string()
    .max(20, 'Store Code cannot be more than 20 characters')
    .required('Store Code is required'),
  websiteUrl: Yup.string().when('storeType', {
    is: (storeType) => storeType === 'ONLINE',
    then: Yup.string().url('Website URL is invalid').required('Website URL is required'),
    otherwise: Yup.string().notRequired(),
  }),
  displayAddress: Yup.string().when('storeType', {
    is: (storeType) => storeType === 'OFFLINE',
    then: Yup.string()
      .max(200, 'Display Address cannot be more than 200 characters')
      .required('Display Address is required'),
    otherwise: Yup.string()
      .max(200, 'Display Address cannot be more than 200 characters')
      .notRequired(),
  }),
  address: Yup.string().when('storeType', {
    is: (storeType) => storeType === 'OFFLINE',
    then: Yup.string()
      .max(200, 'Address cannot be more than 200 characters')
      .required('Address is required'),
    otherwise: Yup.string().max(200, 'Address cannot be more than 200 characters').notRequired(),
  }),
  city: Yup.string().when('storeType', {
    is: (storeType) => storeType === 'OFFLINE',
    then: Yup.string()
      .max(50, 'City cannot be more than 50 characters')
      .test('neglect-check', 'City is required', function (value) {
        const { neglectStateAndCityCheck } = useStoresCreateStore.getState();
        if (neglectStateAndCityCheck) {
          return true;
        }
        return value ? true : this.createError({ message: 'City is required' });
      }),
    otherwise: Yup.string().max(50, 'City cannot be more than 50 characters').notRequired(),
  }),
  state: Yup.string().when('storeType', {
    is: (storeType) => storeType === 'OFFLINE',
    then: Yup.string()
      .max(50, 'State cannot be more than 50 characters')
      .test('neglect-check', 'State is required', function (value) {
        const { neglectStateAndCityCheck } = useStoresCreateStore.getState();
        if (neglectStateAndCityCheck) {
          return true;
        }
        return value ? true : this.createError({ message: 'State is required' });
      }),
    otherwise: Yup.string().max(50, 'State cannot be more than 50 characters').notRequired(),
  }),
  country: Yup.string().when('storeType', {
    is: (storeType) => storeType === 'OFFLINE',
    then: Yup.string()
      .max(50, 'Country cannot be more than 50 characters')
      .required('Country is required'),
    otherwise: Yup.string().max(50, 'Country cannot be more than 50 characters').notRequired(),
  }),
  pinCode: Yup.string().when('storeType', {
    is: (storeType) => storeType === 'OFFLINE',
    then: Yup.string()
      .max(10, 'Pincode cannot be more than 10 characters')
      .required('Pincode is required'),
    otherwise: Yup.string().max(10, 'Pincode cannot be more than 10 characters').notRequired(),
  }),
  storeContact: Yup.object().shape({
    emailId: Yup.string().email('Invalid email address').notRequired(),
    primaryContactNumber: Yup.string()
      .matches(/^(\+?(\d{1,3})?\s?)?((\d{10})|([689]\d{7})|(01\d{8,9}))$/, 'Invalid phone number')
      .notRequired(),
    secondaryContactNumber: Yup.string()
      .matches(/^(\+?(\d{1,3})?\s?)?((\d{10})|([689]\d{7})|(01\d{8,9}))$/, 'Invalid phone number')
      .notRequired(),
  }),
  digitalBilling: Yup.object().when('linkedProducts', {
    is: (linkedProducts) => linkedProducts?.includes('DIGITAL_BILLING'),
    then: Yup.object().shape({
      brandId: Yup.string().required('Brand is required'),
    }),
    otherwise: Yup.object().shape({ brandId: Yup.string().notRequired() }),
  }),

  customFields: Yup.array()
    .of(
      Yup.object().shape({
        title: Yup.string().min(1, 'Title cannot be empty'),
        value: Yup.string().min(1, 'Value cannot be empty'),
      }),
    )
    .test(
      'all-required',
      'All fields must be filled out if there are any custom fields',
      (customFields) => {
        if (!customFields || customFields.length === 0) return true;
        return customFields.every((field) => field!.title && field!.value);
      },
    ),
  linkedProducts: Yup.array().of(Yup.string()).required('Linked Products is required'),
});
