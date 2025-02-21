import React from 'react';
import { Box } from '@razorpay/blade/components';

import { CustomPaymentBlocks } from 'merchant/views/Settings/Configuration/CheckoutEditor/PaymentConfiguration/CustomPaymentBlocks/CustomPaymentBlocks';
import { StandardPaymentBlocks } from 'merchant/views/Settings/Configuration/CheckoutEditor/PaymentConfiguration/StandardPaymentBlocks/StandardPaymentBlocks';
import { DEFAULT_PAYMENT_CONFIG } from 'merchant/views/Settings/Configuration/CheckoutEditor/context/constants';
import {
  CHECKOUT_EDITOR_FIELDS,
  useCheckoutEditor,
} from 'merchant/views/Settings/Configuration/CheckoutEditor/context/index';

import Header from './Header';

export type ConfigurationDetailsProps = {
  hideConfigDetails: () => void;
};

export function ConfigurationDetails({ hideConfigDetails }: ConfigurationDetailsProps) {
  const { values } = useCheckoutEditor();

  const selectedConfig = values[CHECKOUT_EDITOR_FIELDS.SELECTED_PAYMENT_CONFIG];
  const isRazorpayConfigSelected = selectedConfig?.config_id === DEFAULT_PAYMENT_CONFIG.config_id;
  return (
    <Box display="flex" flexDirection="column" gap="spacing.7">
      <Header hideConfigDetails={hideConfigDetails} />
      <Box display="flex" flexDirection="column" gap="spacing.6">
        {!isRazorpayConfigSelected && <CustomPaymentBlocks />}
        <StandardPaymentBlocks isRazorpayConfigSelected={isRazorpayConfigSelected} />
      </Box>
    </Box>
  );
}
