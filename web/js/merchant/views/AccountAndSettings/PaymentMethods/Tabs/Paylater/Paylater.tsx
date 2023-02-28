import React from 'react';
import PaymentMethodsSection from 'merchant/views/AccountAndSettings/PaymentMethods/components/Section';
import { PaymentMethodsFields } from 'merchant/views/AccountAndSettings/AccountAndSettingsHome/typings/section';

const Paylater = () => {
  return <PaymentMethodsSection type={PaymentMethodsFields.PAYLATER} />;
};

export default Paylater;
