import React from 'react';
import { screen, render, waitFor } from 'test-utils';
import DowntimeDetailsContainer from 'merchant/views/EcosystemDowntimes/containers/DowntimeDetailsContainer';
import { EcosystemDowntimeContext } from 'merchant/views/EcosystemDowntimes/context';
import { processPreviousDowntimes } from 'merchant/views/EcosystemDowntimes/helpers';
import { previous_downtimes_mock } from './mocks/mockResponses';

const instrument = {
  logo: '',
  name: 'VISA',
  key: 'VISA',
  method: 'card',
  group: 'network',
};

const initProps = {
  state: {
    activeDowntimes: {},
    focusedInstrument: instrument,
    isPreviousDowntimesFetching: false,
    isPreviousDowntimesLoading: false,
    isPreviousDowntimesError: false,
    previousDowntimes: {},
  },
  dispatch: jest.fn(),
  refreshData: jest.fn(),
};

const App = ({ propsToBeExposed }): JSX.Element => {
  return (
    <EcosystemDowntimeContext.Provider value={propsToBeExposed}>
      <DowntimeDetailsContainer />
    </EcosystemDowntimeContext.Provider>
  );
};

describe('<DowntimeDetailsContainer/>', () => {
  test('should render downtime details container on screen', async () => {
    render(<App propsToBeExposed={initProps} />);
    await waitFor(() => {
      expect(screen.getByLabelText('ecosystem-downtime-details')).toBeInTheDocument();
    });
  });

  test('should render error screen on screen if api fails', async () => {
    const props = {
      ...initProps,
      state: {
        ...initProps.state,
        isPreviousDowntimesError: true,
      },
    };
    render(<App propsToBeExposed={props} />);
    await waitFor(() => {
      expect(screen.getByTestId('ecosystem-health-error')).toHaveTextContent(
        'Something went wrong',
      );
    });
  });

  test('should render correct on screen', async () => {
    render(<App propsToBeExposed={initProps} />);
    await waitFor(() => {
      expect(screen.getByLabelText('ecosystem-downtime-details-header')).toHaveTextContent('VISA');
    });
  });

  test('should render correct header on screen', async () => {
    render(<App propsToBeExposed={initProps} />);
    await waitFor(() => {
      expect(screen.getByLabelText('ecosystem-downtime-details-header')).toHaveTextContent('VISA');
    });
  });

  test('should render other downtime info on screen', async () => {
    const props = {
      ...initProps,
      state: {
        ...initProps.state,
        previousDowntimes: processPreviousDowntimes(previous_downtimes_mock.data),
      },
    };
    render(<App propsToBeExposed={props} />);
    await waitFor(() => {
      const values = screen.getAllByLabelText('summary-tile-value');
      expect(values[0].textContent).toBe('0');

      expect(screen.getByLabelText('downtime-details-current-status')).toHaveTextContent(
        'Operational',
      );
    });
  });

  test('should render spinner while loading previous downtimes', () => {
    const props = {
      ...initProps,
      state: {
        ...initProps.state,
        isPreviousDowntimesFetching: true,
        isPreviousDowntimesLoading: true,
      },
    };
    render(<App propsToBeExposed={props} />);
    expect(screen.getByLabelText('ecosystem-downtimes-refresh-spinner')).toBeInTheDocument();
  });

  test('should render error screen while if endpoint fails', () => {
    const props = {
      ...initProps,
      state: {
        ...initProps.state,
        isPreviousDowntimesError: true,
      },
    };
    render(<App propsToBeExposed={props} />);
    expect(screen.getByTestId('ecosystem-health-error')).toBeInTheDocument();
  });
});
