import * as yup from 'yup';
import ANDROID_MINI_POS from './DeviceConfig/AndroidMiniPos';
import ANDROID_SMART_POS from './DeviceConfig/AndroidSmartPos';
import MOBILE_POS from './DeviceConfig/MobilePos';
import SOUNDBOX from './DeviceConfig/Soundbox';
import STANDEEANDSTICKER from './DeviceConfig/StandeeAndSticker';
import { OrderStatusMetaData, OrderStatusTypes, PosDeviceStoreState } from './types';
import { CheckIcon, ClockIcon, SlashIcon } from '@razorpay/blade/components';
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

export const PosStoreInitialState: PosDeviceStoreState = {
  cartItems: [],
  isCartOpen: false,
  isPricingPlanLoading: true,
  productDescriptions: [],
  user: null,
  deliveryAddresses: [],
  checkoutErrors: [],
  isDeliveryAddressFormOpen: false,
  isRenderedFromPartnerRoute: false,
};

export const ORDER_LIST_STATUS_TYPES = ['paid', 'delivered', 'rejected'];

export const ORDER_STATUS_META_DATA: Record<OrderStatusTypes, OrderStatusMetaData> = {
  ORDER_CONFIRMED: {
    key: 'ORDER_CONFIRMED',
    name: 'ORDER CONFIRMED',
    icon: ClockIcon,
    variant: 'notice',
  },
  ORDER_RECEIVED: {
    key: 'ORDER_RECEIVED',
    name: 'ORDER RECEIVED',
    icon: ClockIcon,
    variant: 'notice',
  },
  ORDER_DELIVERED: {
    key: 'ORDER_DELIVERED',
    name: 'DELIVERED',
    icon: CheckIcon,
    variant: 'positive',
  },
  ORDER_REJECTED: {
    key: 'ORDER_REJECTED',
    name: 'ORDER REJECTED',
    icon: SlashIcon,
    variant: 'negative',
  },
  REFUND_PENDING: {
    key: 'REFUND_PENDING',
    name: 'REFUND PENDING',
    icon: ClockIcon,
    variant: 'notice',
  },
  REFUND_INITIATED: {
    key: 'REFUND_INITIATED',
    name: 'REFUND INITIATED',
    icon: ClockIcon,
    variant: 'notice',
  },
  REFUND_COMPLETED: {
    key: 'REFUND_COMPLETED',
    name: 'AMOUNT REFUNDED',
    icon: CheckIcon,
    variant: 'positive',
  },
};

export const PLAN_NAME_MAPPINGS = {
  monthly: 'Monthly Plan',
  lifetime: 'Lifetime Plan',
};

export const PRODUCT_DESCRIPTIONS = {
  [ANDROID_SMART_POS.code]: ANDROID_SMART_POS,
  [ANDROID_MINI_POS.code]: ANDROID_MINI_POS,
  [MOBILE_POS.code]: MOBILE_POS,
  [SOUNDBOX.code]: SOUNDBOX,
  [STANDEEANDSTICKER.code]: STANDEEANDSTICKER,
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
