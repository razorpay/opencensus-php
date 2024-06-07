import React from 'react';
import { screen } from '@testing-library/react';
import '@testing-library/jest-dom/extend-expect';
import { Provider } from 'react-redux';

import store from 'merchant/store';
import { render } from 'test-utils';

import OfferValidity, {
  validateBlock,
  validateMaxOfferUsage,
  validatesEndsAt,
} from '../OfferValidity';

describe('OfferValidity Component', () => {
  const mockFormData = {
    starts_at: '2024-05-27T00:00:00Z',
    ends_at: '2024-06-27T00:00:00Z',
    block: '0',
    max_offer_usage: '10',
    default_offer: false,
  };

  const renderApp = (props = {}) =>
    render(
      <Provider store={store}>
        <OfferValidity formData={mockFormData} {...props} />
      </Provider>,
    );

  it('should render input fields', () => {
    renderApp();
    expect(screen.getByText('On Payment Failure')).toBeInTheDocument();
    expect(screen.getByText('Show Offer on Checkout')).toBeInTheDocument();
  });

  it('should render starting date and expiry date inputs', () => {
    renderApp();
    expect(screen.getByText('Starting On')).toBeInTheDocument();
    expect(screen.getByText('Expires On')).toBeInTheDocument();
  });

  it('should render max usage input field', () => {
    renderApp();
    expect(screen.getByText('Max Usage')).toBeInTheDocument();
  });

  it('should render "Show Offer on Checkout" checkbox', () => {
    renderApp();
    expect(screen.getByText('Show Offer on Checkout')).toBeInTheDocument();
  });

  it('should render API documentation link', () => {
    renderApp();
    expect(screen.getByText('Orders API here')).toBeInTheDocument();
  });

  it('should return error message if value is empty', () => {
    expect(validateBlock('')).toBe('Please select an option');
  });

  it('should return error message if value is not provided', () => {
    expect(validateBlock(undefined)).toBe('Please select an option');
  });

  it('should return undefined if value is provided', () => {
    expect(validateBlock('some_value')).toBeUndefined();
  });
  it('should return error message if value is not a number', () => {
    expect(validateMaxOfferUsage('abc')).toBe('Please enter a number');
  });

  it('should return undefined if value is empty', () => {
    expect(validateMaxOfferUsage('')).toBeUndefined();
  });

  it('should return undefined if value is within range and a number', () => {
    expect(validateMaxOfferUsage('100')).toBeUndefined();
  });

  it('should return error message if value is not provided', () => {
    const validator = validatesEndsAt(new Date('2024-05-27T00:00:00Z'));
    expect(validator(undefined)).toBe('Please select a date');
  });

  it('should return error message if end date is less than start date', () => {
    const startAt = new Date('2024-05-27T00:00:00Z');
    const validator = validatesEndsAt(startAt);
    const endDateBeforeStart = new Date('2024-05-26T00:00:00Z');
    expect(validator(endDateBeforeStart)).toBe('End date cannot be less that start date.');
  });

  it('should return undefined if end date is greater than or equal to start date', () => {
    const startAt = new Date('2024-05-27T00:00:00Z');
    const validator = validatesEndsAt(startAt);
    const endDateAfterStart = new Date('2024-05-28T00:00:00Z');
    expect(validator(endDateAfterStart)).toBeUndefined();
  });
});
