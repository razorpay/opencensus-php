import React from 'react';
import { Link, EditInlineIcon, Box } from '@razorpay/blade/components';

import {
  WrappedSubTitle,
  WrappedTitle,
} from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/BasicCOD/styled';

import { getFormattedAmountNew } from 'common/utils/rzp-utils';

import {
  FormData,
  FormContextType,
  Amount,
} from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/types';

import basicCODSetupGuideThumbnail from 'assets/magic_checkout/cod-setup-guide-thumbnail.png';

export const ShippingMethodProfile = {
  title: 'Shipping Method',
  tooltip: 'Shipping methods from your Shopify store for each product profile on your store',
  value: (item) => (
    <Box>
      <WrappedTitle>{item?.name}</WrappedTitle>
      <WrappedSubTitle>{item?.shippingZone}</WrappedSubTitle>
    </Box>
  ),
};

export const COD = {
  title: 'COD',
  tooltip: 'Show COD payment option on Shopify Checkout page',
  value: (item) => (item?.allow_cod ? 'Enabled' : 'Disabled'),
};

export const Prepaid = {
  title: 'Prepaid',
  tooltip: 'Show prepaid payment options on Shopify Checkout page, e.g. Razorpay',
  value: (item) => (item?.allow_prepaid ? 'Enabled' : 'Disabled'),
};

export const CODSlabs = {
  title: 'COD Order Range',
  tooltip: 'Configure cart amount, between which COD payment method is enabled',
  value: (item) => {
    if (item.cod_fee_rules) {
      let text = '';
      const { amount } = item.cod_fee_rules;
      if (amount) {
        text = `${getFormattedAmountNew(amount.gte, true)} - ${getFormattedAmountNew(
          amount.lt,
          true,
        )}`;
      }
      return text;
    }
    return item.allow_cod ? 'All Orders' : 'Not Available';
  },
};

export const ShippingRate = {
  title: 'Shipping Rate',
  tooltip: 'Shipping fee configured on Shopify for the shipping method',
  value: (item) => getFormattedAmountNew(item?.fee, true),
};

export const action = (handleEdit) => ({
  title: 'Action',
  tooltip: 'Configure COD and Prepaid payment methods availability',
  value: (item: any) => (
    <Link icon={EditInlineIcon} onClick={() => handleEdit(item)}>
      Configure COD
    </Link>
  ),
});

export const COLUMNS = {
  ShippingMethodProfile,
  COD,
  Prepaid,
  CODSlabs,
  ShippingRate,
};

export const COD_ORDER_INFO =
  'COD will be applicable on all orders if Min and Max Order Range is not set.';
export const COD_ON_ALL_ORDERS = 'COD is Applicable On All Orders';
export const INVALID_PAYMENT_METHOD = 'Please Select Atleast 1 Payment Method';
export const INVALID_COD_CONFIG = 'Please Set Both Min and Max Order amount or Set Both Empty';
export const INVALID_COD_RANGE = 'Please Set Valid COD Order Range';

export const GENERAL_FEE_INFO = 'Fee can only be configured in Shopify.';
export const COD_FEE_INFO = `${GENERAL_FEE_INFO} For COD profile, set rate as total [Shipping + COD]`;
export const SETUP_GUIDE_DOCS_HREF =
  'https://razorpay.com/docs/payments/checkout360/configure-cod/';
export const SETUP_GUIDE_VIDEO_HREF = 'http://bit.ly/checkout360-guide';
export const SETUP_MAGICX_ROUTE = '/magic/settings/magicx-store-settings';

export const BASIC_COD_SETUP_GUIDE = {
  heading: 'Setup Guide',
  title: 'How to setup COD configurations for store?',
  description:
    'All shipping profiles (product groups) and shipping methods have been synced from Shopify. You can use the Configure COD button above to enable/disable COD for your respective shipping methods on Shopify. You can also limit COD availability by cart amount and disable prepaid options using the configurations above.',
  video: 'http://bit.ly/checkout360-guide',
  thumbnail: basicCODSetupGuideThumbnail,
  docs: 'https://razorpay.com/docs/payments/checkout360/configure-cod/',
};

//Greater Than Or Equal
export const GTE = 'gte';
//Less Than
export const LT = 'lt';

export const COD_TABLE_TITLE = {
  title: 'Configure COD for Shopify Shipping Methods',
  tooltip:
    'Shipping methods are fetched from your Shopify Store settings. Configure COD below to get started',
};

export const amount: Amount = {
  gte: 0,
  lt: 1000,
};

export const initialFormData: FormData = {
  allow_cod: false,
  allow_prepaid: true,
  fee_rules: {
    amount,
  },
  cod_fee_rules: {
    amount,
  },
  name: '',
  fee: 0,
  shippingZone: '',
};

export const initialFormContext: FormContextType = {
  formData: initialFormData,
  initialiseFormData: (_value: any) => {},
  updateFormData: (_key: string, _value: any) => {},
  resetForm: () => {},
  formErrors: {},
};

export const CONFIRMATION_MODAL_OBJECT = {
  name: '',
  header: '',
  desc: '',
  primaryCtaLabel: '',
  secondaryCtaLabel: '',
};

export const SYNC_STATES = {
  IDLE: 'idle',
  LOADING: 'loading',
  SUCCESS: 'success',
} as const;
