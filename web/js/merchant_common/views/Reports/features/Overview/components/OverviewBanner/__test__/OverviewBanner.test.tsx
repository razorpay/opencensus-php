import React from 'react';
import { render, screen } from 'test-utils';
import { OverviewBanner } from 'merchant_common/views/Reports/features/Overview/components/OverviewBanner';
import 'merchant_common/views/Reports/mocks/hooks/useReportsSplitzExperimentsMock';
import { isJKOfflineMerchant } from '@libs/shared-utils';

const push = jest.fn();

jest.mock('common/splitz', () => ({
  useSplitzService: () => ({
    abExperiments: {},
  }),
}));

jest.mock('@libs/shared-utils', () => ({
  ...jest.requireActual('@libs/shared-utils'),
  isJKOfflineMerchant: jest.fn(),
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

  test('should not render schedule report tab for J&K bank', () => {
    (isJKOfflineMerchant as jest.Mock).mockReturnValue(true);
    render(
      <OverviewBanner
        loading={false}
        history={{
          push,
        }}
      />,
    );

    expect(screen.queryByText('Schedule Report')).not.toBeInTheDocument();
  });
});
