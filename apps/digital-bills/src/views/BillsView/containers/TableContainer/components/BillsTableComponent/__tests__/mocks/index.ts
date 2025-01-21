import { STORE_MOCK } from '@apps/digital-bills/src/views/BillsView/containers/TableContainer/components/StoreTableComponent/__tests__/mocks';

import type {
  BillStatus,
  PageLimitType,
  Platform,
  TransactionType,
} from '@apps/digital-bills/src/views/BillsView/containers/TableContainer/types';

export const BRAND_MOCK = {
  id: '123',
  name: 'Test Brand',
  logo: 'test_brand_logo_url',
};

export const BILL_MOCK = {
  id: 'bill_1',
  user: {
    phone: {
      number: '1234567890',
      countryCode: '+91',
    },
    email: 'test.email.razorpay.com',
  },
  dates: {
    createdAt: '2021-08-25T00:00:00Z',
  },
  store: STORE_MOCK,
  brand: BRAND_MOCK,
  category: { id: '123' },
  deliveryReport: {
    sms: [],
    email: [],
    whatsapp: [],
  },
  deliveryStatus: {
    sms: 'DELIVERED' as BillStatus,
    email: 'DELIVERED' as BillStatus,
    whatsapp: 'DELIVERED' as BillStatus,
  },
  platform: 'ECOMMERCE' as Platform,
  invoice: {
    number: 'INV123',
    amount: {
      currency: {
        code: 'INR',
        name: 'Indian Rupee',
      },
      value: 100,
    },
  },
  transactionType: 'DIGITAL' as TransactionType,
  isEcomBill: true,
  legacyEntityId: '123',
};

export const BILLS_TABLE_MOCK_PROPS = {
  editColumnModalProps: {
    isEditColumnOpen: false,
    dismissEditColumn: jest.fn(),
    openEditColumn: jest.fn(),
  },
  deleteModalProps: {
    isDeleteModalOpen: false,
    dismissDeleteModal: jest.fn(),
    openDeleteModal: jest.fn(),
  },
  tableProps: {
    currentPage: 0,
    isRefreshing: false,
    billsData: [BILL_MOCK],
    totalItemCount: 10,
    isTableSelectable: true,
    setIsTableSelectable: jest.fn(),
    selectedBills: [],
    setSelectedBills: jest.fn(),
    changePage: jest.fn(),
    defaultPageSize: 10 as PageLimitType,
    tableColumns: [
      { key: 'billId', name: 'Bill ID' },
      { key: 'billContact', name: 'Contact' },
    ],
  },
};
