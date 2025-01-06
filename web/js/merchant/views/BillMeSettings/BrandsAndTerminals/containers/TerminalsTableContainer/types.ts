import { STORE_TYPE_MAP } from 'merchant/views/BillMeSettings/common/constants';
import {
  TERMINAL_BIT_OPTIONS,
  TERMINAL_STATUS_OPTIONS,
  TERMINALS_SEARCH_BY_FIELDS,
} from 'merchant/views/BillMeSettings/BrandsAndTerminals/containers/TerminalsTableContainer/constants';

export type StoreType = keyof typeof STORE_TYPE_MAP;
export type TerminalStatusOptionsType = keyof typeof TERMINAL_STATUS_OPTIONS;
export type TerminalSearchColumnType = keyof typeof TERMINALS_SEARCH_BY_FIELDS;
export type BillingTerminalsType = 'BILLING';

export type Store = {
  name: string;
  storeInfo: {
    storeCode: string;
    storeType: Exclude<StoreType, 'ALL'>;
    linkedProducts: string[];
  };
};

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

export type TerminalsResponse = {
  storeTerminals: {
    storeTerminals: Terminal[];
    limit: number;
    offset: number;
    total: number;
  };
};
