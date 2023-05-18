import React from 'react';
import { render, screen } from 'test-utils';
import DowntimeDetailsCurrentStatus from 'merchant/views/EcosystemDowntimes/components/DowntimeDetailsCurrentStatus';
import { high_sev_downtime_mock } from 'merchant/views/EcosystemDowntimes/__tests__/mocks/mockResponses';

const instrument = {
  key: 'VISA',
  name: 'Visa',
  logo: '',
  method: 'card',
  srKey: 'card.network.Visa',
  group: 'network',
};

describe('<DowntimeDetailsCurrentStatus/>', () => {
  test('should render DowntimeDetailsCurrentStatus with operational status', () => {
    const props = {
      instrument,
      activeDowntime: undefined,
      isMobile: false,
    };
    const { getByText } = render(<DowntimeDetailsCurrentStatus {...props} />);
    expect(getByText('Operational')).toBeInTheDocument();

    expect(screen.getByTestId('status-description-text')).toHaveTextContent(
      'Visa (Cards) is operational',
    );
  });

  test('should render DowntimeDetailsCurrentStatus with high sev status', () => {
    const props = {
      instrument,
      activeDowntime: high_sev_downtime_mock,
      isMobile: false,
    };
    render(<DowntimeDetailsCurrentStatus {...props} />);
    expect(screen.getByLabelText('downtime-details-current-status')).toHaveTextContent(
      'Ongoing High Severity Downtime',
    );
  });
});
