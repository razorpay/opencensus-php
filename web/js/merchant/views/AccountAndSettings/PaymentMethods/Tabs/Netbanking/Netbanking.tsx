import React from 'react';
import PaymentMethodsSection from 'merchant/views/AccountAndSettings/PaymentMethods/components/Section';
import { PaymentMethodsFields } from 'merchant/views/AccountAndSettings/AccountAndSettingsHome/typings/section';

const Netbanking = () => {
  return <PaymentMethodsSection type={PaymentMethodsFields.NETBANKING} />;
};

export default Netbanking;
