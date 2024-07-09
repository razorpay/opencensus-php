import React from 'react';
import { screen } from '@testing-library/react';

import { render } from 'test-utils';

import '@testing-library/jest-dom/extend-expect';
import OverView, { wordWithSpace } from '../Overview';
const mockAbExperiments = { razorpay_offers: { variables: { result: 'on' } } };
jest.mock('common/splitz', () => ({
  useSplitzService: () => ({ abExperiments: mockAbExperiments }),
  withSplitzService: (Component) => (props) =>
    <Component {...props} splitz={{ abExperiments: { Low_cost_offer: {} } }} />,
}));

describe('OverView Component', () => {
  const mockFormData = {
    currencySymbol: '$',
    values: {
      creation_terms_accepted: '1',
      type: 'someType',
      terms: 'someTerms',
      display_text: 'someDisplayText',
      discount_type: 'FLAT',
      flat_cashback: 50,
      min_amount: 100,
      issuer: 'SomeIssuer',
      payment_method: 'Card',
      payment_network: 'Visa',
      payment_method_type: 'Credit',
      starts_at: null,
      ends_at: null,
      redemption_type: null,
    },
    errors: {},
    touched: {},
  };

  it('should render overview with provided data', () => {
    render(<OverView {...mockFormData} />);
    expect(screen.getByText('Offer Type:')).toBeInTheDocument();
    expect(screen.getByText('Offer Terms:')).toBeInTheDocument();
    expect(screen.getByText('someTerms')).toBeInTheDocument();
    expect(screen.getByText('someDisplayText')).toBeInTheDocument();
    expect(screen.getByText('Offer Validity:')).toBeInTheDocument();
    expect(screen.getByText('Valid till --')).toBeInTheDocument();
  });

  it('should render creation terms accepted checkbox', () => {
    render(<OverView {...mockFormData} />);
    expect(screen.getByText('Terms and Conditions')).toBeInTheDocument();
    expect(
      screen.getByRole('checkbox', {
        name: 'I understand that the discount/cashback given in this offer will be borne by me and not Razorpay.',
      }),
    ).toBeInTheDocument();
  });

  it('should return word with space if word is provided', () => {
    const result = wordWithSpace('Word');
    expect(result).toBe('Word ');
  });

  it('should return empty string if word is not provided', () => {
    const result = wordWithSpace('');
    expect(result).toBe('');
  });
});
