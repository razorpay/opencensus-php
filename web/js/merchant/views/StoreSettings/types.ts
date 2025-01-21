import { TERMINAL_BIT_OPTIONS } from './common/constants';

type TerminalBitOptions = keyof typeof TERMINAL_BIT_OPTIONS;

type TerminalInfo = {
  licenseKey: string;
  macAddress: string;
  ipAddress: string;
  version: string;
  bit: TerminalBitOptions;
};

type TerminalDates = {
  updatedAt: string;
};

type TerminalTransactionDates = {
  lastTransactionAt: string;
};

export type Terminal = {
  id: string;
  store: Store;
  isActive: boolean;
  name: string;
  terminalInfo: TerminalInfo;
  dates: TerminalDates;
  transactionDates: TerminalTransactionDates;
};

export type CustomField = {
  title: string;
  value: string;
};
export type Store = {
  id: string;
  name: string;
  address: Address;
  brand: Brand;
  storeInfo: StoreInfo;
  business: Business;
  isActive: boolean;
  registeredFrom: string;
  dates: Dates;
  platform: string;
  contact: Contact;
  customFields: CustomField[];
};

export type Address = {
  displayAddress: string;
  city: string;
  country: string;
  zipcode: string;
  line1: string;
  state: string;
};

export type Brand = {
  id: string;
  name: string;
};

export type StoreInfo = {
  storeCode: string;
  storeInCharge: string;
  storeType: string;
  linkedProducts: string[];
  email: string;
  websiteUrl: string;
};

export type Business = {
  fssaiLicNumber: string;
  gstNumber: string;
  cinNumber: string;
};

export type Dates = {
  createdAt: string;
  deletedAt: string | null;
  updatedAt: string;
};

export type Phone = {
  countryCode: string;
  number: string;
};

export type Contact = {
  primary: Phone;
  secondary: Phone;
};
