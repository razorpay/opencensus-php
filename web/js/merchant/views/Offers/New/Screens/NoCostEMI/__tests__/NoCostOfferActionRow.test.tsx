import React from 'react';

import NoCostOfferActionRow from 'merchant/views/Offers/New/Screens/NoCostEMI/NoCostOfferActionRow';
import { render, screen, userEvent, fireEvent } from 'test-utils';

const initProps = {
  values: {
    issuer: 'HDFC',
  },
  plan: {
    duration: 3,
    interest: 10,
    merchant_payback: '2.4',
    subvention: 'customer',
    min_amount: 1000,
  },
  handleChange: jest.fn(),
  onOffersChange: jest.fn(),
  setFieldValue: jest.fn(),
  key: 1,
  offersData: {},
  errors: {},
  touched: {},
};

describe('<NoCostOfferActionRow>', () => {
  test('should render EMI tenure for no cost & low cost offer correctly', () => {
    render(<NoCostOfferActionRow {...initProps} />);
    expect(screen.getByTestId('offer-action-row')).toBeVisible();
  });

  test('Should be able to select tenure checkbox and select offer type', async () => {
    render(<NoCostOfferActionRow {...initProps} />);
    const checkbox = screen.queryAllByRole('checkbox')[0];
    expect(checkbox).toBeInTheDocument();
    await userEvent.click(checkbox);
    const offer = screen.getAllByTestId('offer-type-select')[0];
    await userEvent.selectOptions(offer, 'low_cost');

    const merchantDiscount = screen.getAllByRole('textbox')[0];
    const customerDiscount = screen.getAllByRole('textbox')[1];
    expect(merchantDiscount).toBeInTheDocument();
    expect(customerDiscount).toBeInTheDocument();
    await fireEvent.change(merchantDiscount, { target: { value: '1.4' } });
    expect(merchantDiscount).toHaveValue('1.4');
  });

  test('should display correct customer borne discount', () => {
    render(<NoCostOfferActionRow {...initProps} />);
    const checkbox = screen.getByRole('checkbox');
    userEvent.click(checkbox);

    const offerTypeSelect = screen.getByTestId('offer-type-select');
    userEvent.selectOptions(offerTypeSelect, 'low_cost');

    const merchantDiscountInput = screen.getAllByRole('textbox')[0];
    fireEvent.change(merchantDiscountInput, { target: { value: '1.4' } });
    fireEvent.blur(merchantDiscountInput);

    const customerDiscountInput = screen.getAllByRole('textbox')[1];
    expect(customerDiscountInput).toHaveValue('2.4');
  });

  test('should disable inputs correctly based on EMI type', () => {
    render(<NoCostOfferActionRow {...initProps} />);
    const checkbox = screen.getByRole('checkbox');
    userEvent.click(checkbox);

    const offerTypeSelect = screen.getByTestId('offer-type-select');
    userEvent.selectOptions(offerTypeSelect, 'no_cost');

    const merchantDiscountInput = screen.getAllByRole('textbox')[0];
    expect(merchantDiscountInput).toBeDisabled();

    const customerDiscountInput = screen.getAllByRole('textbox')[1];
    expect(customerDiscountInput).toBeDisabled();
  });
});
