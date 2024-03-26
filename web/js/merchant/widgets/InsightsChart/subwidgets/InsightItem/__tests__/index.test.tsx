import React from 'react';

import { render, screen } from 'test-utils';
import InsightItem from '..';
import { INSIGHTS_CHART_MOCK_RESPONSE } from 'merchant/containers/Home/RTUX/__tests__/mocks';
import { InsightItemProps } from '../types';

describe('widgets->Insights Chart->Sub Widgets->Insight Item', () => {
  test('should render empty state', () => {
    // Getting handled in TS PR
    const insightItemProps = INSIGHTS_CHART_MOCK_RESPONSE
      .components[0] as unknown as InsightItemProps;
    render(
      <InsightItem
        {...{
          ...insightItemProps,
          data: { ...insightItemProps.data, value: 0, value_type: 'number' },
        }}
        date="today"
        isLoading={false}
        queryKey={[]}
        error={undefined}
      />,
    );
    expect(screen.queryByText(insightItemProps.data.sub_text)).not.toBeInTheDocument();
    expect(screen.getByText('0')).toBeInTheDocument();
  });
});
