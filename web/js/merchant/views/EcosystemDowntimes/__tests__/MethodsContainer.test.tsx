import React from 'react';
import MethodsContainer from 'merchant/views/EcosystemDowntimes/containers/MethodsContainer';
import { screen, waitFor, render, server, userEvent } from 'test-utils';
import { EcosystemDowntimeProvider } from 'merchant/views/EcosystemDowntimes/context';
import { ongoingDowntimesHandler, resolvedDowntimesHandler } from './mocks/handlers';
import * as analytics from 'common/utils/analytics';

const App = (): JSX.Element => {
  return (
    <EcosystemDowntimeProvider>
      <MethodsContainer />
    </EcosystemDowntimeProvider>
  );
};

describe('<MethodsContainer/>', () => {
  test('should render MethodsContainer with Loader on screen on initial load', async () => {
    server.use(
      resolvedDowntimesHandler({ isSuccess: true }),
      ongoingDowntimesHandler({ isSuccess: true }),
    );
    render(<App />);
    expect(screen.queryByTestId('ecosystem-health-loader')).toBeInTheDocument();
    await waitFor(() => {
      expect(screen.queryByTestId('ecosystem-health-loader')).not.toBeInTheDocument();
    });
  });

  test('should render MethodsContainer on screen with instruments', async () => {
    server.use(
      resolvedDowntimesHandler({ isSuccess: true }),
      ongoingDowntimesHandler({ isSuccess: true }),
    );
    render(<App />);
    await waitFor(() => {
      const li = screen.getAllByRole('listitem');
      expect(li).toHaveLength(li.length);
    });
  });

  test('should render ErrorScreen if ongoing,resolved api fails', async () => {
    server.use(
      resolvedDowntimesHandler({ isSuccess: false }),
      ongoingDowntimesHandler({ isSuccess: false }),
    );
    render(<App />);
    await waitFor(
      () => {
        expect(screen.queryByTestId('ecosystem-health-error')).toBeInTheDocument();
      },
      {
        timeout: 4000,
      },
    );

    expect(screen.getByTestId('Notification--error')).toHaveTextContent(
      'Something went wrong while fetching downtimes',
    );
  });

  test('should trigger page view event once MethodsContainer mounts ', async () => {
    const analyticsTrackSpy = jest.spyOn(analytics, 'analyticsTrack');
    server.use(
      resolvedDowntimesHandler({ isSuccess: true }),
      ongoingDowntimesHandler({ isSuccess: true }),
    );
    render(<App />);
    await waitFor(() => {
      expect(screen.queryByTestId('ecosystem-health-loader')).not.toBeInTheDocument();
    });
    expect(analyticsTrackSpy).toHaveBeenCalledWith({
      objectName: `Ecosystem Health Page - List`,
      actionName: 'Viewed',
      screen: 'Transactions - Ecosystem Health',
      properties: expect.any(Object),
    });
  });

  test('should open downtime details if clicked on instrument', async () => {
    server.use(
      resolvedDowntimesHandler({ isSuccess: true }),
      ongoingDowntimesHandler({ isSuccess: true }),
    );
    render(<App />);
    await waitFor(() => {
      expect(screen.queryByTestId('ecosystem-health-loader')).not.toBeInTheDocument();
    });

    await userEvent.click(screen.getByText('VISA'));

    await waitFor(() => {
      expect(screen.getByLabelText('ecosystem-downtime-details')).toBeInTheDocument();
    });
  });
});
