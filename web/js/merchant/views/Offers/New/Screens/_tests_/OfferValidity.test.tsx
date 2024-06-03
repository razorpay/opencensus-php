import React from 'react';
import { render, screen } from '@testing-library/react';
import '@testing-library/jest-dom/extend-expect';
import { Provider } from 'react-redux';

import store from 'merchant/store';

import OfferValidity from '../OfferValidity';

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
});
