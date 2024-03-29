import React from 'react';
import { BladeProvider } from '@razorpay/blade/components';
import { bladeTheme } from '@razorpay/blade/tokens';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { render as rootRender, screen } from '@testing-library/react';
import { Provider } from 'react-redux';
import { MemoryRouter, Route, Routes } from 'react-router-dom';

import { storeWithInitialState } from 'merchant/store';
import Programs from 'merchant/views/GCMS/Programs';
import { waitForLoadingToFinish } from 'test-utils';

const variantOn = { razorpay_gcms: { variables: { result: 'on' } } };

const defaultAbExperiments = variantOn;
const mockAbExperiments = defaultAbExperiments;
const queryClient = new QueryClient({ defaultOptions: { queries: { retry: false } } });
jest.mock('common/splitz', () => ({
  useSplitzService: () => ({ abExperiments: mockAbExperiments }),
}));

describe('GCMS: Programs', () => {
  it('should render programs list page', async () => {
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
            <MemoryRouter initialEntries={['/gcms/programs']}>
              <Routes>
                <Route path="/gcms/programs" element={<Programs />} />
              </Routes>
            </MemoryRouter>
          </BladeProvider>
        </QueryClientProvider>
      </Provider>,
    );

    await waitForLoadingToFinish();

    expect(screen.getByText('Inactive Gift Card')).toBeInTheDocument();
    expect(screen.getByText('Denomination')).toBeInTheDocument();
    expect(screen.getByText('Validity')).toBeInTheDocument();
    expect(screen.getByText('Showing', { exact: false })).toBeInTheDocument();
  });
});
