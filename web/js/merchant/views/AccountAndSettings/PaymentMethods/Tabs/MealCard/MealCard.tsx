import React from 'react';
import PaymentMethodsSection from 'merchant/views/AccountAndSettings/PaymentMethods/components/Section';
import { PaymentMethodsFields } from 'merchant/views/AccountAndSettings/AccountAndSettingsHome/typings/section';

const MealCard = () => {
  return <PaymentMethodsSection type={PaymentMethodsFields.MEAL_CARD} />;
};

export default MealCard;
