import React from 'react';

import * as analytics from 'common/utils/analytics';
import { high_sev_downtime_mock } from 'merchant/views/EcosystemDowntimes/__tests__/mocks/mockResponses';
import Instrument from 'merchant/views/EcosystemDowntimes/components/Instrument';
import { screen, userEvent, render } from 'test-utils';

const initProps = {
  index: 0,
  onClick: jest.fn(),
  instrument: {
    key: 'VISA',
    name: 'VISA',
    logo: 'sbi.png',
    group: 'network',
    method: 'card',
    srKey: 'card.network.Visa',
  },
};

describe('<Instrument/>', () => {
  test('should render instrumnet and with no downtime if status is empty', () => {
    const instrumentEl = render(<Instrument {...initProps} />);
    expect(instrumentEl.getByLabelText('status')).toHaveAttribute('title', 'Operational');
  });

  test('should render downtime with high sev if status of the instrument down with high severity', () => {
    const instrumentEl = render(<Instrument {...initProps} status={high_sev_downtime_mock} />);
    expect(instrumentEl.getByLabelText('status')).toHaveAttribute('title', 'High Severity');
  });

  test('should fire onClick callback on clicking on instrument', async () => {
    render(<Instrument {...initProps} status={high_sev_downtime_mock} />);
    await userEvent.click(screen.getByLabelText('instrument'));
    expect(initProps.onClick.mock.calls).toHaveLength(1);
  });

  test('should fire "InstrumentClick" event on click', async () => {
    const analyticsTrackSpy = jest.spyOn(analytics, 'analyticsTrack');
    render(<Instrument {...initProps} status={high_sev_downtime_mock} />);
    await userEvent.click(screen.getByLabelText('instrument'));
    expect(analyticsTrackSpy).toHaveBeenCalledWith({
      objectName: `Ecosystem Health Page - Instrument`,
      actionName: 'Click',
      screen: 'Transactions - Ecosystem Health',
      properties: {
        key: 'VISA',
        name: 'VISA',
        group: 'network',
        method: 'card',
      },
    });
  });
});
