import { IconComponent } from '@razorpay/blade/components';
import { CurrencyCodeType } from '@razorpay/i18nify-js';
import { PaymentsComponent } from '../types';
import { DateRangeOption } from '../utils';

export interface EarningsBreakup {
  title: string;
  amount: number;
  currency: CurrencyCodeType;
  showComponent: boolean;
}

export interface EarningsAction {
  type: 'navigate-internal' | 'navigate-external' | 'open-modal';
  path: string;
  label: string;
}

export interface EarningsCardProps {
  isLoading: boolean;
  data?: PaymentsComponent;
  dateFilter: DateRangeOption;
}

export interface EarningsItem {
  title: string;
  amount: number;
  currency: CurrencyCodeType;
  icon: IconComponent;
  showComponent: boolean;
  action?: EarningsAction;
  breakup: EarningsBreakup[];
}

// Base interface for common fields
interface BaseEarningsData {
  type: 'locked' | 'growth' | 'earnings';
  lastUpdated: number;
  inputTime: DateRangeOption;
}

// Earnings state with full details
interface EarningsState extends BaseEarningsData {
  type: 'earnings';
  total: number;
  currency: CurrencyCodeType;
  percentageChange: number;
  earnings: EarningsItem[];
}

// Locked state (minimal fields)
interface LockedState extends BaseEarningsData {
  type: 'locked';
}

// Growth state (minimal fields)
interface GrowthState extends BaseEarningsData {
  type: 'growth';
}

// Discriminated union type
export type EarningsData = EarningsState | LockedState | GrowthState;
