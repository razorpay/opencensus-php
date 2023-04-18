import React from 'react';
import { screen, render } from 'test-utils';
import { processPreviousDowntimes } from 'merchant/views/EcosystemDowntimes/helpers';
import { previous_downtimes_mock } from 'merchant/views/EcosystemDowntimes/__tests__/mocks/mockResponses';
import DowntimeHistory from 'merchant/views/EcosystemDowntimes/components/DowntimeHistory';
import { processPreviousDowntimeData } from 'merchant/views/EcosystemDowntimes/containers/DowntimeDetailsContainer';

const instrument = {
  logo: '',
  name: 'VISA',
  key: 'VISA',
  method: 'card',
  group: 'network',
};

describe('<DowntimeHistory/>', () => {
  test('should render downtime-history on screen', () => {
    render(<DowntimeHistory pastDowntimes={undefined} isMobile={false} />);
    expect(screen.getByLabelText('downtime-history')).toBeInTheDocument();
  });

  test('should render No previous downtimes if no past downtimes', () => {
    render(<DowntimeHistory pastDowntimes={undefined} isMobile={false} />);
    expect(screen.getByLabelText('downtime-history-data')).toHaveTextContent(
      'No previous downtimes',
    );
  });

  test('should render timeline chart if past downtime exists', () => {
    const previousDowntimesMock = processPreviousDowntimes(previous_downtimes_mock.data);
    const { method, group, key } = instrument;
    const pastDowntimesForInstrument =
      previousDowntimesMock?.[method]?.[group]?.[key].previousDowntimes;

    const processedDowntimesForTimeline = processPreviousDowntimeData(pastDowntimesForInstrument);

    render(<DowntimeHistory pastDowntimes={processedDowntimesForTimeline} isMobile={false} />);
    expect(screen.getByLabelText('timeline')).toHaveTextContent(
      '01 Feb 12:16 to 01 Feb 12:18 (2mins)',
    );
  });
});
