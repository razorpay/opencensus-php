import moment from 'moment';
import { CheckoutValidationError } from '../types/hook';
export const TODAY = moment();
export const FIELD_PADDING = '8px 12px';
export const REPORT_TEST_DASHBOARD = 'merchant';
export const REPORT_OVERVIEW_LOADING_SKELETONS_COUNT = 5;
export const NON_OWNED_CONFIG_TYPE = 'custom_non_owned';

export const FILE_UPLOAD_PAGE = 'file_upload_page';

export const MONTHLY_INVOICE_REPORT = 'Monthly Invoice Report';
export const OPTIMISER_SETTLEMENTS = 'Optimiser Settlements';
export const PAYMENTS_REPORTS = 'Payments Report';
export const PAYMENTS = 'Payments';

export const CHECKOUT_ERRORS: Record<string, CheckoutValidationError> = {
  DELIVERY_ADDRESS_FORM_OPEN: {
    sev: 0,
    isHideCheckout: false,
    type: 'DELIVERY_ADDRESS_FORM_OPEN',
    title: 'Please save your address to continue',
    description: '',
  },
  MAX_CART_ITEMS: {
    sev: 0,
    isHideCheckout: false,
    type: 'MAX_CART_ITEMS',
    title: 'This order can accommodate a maximum of 9 items',
    description: 'Reduce the total number of items for this order',
  },
  ORDER_CREATE_FAILED: {
    sev: 0,
    isHideCheckout: false,
    type: 'ORDER_CREATE_FAILED',
    title: 'Failed to create order',
    description: 'Something went wrong while creating order',
  },
  KYC_REJECTED: {
    sev: 0,
    isHideCheckout: false,
    type: 'KYC_REJECTED',
    title: 'Unfortunately! We can’t proceed with your order',
    description:
      'Due to some issue with your shop details, your KYC has been rejected. Hence we can’t proceed with your order. For any questions, please contact our support team.',
  },
  ORDER_NOT_DELIVERABLE: {
    sev: 1,
    isHideCheckout: false,
    type: 'ORDER_NOT_DELIVERABLE',
    title: 'We are coming to your city soon!',
    description:
      'Unfortunately, as per the address chosen for delivery, we are not delivering in your city yet. Please join out waitlist to show your support and we’ll be there soon.',
  },
  NO_CART_ITEMS: {
    sev: 0,
    isHideCheckout: false,
    type: 'NO_CART_ITEMS',
    title: 'There are no items in your cart',
    description: '',
  },
  MULTIPLE_ORDERS: {
    sev: 0,
    isHideCheckout: false,
    type: 'NO_CART_ITEMS',
    title: 'Unfortunately! We can’t proceed with your order',
    description:
      'Sorry, we are supporting only one order per merchant at the moment. We will soon allow multiple orders per merchant, thanks for your patience!',
  },
};
export const BILLING_REPORTS = 'bills';
