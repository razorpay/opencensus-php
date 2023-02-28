import React from 'react';
import PaymentMethodsSection from 'merchant/views/AccountAndSettings/PaymentMethods/components/Section';
import { PaymentMethodsFields } from 'merchant/views/AccountAndSettings/AccountAndSettingsHome/typings/section';

const Wallet = () => {
  return <PaymentMethodsSection type={PaymentMethodsFields.WALLET} />;
};

export default Wallet;
