import React from 'react';
import { screen, render, userEvent } from 'test-utils';
import App from 'merchant/views/Subscriptions/RegistrationLinks/components/RegistrationLinksForm/CustomerDetails';

describe('RL - Customer Details Form', () => {
  const onBlurElement = jest.fn();
  beforeEach(() => {
    render(<App handleDateChange={() => () => {}} onBlurElement={onBlurElement} />);
  });

  test('Should render all the Customer Details Fields', () => {
    [
      '^description$',
      'payment / authentication description',
      'customer name',
      'customer contact',
      'phone number of customer',
      'email of customer',
      'notify',
      'via sms',
      'via email',
      'receipt no.',
      'receipt for customer',
      'registration link expiry',
      'no expiry',
    ].forEach((fieldLabel) => {
      expect(screen.getByText(new RegExp(fieldLabel, 'i'))).toBeInTheDocument();
    });
  });

  test('Should show error on invalid phone or email in Customer Details', async () => {
    const phone = screen.getAllByRole('textbox')[2];
    const email = screen.getAllByRole('textbox')[3];

    await userEvent.type(phone, '123');
    expect(screen.getByText('Invalid Phone')).toBeInTheDocument();
    await userEvent.type(email, 'test.com');
    expect(screen.getByText('Invalid Email')).toBeInTheDocument();

    await userEvent.type(phone, '9876543210');
    expect(screen.queryByText('Invalid Phone')).not.toBeInTheDocument();
    await userEvent.type(email, 'test@test.com');
    expect(screen.queryByText('Invalid Email')).not.toBeInTheDocument();
  });

  test('Should render Customer Details with expiry date', async () => {
    const noExpiry = screen.getByRole('checkbox', {
      name: /no expiry/i,
    });
    const calendar = screen.getByPlaceholderText(/expiry \(dd-mm-yyyy\)/i);
    await userEvent.click(calendar);
    await userEvent.click(noExpiry);
    expect(onBlurElement).toHaveBeenCalledTimes(1);
  });
});
