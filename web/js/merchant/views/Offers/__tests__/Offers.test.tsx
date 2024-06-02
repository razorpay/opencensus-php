import 'react-dates/initialize';
import React from 'react';
import { BladeProvider } from '@razorpay/blade/components';
import { bladeTheme } from '@razorpay/blade/tokens';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { screen } from '@testing-library/react';
import { Provider } from 'react-redux';

import store from 'merchant/store';
import { render } from 'test-utils';

import OfferIndex from '../index.js';

const variantOn = { razorpay_offers: { variables: { result: 'on' } } };
const defaultAbExperiments = variantOn;
const mockAbExperiments = defaultAbExperiments;
const queryClient = new QueryClient({ defaultOptions: { queries: { retry: false } } });
jest.mock('common/splitz', () => ({
  useSplitzService: () => ({ abExperiments: mockAbExperiments }),
  withSplitzService: (Component) => (props) =>
    <Component {...props} splitz={{ abExperiments: { Low_cost_offer: {} } }} />,
}));

const renderOffers = () => {
  return render(
    <Provider store={store}>
      <QueryClientProvider client={queryClient}>
        <BladeProvider themeTokens={bladeTheme}>
          <OfferIndex />
        </BladeProvider>
      </QueryClientProvider>
    </Provider>,
  );
};

describe('Offers', () => {
  beforeAll(() => {
    window.rzpQ = window.rzpQ || {};
    window.rzpQ.component = window.rzpQ.component || jest.fn();
    window.rzpQ.productOnboarding = jest.fn(() => ({
      success: jest.fn(),
    }));
  });
  it('should render offers page', () => {
    renderOffers();
    expect(screen.getByText('Offers')).toBeInTheDocument();
  });

  it('should open No Cost EMI form', () => {
    renderOffers();
    expect(screen.getByText('Read More')).toBeInTheDocument();
  });
});
