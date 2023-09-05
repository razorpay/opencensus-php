import React from 'react';
import { render } from 'test-utils';

import Details from 'merchant/views/Transactions/v2/common/components/Details';
import 'jest-location-mock';

jest.mock('common/hooks/useMobile', () => ({
  useMobile: jest.fn(),
}));

jest.mock('common/utils/selfServeAnalytics', () => ({
  selfServeTrackInitiate: jest.fn(),
}));

export const useMobileMock = jest.requireMock('common/hooks/useMobile');

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
