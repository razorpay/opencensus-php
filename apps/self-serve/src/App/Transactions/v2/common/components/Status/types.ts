import { BadgeProps } from '@razorpay/blade/components';
import { Item } from 'apps/self-serve/src/App/Transactions/v2/Payments/types';

export type StatusProps = {
  variant: BadgeProps['variant'];
  content: string;
  status: Item['status'];
};
