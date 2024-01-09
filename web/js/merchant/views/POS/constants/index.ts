import * as yup from 'yup';

import FirstTileImage from 'assets/pos/main-banner/pos-tile-image-1.webp';
import SecondTileImage from 'assets/pos/main-banner/pos-tile-image-2.webp';
import ThirdTileImage from 'assets/pos/main-banner/pos-tile-image-3.webp';
import PRODUCTS_TABLE from 'merchant/views/POS/constants/ProductsTable';
import { getPincodeInfo } from 'merchant/views/POS/services';
import {
  UpdateCartTypes,
  PosDeviceStoreState,
  DeliveryAddress,
  CheckoutValidationError,
  ProductPlans,
  RoutePattern,
  MainBannerTilesItem,
} from 'merchant/views/POS/types';

import ANDROID_MINI_POS from './AndroidMiniPos';
import ANDROID_SMART_POS from './AndroidSmartPos';
import MOBILE_POS from './MobilePos';

export const DELIVERY_AVAILABLE_TEXT = 'Delivery in 2-3 business days post KYC approval.';
export const DELIVERY_UNAVAILABLE_TEXT = 'Pincode not serviceable! Arriving Soon.';

export const MAIN_BANNER_TILES: MainBannerTilesItem[] = [
  {
    name: 'Easy Card Swipe Tile',
    decription: 'Easy Card Swipe',
    image: FirstTileImage,
    styleProps: {
      top: '20px',
      initialZoom: '1.4',
      finalZoom: '1.5',
    },
  },
  {
    name: 'Quick Tap & Pay Tile',
    decription: 'Quick Tap & Pay',
    image: SecondTileImage,
    styleProps: {
      top: '20px',
      initialZoom: '1.4',
      finalZoom: '1.5',
    },
  },
  {
    name: 'Efficient Billing Printer Tile',
    decription: 'Efficient Billing Printer',
    image: ThirdTileImage,
    styleProps: {
      top: '0px',
      initialZoom: '1',
      finalZoom: '1.1',
    },
  },
];

export const PRODUCT_TABLE_LIST = PRODUCTS_TABLE;

export const ACTIONS = {
  OPEN_CART: 'OPEN_CART',
  CLOSE_CART: 'CLOSE_CART',
  UPDATE_CART: 'UPDATE_CART',
  SET_PRODUCT_DESCRIPTION: 'SET_PRODUCT_DESCRIPTION',
  SET_USER: 'SET_USER',
  UPDATE_DELIVERY_ADDRESSES: 'UPDATE_DELIVERY_ADDRESSES',
  SET_CHECKOUT_ERRORS: 'SET_CHECKOUT_ERRORS',
  SET_DELIVERY_ADDRESS_FORM_OPEN: 'SET_DELIVERY_ADDRESS_FORM_OPEN',
};

export const UPDATE_CART_ACTIONS: Record<UpdateCartTypes, UpdateCartTypes> = {
  ADD_TO_CART: 'ADD_TO_CART',
  INCREASE_QUANTITY: 'INCREASE_QUANTITY',
  DECREASE_QUANTITY: 'DECREASE_QUANTITY',
  REMOVE_ITEM: 'REMOVE_ITEM',
  TOGGLE_PLAN: 'TOGGLE_PLAN',
};

export const PRODUCT_DESCRIPTIONS = {
  a50: ANDROID_SMART_POS,
  a910: ANDROID_MINI_POS,
  d180: MOBILE_POS,
};

export const PosStoreInitialState: PosDeviceStoreState = {
  cartItems: [],
  isCartOpen: false,
  isPricingPlanLoading: true,
  productDescriptions: [],
  user: null,
  deliveryAddresses: [],
  checkoutErrors: [],
  isDeliveryAddressFormOpen: false,
};

export const AVAILABLE_CITIES = ['Bengaluru'];

export const PLAN_NAME_MAPPINGS = {
  monthly: 'Monthly Plan',
  lifetime: 'Lifetime Plan',
};

export const NEW_DELIVERY_ADDRESS_FIELD: Omit<DeliveryAddress, 'id'> = {
  name: '',
  phoneNumber: '',
  pincode: '',
  address: '',
  city: '',
  isSelected: true,
  state: '',
  type: '',
};

export const deliveryAddressSchema = yup.object().shape({
  name: yup.string().required('Name is required'),
  phoneNumber: yup
    .string()
    .required('Phone number is requird')
    .test('len', 'Phone number entered is invalid', (val) => {
      const number = val ?? '';
      return number.length >= 10 && number.length <= 13;
    }),
  pincode: yup
    .string()
    .required('Pincode is requird')
    .test(
      'len',
      'Pincode must be exactly 6 characters',
      (val) => (val ?? '').toString().length === 6,
    ),
  city: yup.string().required('City is requird'),
  address: yup
    .string()
    .required('Address is requird')
    .test(
      'len',
      'Address cannot be more than 100 characters',
      (val) => (val?.toString?.() ?? '').length <= 100,
    ),
  state: yup.string().required('Please select a state'),
});

