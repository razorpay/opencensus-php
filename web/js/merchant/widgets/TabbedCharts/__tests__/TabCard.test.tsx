import React from 'react';

import TabCard from 'merchant/widgets/TabbedCharts/TabCard';
import { render, screen } from 'test-utils';

import { TABBED_CHARTS_MOCKED_RESPONSE } from './mocks';

describe('widgets->TabbedCharts->TabCard', () => {
  it('should display amount as 0', () => {
    const tabChartResponse = TABBED_CHARTS_MOCKED_RESPONSE.components[0];
    render(
      <TabCard
        isActive={true}
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
});
