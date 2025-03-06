import type { Transactions } from '@apps/digital-bills/src/views/BillsView/containers/BillsOverviewContainer/types';

export type TransactionType = 'DIGITAL' | 'PRINT' | 'DIGITAL_PRINT' | 'DISCARDED';
export type BillStatus = 'DELIVERED' | 'FAILED' | 'PENDING' | 'NOT_ATTEMPTED' | 'ATTEMPTED';
export type Channel = 'SMS' | 'WHATSAPP' | 'EMAIL';
export type Platform = 'ECOMMERCE' | 'RETAIL';

export type StoreStatus = 'ACTIVE' | 'INACTIVE';

export type BillUserSearchInput = {
  email: string | undefined;
  contact: string | undefined;
};

export type BillSearchInputs = BillUserSearchInput & {
  invoiceNumber: string;
  storeCode: string;
};

export type StoreSearchInput = {
  storeCode: string;
  storeName: string;
};

export type User = {
  email: string;
  phone: {
    countryCode: string | null;
    number: string;
  };
};

export type Brand = {
  name: string;
  logo: string;
  id: string;
};

export type Category = {
  id: string;
};

export type Dates = {
  createdAt: string;
};

export type ChannelReport = {
  attemptedAt: string;
  channel: Channel;
  deliveredAt: string;
  receiver: string;
  createdAt: string;
  status: BillStatus;
};

export type DeliveryReport = {
  sms: ChannelReport[];
  email: ChannelReport[];
  whatsapp: ChannelReport[];
};

export type Currency = {
  code: string;
  name: string | null;
};

export type Amount = {
  currency: Currency;
  value: number;
};

export type Invoice = {
  number: string;
  amount: Amount;
};

type BillVisit = {
  ip: string;
  source: string;
  userAgent: string;
  visitedAt: string;
};

export type DeliveryStatus = {
  sms: BillStatus;
  email: BillStatus;
  whatsapp: BillStatus;
};

export type Store = {
  id: string;
  name: string;
  address: {
    displayAddress: string;
  };
  brand: {
    logo: string;
  };
  storeInfo: {
    storeCode: string;
  };
  platform: Platform;
  isActive: boolean;
};

export type Bill = {
  user: User;
  store: Store;
  brand: Brand;
  category: Category;
  dates: Dates;
  deliveryReport: DeliveryReport;
  deliveryStatus: DeliveryStatus;
  id: string;
  invoice: Invoice;
  transactionType: TransactionType;
  isEcomBill: boolean;
  visits?: BillVisit[];
  platform: Platform;
  legacyEntityId: string;
  signedToken?: string;
};

export type PageLimitType = 10 | 25 | 50;

export type BillsDataResponse = {
  bills: {
    bills: Bill[];
    limit: PageLimitType;
    offset: number;
    total: number;
  };
};

export type StoreAggregation = {
  store: Store;
  salesInfo: {
    totalSales: number;
    averageSales: number;
  };
  totalTransactions: number;
  transactionInfo: Transactions;
  id: string;
};

export type StoreAggregationDataResponse = {
  billStoresAggregation: {
    stores: StoreAggregation[];
    limit: PageLimitType;
    offset: number;
    total: number;
  };
};

export type StoresDataResponse = {
  stores: {
    stores: Store[];
    limit: number;
    offset: number;
    total: number;
  };
};

export type StoreGroup = {
  id: string;
  name: string;
  description: string | null;
  isActive: boolean | null;
  stores: Store[];
  storesCount: number;
};

export type StoreGroupsDataResponse = {
  storeGroups: {
    storeGroups: StoreGroup[];
    limit: number;
    offset: number;
    total: number;
  };
};

export type TitleCellValue = {
  smsDeliveryReport?: ChannelReport;
  emailDeliveryReport?: ChannelReport;
  whatsAppDeliveryReport?: ChannelReport;
  timestamp: string;
};

export type BillsTablePayload = {
  limit: PageLimitType;
  offset: number;
  transactionType: TransactionType[];
  status: BillStatus[];
  fromDate: string | null;
  toDate: string | null;
  minAmount: number | null;
  maxAmount: number | null;
  invoiceNumber: string;
  storeCode: string;
  user: BillUserSearchInput;
  storeIds: string[];
};
