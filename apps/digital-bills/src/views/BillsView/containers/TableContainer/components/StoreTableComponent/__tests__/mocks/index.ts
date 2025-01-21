import {
  PageLimitType,
  Platform,
} from '@apps/digital-bills/src/views/BillsView/containers/TableContainer/types';

export const STORE_MOCK = {
  id: '123',
  isActive: true,
  storeInfo: {
    storeCode: '1234',
  },
  name: 'Test Store',
  brand: {
    logo: 'test_brand_logo_url',
  },
  address: {
    displayAddress: 'Store Address',
  },
  platform: 'RETAIL' as Platform,
};

export const STORE_TABLE_MOCK_PROPS = {
  isRefreshing: false,
  totalItemCount: 2,
  defaultPageSize: 10 as PageLimitType,
  changePage: jest.fn(),
  currentPage: 0,
  storesData: [
    {
      id: '123',
      store: STORE_MOCK,
      salesInfo: {
        totalSales: 100,
        averageSales: 200,
      },
      totalTransactions: 300,
      transactionInfo: {
        DIGITAL: 400,
        DIGITAL_PRINT: 500,
        PRINT: 600,
      },
    },
  ],
};
