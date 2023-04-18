import React from 'react';
import { screen, render } from 'test-utils';
import {
  getDowntimesAfterTimestamp,
  processPreviousDowntimes,
} from 'merchant/views/EcosystemDowntimes/helpers';
import { previous_downtimes_mock } from 'merchant/views/EcosystemDowntimes/__tests__/mocks/mockResponses';
import DowntimeTiles from 'merchant/views/EcosystemDowntimes/components/DowntimeTiles';
import moment from 'moment';

const instrument = {
  logo: '',
  name: 'VISA',
  key: 'VISA',
  method: 'card',
  group: 'network',
};

const initState = {
  activeDowntimes: {},
  previousDowntimes: processPreviousDowntimes(previous_downtimes_mock.data),
};

const App = ({ summaryFields }): JSX.Element => {
  const { key, method, group } = instrument;
  const { activeDowntimes, previousDowntimes } = initState;
  const activeDowntimesForInstrument = activeDowntimes?.[method]?.[group]?.[key];
  const pastDowntimes = previousDowntimes?.[method]?.[group]?.[key]?.previousDowntimes;
  return (
    <DowntimeTiles
      activeDowntime={activeDowntimesForInstrument}
      pastDowntimes={pastDowntimes}
      isMobile={false}
      summaryFields={summaryFields}
    />
  );
};

describe('<DowntimeTiles/>', () => {
  beforeEach(() => {
    jest.useFakeTimers('modern').setSystemTime(new Date(2023, 1, 3));
  });

  test('should render with correct value and description in summary tile', () => {
    const summaryFields = [
      {
        name: 'downtimeInLast7days',
        description: 'Downtime Duration',
        information: '(last 7 days)',
        value: (params) =>
          getDowntimesAfterTimestamp({
            ...params,
            timestamp: moment(new Date()).subtract(7, 'days').unix(),
          })?.totalDuration,
      },
    ];
    render(<App summaryFields={summaryFields} />);
    expect(screen.getByLabelText('summary-tile-value')).toHaveTextContent('2mins');
  });

  test('should render with correct value and description in summary tile', () => {
    const summaryFields = [
      {
        name: 'downtimeInLast24hrs',
        description: 'Downtime Duration',
        information: '(last 24 hours)',
        value: (params) =>
          getDowntimesAfterTimestamp({
            ...params,
            timestamp: moment(new Date()).subtract(24, 'hours').unix(),
          })?.totalDuration,
      },
    ];
    render(<App summaryFields={summaryFields} />);
    expect(screen.getByLabelText('summary-tile-value')).toHaveTextContent('0');
  });
});
