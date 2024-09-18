import React from 'react';
import { screen } from '@testing-library/react';
import '@testing-library/jest-dom/extend-expect';
import { Provider } from 'react-redux';

import store from 'merchant/store';
import { render } from 'test-utils';

import OfferValidity, {
  validateBlock,
  validateExpiry,
  validateMaxOfferUsage,
} from '../OfferValidity';
import moment from 'moment';

describe('OfferValidity Component', () => {
  const mockValues = {
    starts_at: moment('2024-05-27T00:00:00Z'),
    ends_at: moment('2024-06-27T00:00:00Z'),
    block: '0',
    max_offer_usage: '10',
    default_offer: '0',
  };

  const renderApp = (props = {}) =>
    render(
      <Provider store={store}>
        <OfferValidity values={mockValues} errors={{}} touched={{}} {...props} />
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
    expect(validateBlock('some_value')).toBe(false);
  });
  it('should return error message if value is not a number', () => {
    expect(validateMaxOfferUsage('abc')).toBe('Please enter a number');
  });

  it('should return undefined if value is empty', () => {
    expect(validateMaxOfferUsage('')).toBe(false);
  });

  it('should return undefined if value is within range and a number', () => {
    expect(validateMaxOfferUsage('100')).toBe(false);
  });

  it('should return false if no start date is passed.', () => {
    expect(validateExpiry(undefined, moment(new Date()))).toBeFalsy();
  });

  it('should return error message if end date is less than start date', () => {
    expect(
      validateExpiry(moment(new Date('2024-10-10')), moment(new Date('2024-10-09'))),
    ).not.toBeFalsy();
  });

  it('should return false if end date is after start date', () => {
    expect(
      validateExpiry(moment(new Date('2047-10-29')), moment(new Date('2047-10-30'))),
    ).toBeFalsy();
  });
});
