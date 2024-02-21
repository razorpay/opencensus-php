import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import { isDeepStrictEqual } from 'util';

import TransactionsTimeline from 'merchant/views/Transactions/v2/Payments/components/TransactionTimeline';
import { render, screen } from 'test-utils';

describe('Timeline component', () => {
  const App = ({ skips }) => {
    return <TransactionsTimeline skips={skips} />;
  };
  describe(`Should render skip timeline`, () => {
    test('should show three entries of skipped attempts', async () => {
      render(
        <App
          skips={[
            {
              skip_time: '1705462200',
              skip_reason: 'The settlement amount exceeded the available balance.',
            },
            {
              skip_time: '1705030200',
              skip_reason: 'The settlement amount exceeded the available balance.',
            },
          ]}
        />,
      );
      expect(screen.getByTestId('transaction-timeline')).toBeInTheDocument();
      const items = await screen.findAllByText('Settlement failed');
      expect(items).toHaveLength(2);
      expect(screen.getByText('Fri, Jan 12, 2024 3:30 AM')).toBeInTheDocument();
      expect(isDeepStrictEqual).toHaveLength(2);
    });
  });
});
