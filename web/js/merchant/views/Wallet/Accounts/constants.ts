import type { BadgeProps } from '@razorpay/blade/components';

export const STATUS_BADGE_PROPS: Record<string, BadgeProps> = {
  active: {
    emphasis: 'intense',
    color: 'positive',
    children: 'Active',
  },
  inactive: {
    emphasis: 'intense',
    color: 'negative',
    children: 'Inactive',
  },
  pending_activation: {
    emphasis: 'intense',
    color: 'primary',
    children: 'Pending Activation',
  },
};
