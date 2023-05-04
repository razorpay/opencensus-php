import React from 'react';
import MethodsContainer from 'merchant/views/EcosystemDowntimes/containers/MethodsContainer';
import {
  screen,
  waitFor,
  render,
  server,
  waitForElementToBeRemoved,
  userEvent,
  delay,
} from 'test-utils';
import { EcosystemDowntimeProvider } from 'merchant/views/EcosystemDowntimes/context';
import {
  ongoingDowntimesHandler,
  resolvedDowntimesHandler,
} from 'merchant/views/EcosystemDowntimes/__tests__/mocks/handlers';
import EcosystemRefreshNudge from 'merchant/views/EcosystemDowntimes/components/EcosystemRefreshNudge';
import * as Services from 'merchant/views/EcosystemDowntimes/services';

const App = ({ waitTime = 0 }: { waitTime?: number }): JSX.Element => {
  return (
    <EcosystemDowntimeProvider>
      <EcosystemRefreshNudge waitInterval={waitTime} />
      <MethodsContainer />
    </EcosystemDowntimeProvider>
  );
};

describe('<EcosystemRefreshNudge/>', () => {
  test('Refresh nudge should render on screen', async () => {
    server.use(
      resolvedDowntimesHandler({ isSuccess: true }),
      ongoingDowntimesHandler({ isSuccess: true }),
    );
    render(<App waitTime={1} />);
    await waitFor(() => {
      expect(screen.getByLabelText('ecosystem-refresh-nudge')).toBeInTheDocument();
    });
  });

  test('Refresh nudge should be enabled after wait interval ellapses', async () => {
    server.use(
      ongoingDowntimesHandler({ isSuccess: true, downtimeExists: false }),
      resolvedDowntimesHandler({ isSuccess: true }),
    );
    render(<App waitTime={1} />);
    await waitForElementToBeRemoved(() => screen.queryByTestId('ecosystem-health-loader'));
    expect(screen.getByTestId('time-left')).toHaveTextContent('1s');
    await delay(2000);
    expect(screen.getByLabelText('ecosystem-refresh-button')).toHaveClass('enabled-refresh');

    server.use(ongoingDowntimesHandler({ isSuccess: true, downtimeExists: true }));
    await userEvent.click(screen.getByLabelText('ecosystem-refresh-button'));
    expect(screen.getByLabelText('ecosystem-downtimes-refresh-spinner')).toBeInTheDocument();

    await waitFor(() => {
      expect(screen.getByLabelText('overall-summary-text')).toHaveTextContent(
        'Few drops noticed in Cards, UPI',
      );
    });
  });

  test('Refresh nudge should be enabled if error encountered', async () => {
    server.use(
      resolvedDowntimesHandler({ isSuccess: false }),
      ongoingDowntimesHandler({ isSuccess: false, downtimeExists: false }),
    );
    render(<App waitTime={1} />);
    await delay(2000);
    expect(screen.getByLabelText('ecosystem-refresh-button')).toHaveClass('enabled-refresh');
  });

  test('Refresh nudge should refetch past downtimes', async () => {
    const servicesSpy = jest.spyOn(Services, 'fetchResolvedDowntimes');
    server.use(
      resolvedDowntimesHandler({ isSuccess: true }),
      ongoingDowntimesHandler({ isSuccess: true, downtimeExists: false }),
    );
    render(<App />);
    await waitFor(() => {
      expect(screen.getByLabelText('ecosystem-refresh-button')).toHaveClass('enabled-refresh');
    });
    await userEvent.click(screen.getByLabelText('ecosystem-refresh-button'));
    expect(servicesSpy).toHaveBeenCalledTimes(2);
  });
});
