import React from 'react';
import { screen, fireEvent } from '@testing-library/react';

import '@testing-library/jest-dom/extend-expect';
import { validatePaymentMethod, validateMaxPaymentCount } from 'merchant/views/Offers/New/helpers';
import { SUBSCRIPTION_OFFERS_PAYMENT_METHODS, CARD_TYPES } from 'merchant/views/Offers/constants';
import { render } from 'test-utils';

import ApplicableOn from '../ApplicableOn'; // Adjust the import path accordingly

jest.mock('merchant/views/Offers/New/helpers', () => ({
  validatePaymentMethod: jest.fn(),
  validateMaxPaymentCount: jest.fn(),
}));

const mockFormData = {
  payment_method: SUBSCRIPTION_OFFERS_PAYMENT_METHODS.Card,
  payment_method_type: CARD_TYPES.CREDIT,
  issuer: 'issuer1',
  payment_network: 'network1',
  max_payment_count: 5,
  iins: ['123456', '654321'],
};

const renderComponent = () =>
  render(<ApplicableOn values={mockFormData} isFormLocked={false} errors={{}} touched={{}} />);

describe('ApplicableOn Component', () => {
  test('renders without crashing', () => {
    renderComponent();
    expect(screen.getByText('Payment Method')).toBeInTheDocument();
  });

  test('renders correct fields for card payment method', () => {
    renderComponent();
    expect(screen.getByText('Card Type')).toBeInTheDocument();
    expect(screen.getByText('Bank')).toBeInTheDocument();
    expect(screen.getByText('Network')).toBeInTheDocument();
    expect(screen.getByText('Max Usage Per Card')).toBeInTheDocument();
    expect(screen.getByText('IINs')).toBeInTheDocument();
  });

  test('renders correct fields for card payment method', () => {
    renderComponent();
    expect(screen.getByText('Card Type')).toBeInTheDocument();
    expect(screen.getByText('Bank')).toBeInTheDocument();
    expect(screen.getByText('Network')).toBeInTheDocument();
    expect(screen.getByText('Max Usage Per Card')).toBeInTheDocument();
  });

  test('calls validatePaymentMethod correctly', () => {
    renderComponent();
    fireEvent.blur(screen.getByText('Payment Method'));
    expect(validatePaymentMethod).toHaveBeenCalled();
  });

  test('calls validateMaxPaymentCount correctly', () => {
    renderComponent();
    fireEvent.blur(screen.getByText('Max Usage Per Card'));
    expect(validateMaxPaymentCount).toHaveBeenCalled();
  });
});
