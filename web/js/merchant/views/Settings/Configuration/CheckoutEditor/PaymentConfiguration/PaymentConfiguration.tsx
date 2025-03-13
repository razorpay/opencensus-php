import React, { useEffect } from 'react';

import { ConfigurationDetails } from 'merchant/views/Settings/Configuration/CheckoutEditor/PaymentConfiguration/ConfigurationDetails/ConfigurationDetails';
import { PAYMENT_CONFIG_SCREEN } from 'merchant/views/Settings/Configuration/CheckoutEditor/context/constants';
import {
  CHECKOUT_EDITOR_FIELDS,
  useCheckoutEditor,
} from 'merchant/views/Settings/Configuration/CheckoutEditor/context/index';

import ConfigurationList from './ConfigurationList';
import _track from './track';

export function PaymentConfiguration({
  parentRef
}: {
  parentRef: React.RefObject<HTMLDivElement>;
}) {
  const { values, handlePaymentConfigScreenChange } = useCheckoutEditor();
  const selectedConfig = values[CHECKOUT_EDITOR_FIELDS.SELECTED_PAYMENT_CONFIG];
  const paymentConfigScreen = values[CHECKOUT_EDITOR_FIELDS.PAYMENT_CONFIG_SCREEN];

  function handleShowConfigDetails() {
    handlePaymentConfigScreenChange(PAYMENT_CONFIG_SCREEN.CONFIG_DETAILS);
  }

  function handleHideConfigDetails() {
    handlePaymentConfigScreenChange(PAYMENT_CONFIG_SCREEN.CONFIG_LIST);
  }

  useEffect(() => {
    _track.paymentConfigVisited();
  }, []);

  useEffect(() => {
    // have to reset scroll since browser retains scroll position in 
    // ConfigurationDetails and ConfigurationList components
    if (parentRef?.current) {
      parentRef.current.scrollTo(0, 0);
    }
  }, [paymentConfigScreen]);

  return (
    paymentConfigScreen === PAYMENT_CONFIG_SCREEN.CONFIG_DETAILS && selectedConfig ? (
      <ConfigurationDetails hideConfigDetails={handleHideConfigDetails} />
    ) : (
      <ConfigurationList showConfigDetails={handleShowConfigDetails} />
    )
  );
}
