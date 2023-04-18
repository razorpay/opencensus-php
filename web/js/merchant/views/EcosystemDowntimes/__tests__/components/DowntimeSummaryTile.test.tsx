import React from 'react';
import { screen, render } from 'test-utils';
import DowntimeSummaryTile from 'merchant/views/EcosystemDowntimes/components/DowntimeSummaryTile';

const initProps = {
  description: 'Test Description',
  subText: 'Test subtext',
  value: 10,
  isMobile: false,
};

describe('<DowntimeSummaryTile/>', () => {
  test('should render description and subtext on screen', () => {
    render(<DowntimeSummaryTile {...initProps} />);
    expect(screen.getByLabelText('summary-tile-description')).toHaveTextContent('Test Description');
  });

  test('should render value on screen', () => {
    render(<DowntimeSummaryTile {...initProps} />);
    expect(screen.getByLabelText('summary-tile-value')).toHaveTextContent('10');
  });
});
