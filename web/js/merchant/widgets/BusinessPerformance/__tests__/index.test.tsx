import React from 'react';
import { renderWithSuspense, screen, waitFor } from 'test-utils';

import { BusinessPerformance } from '../index';
import { BusinessPerformanceProps } from '../types';

const mockUseMobile = jest.fn();
jest.mock('@libs/shared-utils', () => ({
  useMobile: mockUseMobile,
}));

describe('Testing BusinessPerformance Component', () => {
  const mockProps: BusinessPerformanceProps = {
    id: 'bp',
    inputs: [
      {
        default_value: 'no-of-payments',
        name: 'View By:',
        type: 'base-select',
        values: ['no-of-payments', 'gmv'],
      },
    ],
    title: 'In Person Business Performance',
    type: 'business_performance',
    components: [
      {
        data: {
          cards: [
            { id: 'bp_card_1', label: 'Gujarat', value: '1.23Cr' },
            { id: 'bp_card_2', label: 'Karnataka', value: '1.23L' },
            { id: 'bp_card_3', label: 'Kerala', value: '1.23k' },
          ],
        },
        id: 'bp_1',
        title: 'Top Performing States',
        variant: 'positive' as const,
      },
      {
        data: {
          cards: [
            { id: 'bp_card_1', label: 'Andhra Pradesh', value: '1.23k' },
            { id: 'bp_card_2', label: 'Manipur', value: '1.23L' },
            { id: 'bp_card_3', label: 'Haryana', value: '1.23Cr' },
          ],
        },
        id: 'bp_2',
        title: 'Bottom Performing States',
        variant: 'negative' as const,
      },
    ],
    filters: {
      date_time: { quick: 'last_7_days' },
      store_ids: [],
      payment_source: 'all',
    },
  };

  it('should check the UI by toggling the loading state', async () => {
    mockUseMobile.mockReturnValue(false);
    const { rerender } = renderWithSuspense(
      <BusinessPerformance
        {...mockProps}
        isLoading={true}
        queryKey={[]}
        analytics={{ enabled: false }}
      />,
    );

    expect(screen.getByTestId('business-performance-loader')).toBeInTheDocument();

    rerender(
      <React.Suspense fallback="">
        <BusinessPerformance
          {...mockProps}
          isLoading={false}
          queryKey={[]}
          analytics={{ enabled: false }}
        />
      </React.Suspense>,
    );

    await waitFor(() => {
      expect(screen.getByRole('heading', { name: mockProps.title })).toBeInTheDocument();
    });
  });

  it.each([true, false])(
    'should render the component correctly when isMobile is %s',
    (isMobile) => {
      mockUseMobile.mockReturnValue(isMobile);
      renderWithSuspense(
        <BusinessPerformance
          {...mockProps}
          isLoading={false}
          queryKey={[]}
          analytics={{ enabled: false }}
        />,
      );

      expect(screen.getByText('Top Performing States')).toBeInTheDocument();
      expect(screen.getByText('Bottom Performing States')).toBeInTheDocument();

      expect(screen.getAllByText('#1')).toHaveLength(2);

      expect(screen.getByText('Gujarat')).toBeInTheDocument();
      expect(screen.getAllByText('1.23Cr')).toHaveLength(2);

      expect(screen.getByText('Andhra Pradesh')).toBeInTheDocument();
      expect(screen.getAllByText('1.23k')).toHaveLength(2);
    },
  );
});
