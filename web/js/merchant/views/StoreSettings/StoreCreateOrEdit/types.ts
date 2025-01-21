import { Store } from 'merchant/views/StoreSettings/types';

export type StoreUpdateResponse = {
  storeUpdate: {
    store: Store;
  };
};

export type StoreCreateResponse = {
  storeCreate: {
    store: Store;
  };
};

export type BillingTerminal = {
  ipAddress: string;
  id: string;
  name: string;
  macAddress: string;
  isEditing?: boolean;
  tempKey?: string;
  isActive?: boolean;
};

type CustomField = {
  title: string;
  value: string;
};

export type StoreCreatePayload = {
  storeType: string;
  name: string;
  storeCode?: string;
  websiteUrl: string;
  address: {
    displayAddress: string;
    line1: string;
    city: string;
    state: string;
    country: string;
    zipcode: string;
  };
  customFields: CustomField[];
  pinCode: string;
  primaryContact: {
    number: string;
  };
  secondaryContact: {
    number: string;
  };
  storeInCharge: string;
  brandId: string | null;
  linkedProducts: string[];
  storeEmail: string | undefined;
};

export type TerminalFormValues = {
  billingTerminals: BillingTerminal[];
};

export type StoreCreateFormValues = {
  storeType: string;
  storeName: string;
  storeCode?: string;
  websiteUrl: string;
  displayAddress: string;
  city: string;
  state: string;
  country: string;
  address: string;
  pinCode: string;
  customFields: any[];
  business?: {
    fssaiLicNumber: string;
    gstNumber: string;
    cinNumber: string;
  };
  storeContact: {
    emailId?: string;
    primaryContactNumber?: string;
    secondaryContactNumber?: string;
    storeInchargeName?: string;
  };
  digitalBilling: {
    brandId?: string;
    brandName?: string;
  };
  linkedProducts?: string[];
};

export type StoreTerminal = {
  id: string;
  name: string;
  isActive: boolean;
  terminalInfo: {
    ipAddress: string;
    macAddress: string;
    licenseKey: string;
  };
};

export type StoreTerminalCreateVariable = {
  storeId: string;
  name?: string;
  macAddress?: string;
  ipAddress?: string;
  type?: string;
};

export type StoreTerminalCreateResponse = {
  storeTerminalCreate: {
    storeTerminal: StoreTerminal;
    success: boolean;
    message: string;
  };
};

// Since i18 doesnt export the type
export type I18nifyCountryCodeType = 'IN' | 'MY' | 'SG' | 'US';
