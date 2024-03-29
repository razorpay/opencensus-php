import React from 'react';

import { render, screen } from 'test-utils';
import { TABBED_CHARTS_MOCKED_RESPONSE } from './mocks';
import TabCard from '../TabCard';

describe('widgets->TabbedCharts->TabCard', () => {
  it('should display amount as 0 if tab position is zero', () => {
    const tabChartResponse = TABBED_CHARTS_MOCKED_RESPONSE.components[0];
    render(
      <TabCard
        isActive={true}
        cardPosition={0}
        tabData={{
          ...tabChartResponse,
          data: {
            ...tabChartResponse.data,
            value: 0,
          },
        }}
      />,
    );
    expect(screen.getByText('0')).toBeInTheDocument();
    expect(screen.queryByText(tabChartResponse.data.sub_text)).not.toBeInTheDocument();
  });

  it('should display amount as -- if tab position is not zero', () => {
    const tabChartResponse = TABBED_CHARTS_MOCKED_RESPONSE.components[0];
    render(
      <TabCard
        isActive={true}
        cardPosition={1}
        tabData={{
          ...tabChartResponse,
          data: {
            ...tabChartResponse.data,
            value: 0,
          },
        }}
      />,
    );
    expect(screen.getByText('--')).toBeInTheDocument();
    expect(screen.queryByText(tabChartResponse.data.sub_text)).not.toBeInTheDocument();
  });
});
