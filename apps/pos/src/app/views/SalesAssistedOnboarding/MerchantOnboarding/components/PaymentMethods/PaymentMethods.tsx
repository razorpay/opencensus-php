import React from 'react';
import PaymentMethodContextProvider from './PaymentMethodContextProvider';
import PaymentMethodForm from './PaymentMethodForm';
import NACHForm from './NACHForm';

const PaymentMethods = ({ nach = false }) => {
  return (
    <PaymentMethodContextProvider component={nach ? NACHForm : PaymentMethodForm} nach={nach} />
  );
};

export default PaymentMethods;
