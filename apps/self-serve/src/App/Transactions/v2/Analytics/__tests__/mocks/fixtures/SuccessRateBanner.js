import React from 'react';
import { createMemoryHistory } from 'history';
import { render, waitFor } from 'apps/self-serve/src/services/test/test-utils';
// TODO: @Abhijeet to confirm
import SuccessRateBanner from 'apps/self-serve/src/App/Transactions/v2/Analytics/components/SuccessRateBanner';
import 'jest-location-mock';

const history = createMemoryHistory();
history.push = jest.fn();

export const currentPathname = '/payments';

export const renderApp = (props = {}) => {
  render(
    <SuccessRateBanner successRateData={99} {...props} location={{ pathname: currentPathname }} />,
    {
      history,
    },
  );
};

export const assertRedirect = async () => {
  await waitFor(() => {
    expect(history.push).toBeCalled();
  });
};
