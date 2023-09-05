import { render, waitFor } from 'test-utils';
import { createMemoryHistory } from 'history';
import SuccessRateBanner from 'merchant/views/Transactions/v2/Analytics/components/SuccessRateBanner';
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
