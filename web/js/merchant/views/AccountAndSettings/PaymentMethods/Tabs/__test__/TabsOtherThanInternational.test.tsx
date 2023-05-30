import React from 'react';
import Cards from 'merchant/views/AccountAndSettings/PaymentMethods/Tabs/Cards';
import Emi from 'merchant/views/AccountAndSettings/PaymentMethods/Tabs/Emi';
import Netbanking from 'merchant/views/AccountAndSettings/PaymentMethods/Tabs/Netbanking';
import Paylater from 'merchant/views/AccountAndSettings/PaymentMethods/Tabs/Paylater';
import UpiQR from 'merchant/views/AccountAndSettings/PaymentMethods/Tabs/UpiQR';
import Wallet from 'merchant/views/AccountAndSettings/PaymentMethods/Tabs/Wallet';
import MealCard from 'merchant/views/AccountAndSettings/PaymentMethods/Tabs/MealCard';
import { PaymentMethodsFields } from 'merchant/views/AccountAndSettings/AccountAndSettingsHome/typings/section';
import { render, screen } from 'test-utils';

jest.mock('merchant/views/AccountAndSettings/PaymentMethods/components/Section', () => ({
  __esModule: true,
  default: ({ type }) => <div>type: {type}</div>,
}));

describe('Payment Method tabs other than international', () => {
  test.each([
    [PaymentMethodsFields.CARDS, Cards],
    [PaymentMethodsFields.EMI, Emi],
    [PaymentMethodsFields.NETBANKING, Netbanking],
    [PaymentMethodsFields.PAYLATER, Paylater],
    [PaymentMethodsFields.UPI, UpiQR],
    [PaymentMethodsFields.WALLET, Wallet],
    [PaymentMethodsFields.MEAL_CARD, MealCard],
  ])('should render %s component', (text, Component) => {
    render(<Component />);
    expect(screen.getByText(`type: ${text}`)).toBeInTheDocument();
  });
});
