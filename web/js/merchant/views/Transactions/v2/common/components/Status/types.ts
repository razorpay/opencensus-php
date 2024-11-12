import { BadgeProps } from '@razorpay/blade/components';
import { User } from 'common/typings';
import { Item } from 'merchant/views/Transactions/v2/Payments/types';

export type StatusProps = {
  variant: BadgeProps['color'];
  content: string;
  status: Item['status'];
  isFailedIconEnabled?: boolean;
  gatewayData?: Item['gateway_data'];
  user: User;
};
