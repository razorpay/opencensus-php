import type { BadgeProps } from '@razorpay/blade/components';

export const STATUS_BADGE_PROPS: Record<string, BadgeProps> = {
  active: {
    contrast: 'high',
    variant: 'positive',
    children: 'Active',
  },
  inactive: {
    contrast: 'high',
    variant: 'negative',
    children: 'Inactive',
  },
  pending_activation: {
    contrast: 'high',
    variant: 'blue',
    children: 'Pending Activation',
  },
};
