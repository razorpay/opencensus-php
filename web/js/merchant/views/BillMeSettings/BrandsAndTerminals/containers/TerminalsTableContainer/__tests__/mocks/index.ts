export const TERMINALS_DATA = [
  {
    id: 'NYJrOwFmMa5r89',
    name: 'P01',
    store: {
      storeInfo: {
        storeType: 'OFFLINE',
        storeCode: '123',
        linkedProducts: ['DIGITAL_BILLING'],
      },
      name: 'Test Name',
    },
    isActive: false,
    terminalInfo: {
      licenseKey: '2keixaslmxacpom2313',
      macAddress: '30-48-33-68-3C-F3',
      ipAddress: '149.25.113.141',
      version: '7.4.1',
      bit: 'BIT_32',
    },
    transactionDates: {
      lastTransactionAt: '2024-02-08T18:29:59.000Z',
    },
    dates: {
      updatedAt: '2024-08-13T18:29:59.000Z',
    },
  },
  {
    id: 'NYJrOwFmMa5r90',
    name: 'P02',
    store: {
      storeInfo: {
        storeType: 'ONLINE',
        storeCode: '234',
        linkedProducts: ['DIGITAL_BILLING'],
      },
      name: 'Test Name 1',
    },
    isActive: true,
    terminalInfo: {
      licenseKey: '2keixaslmxacpom2314',
      macAddress: '30-48-33-68-3C-F5',
      ipAddress: null,
      version: '7.4.2',
      bit: 'BIT_64',
    },
    transactionDates: {
      lastTransactionAt: '2024-02-09T18:29:59.000Z',
    },
    dates: {
      updatedAt: '2024-08-18T18:29:59.000Z',
    },
  },
  {
    id: 'NYJrOwFmMa5r91',
    name: 'P03',
    store: {
      storeInfo: {
        storeType: 'ONLINE',
        storeCode: '235',
        linkedProducts: [],
      },
      name: 'Test Name 1',
    },
    isActive: true,
    terminalInfo: {
      licenseKey: '2keixaslmxacpom2314',
      macAddress: '30-48-33-68-3C-F5',
      ipAddress: null,
      version: '7.4.2',
      bit: 'BIT_64',
    },
    transactionDates: {
      lastTransactionAt: '2024-02-09T18:29:59.000Z',
    },
    dates: {
      updatedAt: '2024-08-18T18:29:59.000Z',
    },
  },
];

export const TERMINALS_LIST_RESPONSE = {
  storeTerminals: {
    storeTerminals: TERMINALS_DATA,
  },
};

export const TERMINALS_FILTER_PAYLOAD = {
  limit: 10,
  offset: 0,
  searchTerm: '',
};
