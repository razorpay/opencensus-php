import React from 'react';
import Details from 'apps/self-serve/src/App/Transactions/v2/common/components/Details';
import { render } from 'apps/self-serve/src/services/test/test-utils';

import 'jest-location-mock';

jest.mock('@libs/shared-utils', () => ({
  useMobile: jest.fn(),
}));

jest.mock('common/utils/selfServeAnalytics', () => ({
  selfServeTrackInitiate: jest.fn(),
}));

export const useMobileMock = jest.requireMock('@libs/shared-utils');

export const renderApp = () =>
  render(
    <Details
      itemId="123"
      baseUrl="/base"
      selfServeAction="action"
      initiatePoint="point"
      initiatePage="page"
    />,
  );
