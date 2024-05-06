import React from 'react';
import Details from 'apps/self-serve/src/App/Transactions/v2/common/components/Details';
import { render } from 'apps/self-serve/src/services/test/test-utils';

import 'jest-location-mock';

jest.mock('@dashboard/shared-ui/hooks', () => ({
  useMobile: jest.fn(),
}));

jest.mock('common/utils/selfServeAnalytics', () => ({
  selfServeTrackInitiate: jest.fn(),
}));

export const useMobileMock = jest.requireMock('@dashboard/shared-ui/hooks');

export const renderApp = () =>
  render(
    <Details
      itemId="123"
      baseUrl="/base"
      selfServeAction="action"
      initiatePoint="point"
      initiatePage="page"
      prevPath="/prev"
    />,
  );
