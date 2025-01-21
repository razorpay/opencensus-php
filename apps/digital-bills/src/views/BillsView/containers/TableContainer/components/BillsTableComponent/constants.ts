import { MessageSquareIcon, MessageCircleIcon, MailIcon } from '@razorpay/blade/components';

export const SOURCE_TYPE_MAPPER = {
  RETAIL: {
    Name: 'Retail',
    Variant: 'positive',
  },
  ECOMMERCE: {
    Name: 'E-Commerce',
    Variant: 'information',
  },
} as const;

export const CHANNEL_ICON_MAP = {
  sms: MessageSquareIcon,
  email: MailIcon,
  whatsapp: MessageCircleIcon,
};

export const STATUS_TEXT_MAP = {
  DELIVERED: 'Sent',
  FAILED: 'Failed',
  PENDING: 'Pending',
  NOT_ATTEMPTED: 'Not Attempted',
  ATTEMPTED: 'Attempted',
};
