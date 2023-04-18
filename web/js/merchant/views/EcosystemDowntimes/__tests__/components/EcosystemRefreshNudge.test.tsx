import React from 'react';
import MethodsContainer from 'merchant/views/EcosystemDowntimes/containers/MethodsContainer';
import { screen, waitFor, render, server, waitForElementToBeRemoved, userEvent } from 'test-utils';
import { EcosystemDowntimeProvider } from 'merchant/views/EcosystemDowntimes/context';
import {
  ongoingDowntimesHandler,
  resolvedDowntimesHandler,
} from 'merchant/views/EcosystemDowntimes/__tests__/mocks/handlers';
import EcosystemRefreshNudge from 'merchant/views/EcosystemDowntimes/components/EcosystemRefreshNudge';

const App = ({ waitTime = 0 }: { waitTime: number }): JSX.Element => {
  return (
    <EcosystemDowntimeProvider>
      <EcosystemRefreshNudge waitInterval={waitTime} />
      <MethodsContainer />
    </EcosystemDowntimeProvider>
  );
};

describe('<EcosystemRefreshNudge/>', () => {
  test('Refresh nudge should render on screen', async () => {
    server.use(ongoingDowntimesHandler({ isSuccess: true }));
    render(<App waitTime={1} />);
    await waitFor(() => {
      expect(screen.getByLabelText('ecosystem-refresh-nudge')).toBeInTheDocument();
    });
  });

  test('Refresh nudge should be enabled after wait interval ellapses', async () => {
    server.use(ongoingDowntimesHandler({ isSuccess: true, downtimeExists: false }));
    render(<App waitTime={1} />);
    await waitForElementToBeRemoved(() => screen.queryByTestId('ecosystem-health-loader'));
    expect(screen.getByTestId('time-left')).toHaveTextContent('1s');
    await waitFor(
      () => {
        expect(screen.getByLabelText('ecosystem-refresh-button')).toHaveClass('enabled-refresh');
      },
      {
        timeout: 2000,
      },
    );
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
    server.use(ongoingDowntimesHandler({ isSuccess: false, downtimeExists: false }));
    render(<App waitTime={1} />);
    await waitFor(
      () => {
        expect(screen.getByLabelText('ecosystem-refresh-button')).toHaveClass('enabled-refresh');
      },
      {
        timeout: 2000,
      },
    );
  });

  test('Refresh nudge should be enabled if error encountered while fetch past downtimes', async () => {
    server.use(resolvedDowntimesHandler({ isSuccess: false }));
    render(<App waitTime={1} />);
    await waitFor(
      () => {
        expect(screen.getByLabelText('ecosystem-refresh-button')).toHaveClass('enabled-refresh');
      },
      {
        timeout: 2000,
      },
    );
  });
});
