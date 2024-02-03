import React from 'react';
import { BladeProvider } from '@razorpay/blade/components';
import { paymentTheme } from '@razorpay/blade/tokens';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { render as rootRender, screen } from '@testing-library/react';
import { Provider } from 'react-redux';
import { MemoryRouter, Route, Routes } from 'react-router-dom';

import { storeWithInitialState } from 'merchant/store';
import ProgramDetails from 'merchant/views/GCMS/Programs/ProgramDetails';
import { waitForLoadingToFinish } from 'test-utils';

const variantOn = { razorpay_gcms: { variables: { result: 'on' } } };

const defaultAbExperiments = variantOn;
const mockAbExperiments = defaultAbExperiments;
const queryClient = new QueryClient({ defaultOptions: { queries: { retry: false } } });
jest.mock('common/splitz', () => ({
  useSplitzService: () => ({ abExperiments: mockAbExperiments }),
}));

describe('GCMS: Programs', () => {
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
          <BladeProvider themeTokens={paymentTheme}>
            <MemoryRouter initialEntries={['/gcms/programs/iprog_NEC3fO5GTvvX2S']}>
              <Routes>
                <Route path="/gcms/programs/:program_id" element={<ProgramDetails />} />
              </Routes>
            </MemoryRouter>
          </BladeProvider>
        </QueryClientProvider>
      </Provider>,
    );

    await waitForLoadingToFinish();

    expect(screen.getByText('Inactive Gift Card')).toBeInTheDocument();
    expect(screen.getByText('Go back')).toBeInTheDocument();
  });

  it('should render error message when api fails', async () => {
    const initialState = {
      session: {
        mode: 'test',
        merchantId: 'test',
      },
    };
    rootRender(
      <Provider store={storeWithInitialState(initialState)}>
        <QueryClientProvider client={queryClient}>
          <BladeProvider themeTokens={paymentTheme}>
            <MemoryRouter initialEntries={['/gcms/programs/iprog_NEC3fO5GTvvX2Z']}>
              <Routes>
                <Route path="/gcms/programs/:programId" element={<ProgramDetails />} />
              </Routes>
            </MemoryRouter>
          </BladeProvider>
        </QueryClientProvider>
      </Provider>,
    );

    await waitForLoadingToFinish();

    expect(screen.queryByText('Inactive Gift Card')).toBeNull();
    expect(screen.getByText('Go back')).toBeInTheDocument();
  });
});
