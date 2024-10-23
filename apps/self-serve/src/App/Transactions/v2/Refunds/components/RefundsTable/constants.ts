import { BadgeProps } from '@razorpay/blade/components';
interface RefundStatus {
  variant: BadgeProps['color'];
  content: string;
}

export const getRefundsStatusVariantMap = (
  organizationName = 'Razorpay',
): { [key: string]: RefundStatus } => ({
  processing: {
    variant: 'notice',
    content: `${organizationName} is attempting to complete the refund. It can take upto 3-5 working days`,
  },
  processed: {
    variant: 'positive',
    content: `${organizationName} has completed the refund. After this, bank can take 5-7 working days to credit the amount to customer(s) account`,
  },
  failed: {
    variant: 'negative',
    content: 'Due to customer(s) account error or bank-related issues',
  },
});