export const pincodeValidationSchema = (
  availableCities: string[] = [],
): Record<'pincode', yup.NumberSchema<number>> => ({
  pincode: yup
    .number()
    .required('Pincode is requird')
    .test(
      'len',
      'Pincode must be exactly 6 characters',
      (val) => (val ?? '').toString().length === 6,
    )
    .test('deliverable pincode', DELIVERY_UNAVAILABLE_TEXT, async (pincode): Promise<boolean> => {
      if (!pincode) return false;
      try {
        const { data } = await getPincodeInfo(pincode);
        if (!data?.city) {
          return false;
        }
        return availableCities.includes(data.city);
      } catch (_error) {
        return false;
      }
    }),
});

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

export const PRODUCT_PLANS: Record<string, ProductPlans> = {
  MONTHLY: 'monthly',
  LIFETIME: 'lifetime',
};

export const FEE_TYPES = {
  MONTHLY: 'monthly',
  SETUP_FEE: 'setup_fee',
};

export const MAX_ORDERABLE_ITEMS = 9;

type RouteParams = {
  params: {
    productName: string;
    orderId: string;
  };
};

export const ROUTE_PATTERNS: RoutePattern[] = [
  {
    path: '/pos/catalog/order-summary',
    steps: [
      {
        link: '/pos/catalog',
        label: 'Catalog',
      },
      {
        link: '/pos/catalog/order-summary',
        label: 'Checkout',
      },
    ],
  },
  {
    path: '/pos/catalog/:productName',
    steps: [
      {
        link: '/pos/catalog',
        label: 'Catalog',
      },
      {
        link: ({ params }: RouteParams): string => `/pos/catalog/${params?.productName}`,
        label: ({ params }: RouteParams): string =>
          PRODUCT_DESCRIPTIONS[params?.productName].productTitle,
      },
    ],
  },
  {
    path: '/pos/catalog/:productName/order-summary',
    steps: [
      {
        link: '/pos/catalog',
        label: 'Catalog',
      },
      {
        link: ({ params }: RouteParams): string => `/pos/catalog/${params?.productName}`,
        label: ({ params }: RouteParams): string =>
          PRODUCT_DESCRIPTIONS[params?.productName].productTitle,
      },
      {
        link: ({ params }: RouteParams): string =>
          `/pos/catalog/${params?.productName}/order-summary`,
        label: 'Checkout',
      },
    ],
  },
  {
    path: '/pos/orders/order-summary',
    steps: [
      {
        link: '/pos/orders',
        label: 'Your Orders',
      },
      {
        link: '/pos/orders/order-summary',
        label: 'Checkout',
      },
    ],
  },
  {
    path: '/pos/orders/:orderId',
    steps: [
      {
        link: '/pos/orders',
        label: 'Your Orders',
      },
      {
        link: ({ params }: RouteParams): string => `/pos/orders/${params?.orderId}`,
        label: ({ params }: RouteParams): string => `Order Details #${params?.orderId}`,
      },
    ],
  },
  {
    path: '/pos/orders/:orderId/order-summary',
    steps: [
      {
        link: '/pos/orders',
        label: 'Your Orders',
      },
      {
        link: ({ params }: RouteParams): string => `/pos/orders/${params?.orderId}`,
        label: ({ params }: RouteParams): string => `Order Details #${params?.orderId}`,
      },
      {
        link: ({ params }: RouteParams): string => `/pos/orders/${params?.orderId}/order-summary`,
        label: 'Checkout',
      },
    ],
  },
  {
    path: '/pos/orders',
    steps: [
      {
        link: '/pos/orders',
        label: 'Your Orders',
      },
      {
        link: (): string => `/pos/catalog/order-summary`,
        label: 'Checkout',
      },
    ],
  },
];

export const PAGE_READ_SUCCESS_MS = 15000;

export const POS_TERMS_AND_CONDITION_DATE = 1702916817;

export const EASY_DASHBOARD_ROUTES = {
  l2onboarding: '/onboarding/l2',
  storeDetails: '/onboarding/pos/store-details',
  storeDetailsWithIntent: '/onboarding/pos/store-details?intent=pos',
  l2onboardingWithIntent: '/onboarding/l2?intent=pos',
};

export { DETAILED_PRICING, TERMS_AND_CONDITIONS } from './DetailedPricingAndTnc';
export { ORDER_STATUS_META_DATA, ORDER_STATUS_TIMELINE_ITEMS } from './OrderStatus';
export * from './CommsBanner';
