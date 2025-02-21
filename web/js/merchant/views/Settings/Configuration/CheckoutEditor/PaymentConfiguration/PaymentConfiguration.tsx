import React from 'react';

import { ConfigurationDetails } from 'merchant/views/Settings/Configuration/CheckoutEditor/PaymentConfiguration/ConfigurationDetails/ConfigurationDetails';
import { PAYMENT_CONFIG_SCREEN } from 'merchant/views/Settings/Configuration/CheckoutEditor/context/constants';
import {
  CHECKOUT_EDITOR_FIELDS,
  useCheckoutEditor,
} from 'merchant/views/Settings/Configuration/CheckoutEditor/context/index';

import ConfigurationHeading from './ConfigurationHeading';
import ConfigurationList from './ConfigurationList';

export function PaymentConfiguration() {
  const { values, handlePaymentConfigScreenChange } = useCheckoutEditor();
  const selectedConfig = values[CHECKOUT_EDITOR_FIELDS.SELECTED_PAYMENT_CONFIG];
  const paymentConfigScreen = values[CHECKOUT_EDITOR_FIELDS.PAYMENT_CONFIG_SCREEN];

  function handleShowConfigDetails() {
    handlePaymentConfigScreenChange(PAYMENT_CONFIG_SCREEN.CONFIG_DETAILS);
  }

  function handleHideConfigDetails() {
    handlePaymentConfigScreenChange(PAYMENT_CONFIG_SCREEN.CONFIG_LIST);
  }
  return (
    <>
      <ConfigurationHeading />

      {paymentConfigScreen === PAYMENT_CONFIG_SCREEN.CONFIG_DETAILS && selectedConfig ? (
        <ConfigurationDetails hideConfigDetails={handleHideConfigDetails} />
      ) : (
        <ConfigurationList showConfigDetails={handleShowConfigDetails} />
      )}
    </>
  );
}
