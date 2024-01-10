import React from 'react';
import { BladeProvider } from '@razorpay/blade/components';
import { paymentTheme } from '@razorpay/blade/tokens';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { render as rootRender, screen } from '@testing-library/react';
import { MemoryRouter, Route, Routes } from 'react-router-dom';

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
    rootRender(
      <QueryClientProvider client={queryClient}>
        <BladeProvider themeTokens={paymentTheme}>
          <MemoryRouter initialEntries={['/gcms/programs']}>
            <Routes>
              <Route path="/gcms/programs" element={<Programs />} />
            </Routes>
          </MemoryRouter>
        </BladeProvider>
      </QueryClientProvider>,
    );

    await waitForLoadingToFinish();

    expect(screen.getByText('Inactive Gift Card')).toBeInTheDocument();
    expect(screen.getByText('Denomination')).toBeInTheDocument();
    expect(screen.getByText('Validity')).toBeInTheDocument();
    expect(screen.getByText('Showing', { exact: false })).toBeInTheDocument();
  });
});
