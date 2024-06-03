import React from 'react';
import { render, screen } from '@testing-library/react';

import '@testing-library/jest-dom/extend-expect';
import OverView from '../Overview';
const mockAbExperiments = { razorpay_offers: { variables: { result: 'on' } } };
jest.mock('common/splitz', () => ({
  useSplitzService: () => ({ abExperiments: mockAbExperiments }),
  withSplitzService: (Component) => (props) =>
    <Component {...props} splitz={{ abExperiments: { Low_cost_offer: {} } }} />,
}));

describe('OverView Component', () => {
  const mockFormData = {
    currencySymbol: '$',
    formData: {
      creation_terms_accepted: true,
      description: {
        type: 'someType',
        terms: 'someTerms',
        display_text: 'someDisplayText',
      },
      discountType: {
        discount_type: 'FLAT',
        flat_cashback: 50,
        min_amount: 100,
      },
      applicableOn: {
        issuer: 'SomeIssuer',
        payment_method: 'Card',
        payment_network: 'Visa',
        payment_method_type: 'Credit',
      },
      offerValidity: {
        starts_at: null,
        ends_at: null,
        redemption_type: null,
      },
    },
  };

  it('should render overview with provided data', () => {
    render(<OverView {...mockFormData} />);
    expect(screen.getByText('Offer Type:')).toBeInTheDocument();
    expect(screen.getByText('Offer Terms:')).toBeInTheDocument();
  });

  it('should render creation terms accepted checkbox', () => {
    render(<OverView {...mockFormData} />);
    expect(screen.getByText('Terms and Conditions')).toBeInTheDocument();
  });
});
