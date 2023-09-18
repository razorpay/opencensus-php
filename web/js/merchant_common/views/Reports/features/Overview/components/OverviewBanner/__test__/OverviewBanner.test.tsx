import React from 'react';
import { render, screen } from 'test-utils';
import { OverviewBanner } from 'merchant_common/views/Reports/features/Overview/components/OverviewBanner';
import 'merchant_common/views/Reports/mocks/hooks/useReportsSplitzExperimentsMock';

const push = jest.fn();

jest.mock('common/splitz', () => ({
  useSplitzService: () => ({
    abExperiments: {},
  }),
}));

describe('OverviewCardSkeleton', () => {
  test('should render card component without any error', () => {
    render(
      <OverviewBanner
        loading={false}
        history={{
          push,
        }}
      />,
    );
    expect(
      screen.getByText(
        'Generate reports for all your business transactions, settlements & subscriptions',
      ),
    ).toBeInTheDocument();
    expect(
      screen.getByText(
        'All your products reports in one place, now with new and better interface.',
      ),
    ).toBeInTheDocument();
    expect(screen.getByText(`Download Report`)).toBeInTheDocument();
  });
});
