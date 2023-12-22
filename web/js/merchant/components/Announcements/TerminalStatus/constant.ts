export const BANNER_HEADING = {
  pending: {
    theme: 'warning',
    mobile: {
      title: 'We are working on activating UPI payments for your business',
      message: 'We’ll let you know once done',
    },
    desktop: {
      title: 'UPI activation in-progress',
      message:
        'We’re actively working on enabling UPI payment for your business. We’ll let you know once it’s done',
    },
  },
  success: {
    theme: 'success',
    mobile: {
      title: 'UPI payments for your business are now live',
      message: 'You can now collect payments from your customer using UPI',
    },
    desktop: {
      title: 'UPI payments live',
      message: 'You can now collect payments from your customers using UPI',
    },
  },
  rejected: {
    theme: 'danger',
    mobile: {
      title: 'UPI payments for your business could not be activated',
      message:
        'Unfortunately, based on your given business category our banking partners cannot support UPI payments for you',
    },
    desktop: {
      title: 'UPI payments not activated',
      message:
        'Unfortunately, based on your given business category our banking partners cannot support UPI payments for you',
    },
  },
};

export enum TERMINAL_PROCUREMENT_STATUSES {
  PENDING = 'pending',
  SUCCESS = 'success',
  REJECTED = 'rejected',
  PENDING_SEEN = 'pending_seen',
  NO_BANNER = 'no_banner',
  PENDING_ACK = 'pending_ack',
}

export const STATUSES_FOR_DISPLAYING_BANNER = [
  TERMINAL_PROCUREMENT_STATUSES.PENDING,
  TERMINAL_PROCUREMENT_STATUSES.REJECTED,
  TERMINAL_PROCUREMENT_STATUSES.SUCCESS,
];
