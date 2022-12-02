export type Plugin = {
  name: string;
  icon?: string;
  integration_guide?: string;
  integration_url?: string;
};

export enum Platform {
  WEBSITE = 'business_website',
  ANDROID = 'playstore_url',
  IOS = 'appstore_url',
}

export enum KeyField {
  ID = 'ID',
  SECRET = 'Secret',
}

export enum Plugins {
  SHOPIFY = 'Shopify',
}

export type MerchantProduct = 'PG' | 'PH';
