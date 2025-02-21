import { PAYMENT_NETWORK_CODE } from './constants/card';

type ValueOf<T> = T[keyof T];

export type cardNetwork = ValueOf<typeof PAYMENT_NETWORK_CODE>;

export type paylaterConfigType = {
  value: string;
  name?: string;
  display_name?: string;
};

export type UpiAppConfiguration = {
  app_name: string;
  code: string;
};
