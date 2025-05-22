import React from 'react';
import { BladeProvider } from '@razorpay/blade/components';
import { bladeTheme } from '@razorpay/blade/tokens';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { render as rootRender, screen } from '@testing-library/react';
import { Provider } from 'react-redux';
import { MemoryRouter, Route, Routes } from 'react-router-dom';

import { storeWithInitialState } from 'merchant/store';
import ProgramDetails from 'merchant/views/GCMS/Programs/ProgramDetails';
import { waitForLoadingToFinish } from 'test-utils';
import { Program, ProgramPriceType } from '../types';
import { string } from 'yup';
import { gift_card } from '@dashboards/payments/views/Settings/Configuration/CheckoutEditor/PaymentConfiguration/constants/method-names';

const variantOn = { razorpay_gcms: { variables: { result: 'on' } } };

const defaultAbExperiments = variantOn;
const mockAbExperiments = defaultAbExperiments;
const queryClient = new QueryClient({ defaultOptions: { queries: { retry: false } } });
jest.mock('common/splitz', () => ({
  useSplitzService: () => ({ abExperiments: mockAbExperiments }),
}));

const mockData = {
  id: 'iprog_NEC3fO5GTvvX2S',
  name: 'Inactive Gift Card',
  merchant: 'merchant_id',
  type: 'gift_card',
  policies: {
    gift_card_brand_name: 'Razorpay merchant',
    gift_card_pin_enabled: false,
    gift_card_ppi_type: 'none',
    gift_card_maximum_price: 1,
    gift_card_minimum_price: 4,
    gift_card_price_type: ProgramPriceType.RANGE,
    validity_span: 'day',
    validity_quantity: 2,
  },
};

describe('<ProgramDetails />', () => {
  it('should render programs details page', async () => {
    const initialState = {
      session: {
        mode: 'test',
        merchantId: 'test',
      },
    };
    rootRender(
      <Provider store={storeWithInitialState(initialState)}>
        <QueryClientProvider client={queryClient}>
          <BladeProvider themeTokens={bladeTheme}>
            <MemoryRouter initialEntries={['/gcms/programs/iprog_NEC3fO5GTvvX2S']}>
              <Routes>
                <Route
                  path="/gcms/programs/:program_id"
                  element={<ProgramDetails program={mockData} isImageLoading={false} />}
                />
              </Routes>
            </MemoryRouter>
          </BladeProvider>
        </QueryClientProvider>
      </Provider>,
    );

    await waitForLoadingToFinish();

    expect(screen.getByText('Inactive gift card')).toBeInTheDocument();
  });
});
