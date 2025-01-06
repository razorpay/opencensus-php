export const TERMINAL_MOCK_DATA = [
  {
    id: 'terminal1',
    name: 'Test Terminal',
    terminalInfo: {
      macAddress: '30-48-33-68-3C-F3',
      ipAddress: '149.25.113.141',
    },
  },
];

export const FETCHED_STORE_INFO = {
  id: '123',
  name: 'Test Store',
  address: {
    displayAddress: 'Test Address',
    city: 'Test City',
    country: 'Test Country',
    zipcode: '123456',
    line1: 'Test Line 1',
    state: 'Test State',
  },
  brand: {
    id: 'brand1',
    name: 'Test Brand',
  },
  storeInfo: {
    storeCode: '123',
    storeInCharge: 'Test Incharge',
    storeType: 'Test Type',
    linkedProducts: ['DIGITAL_BILLING'],
    email: 'test.email@razorpay.com',
    websiteUrl: 'https://www.razorpay.com',
  },
  business: {
    fssaiLicNumber: '123',
    gstNumber: '123',
    cinNumber: '123',
  },
  isActive: true,
  registeredFrom: 'PLATFORM',
  dates: {
    createdAt: '2021-07-28T08:00:00Z',
    deletedAt: null,
    updatedAt: '2021-07-28T08:00:00Z',
  },
  platform: 'RETAIL',
  contact: {
    primary: {
      number: '1234567890',
      countryCode: '+91',
    },
    secondary: {
      number: '1234567890',
      countryCode: '+91',
    },
  },
  customFields: [
    { title: 'Test Custom Field label', value: 'Test Custom Field value' },
    { title: '', value: '' },
  ],
};
