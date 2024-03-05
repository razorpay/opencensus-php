import { BadgeProps } from '@razorpay/blade/components';
import { Payments, Item } from 'apps/self-serve/src/App/Transactions/v2/Payments/types';
import { ListContainerProps } from 'apps/self-serve/src/App/Transactions/v2/common/types';

export type PaymentsTableProps = ListContainerProps<Payments['items']>;

export type StatusProps = {
  variant: BadgeProps['variant'];
  content: string;
  status: Item['status'];
};
