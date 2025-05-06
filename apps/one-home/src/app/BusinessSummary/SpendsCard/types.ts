import { IconComponent } from '@razorpay/blade/components';
import { CurrencyCodeType } from '@razorpay/i18nify-js';
import { SpendsComponent } from '../types';
import { DateRangeOption } from '../utils';

// Base interface for all spendings states
interface BaseSpendingsData {
  type: 'locked' | 'growth' | 'spendings';
  lastUpdated: number;
  inputTime: DateRangeOption;
}

// Locked State (Minimal Fields)
interface LockedState extends BaseSpendingsData {
  type: 'locked';
}

// Growth State (Minimal Fields)
interface GrowthState extends BaseSpendingsData {
  type: 'growth';
}

// Spending Actions (Supports multiple action types)
export interface SpendingsAction {
  type: 'navigate-internal' | 'navigate-external' | 'open-modal';
  path: string;
  url: string;
  label: string;
}

export interface SpendsCardProps {
  isLoading: boolean;
  data?: SpendsComponent;
  dateFilter: DateRangeOption;
}
// Spendings Breakdown
interface SpendingsBreakup {
  title: string;
  amount: number;
  currency: CurrencyCodeType;
  showComponent: boolean;
}

// Spendings Item
export interface SpendingsItem {
  title: string;
  amount: number;
  currency: CurrencyCodeType;
  icon: IconComponent;
  showComponent: boolean;
  action?: SpendingsAction;
  breakup: SpendingsBreakup[];
}

// Spendings State
interface SpendingsState extends BaseSpendingsData {
  type: 'spendings';
  total: number;
  currency: CurrencyCodeType;
  percentageChange: number;
  spendings: SpendingsItem[];
}

// Discriminated Union
export type SpendingsData = SpendingsState | LockedState | GrowthState;
