import { CurrencyCodeType } from '@razorpay/i18nify-js';
import { BalanceComponent } from '../types';
import { DateRangeOption } from '../utils';

export interface BalanceCardHeaderProps {
  type?: 'unmasked' | 'masked';
  value: number;
  currency: CurrencyCodeType;
  title: string;
}

export interface BalanceCardBodyProps {
  type?: 'unmasked' | 'masked';
  value: number;
  currency: CurrencyCodeType;
  title: string;
}

export interface BalanceCardProps {
  isLoading: boolean;
  isMobile: boolean;
  data?: BalanceComponent;
  dateFilter: DateRangeOption;
}