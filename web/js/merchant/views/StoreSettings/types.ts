export type CustomField = {
  title: string;
  value: string;
};

export type ContactType = {
  number: string;
  countryCode: string;
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
  deletedAt: any;
  updatedAt: string;
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
  contact: {
    primary: ContactType;
    secondary: ContactType;
  };
  customFields: CustomField[];
};
