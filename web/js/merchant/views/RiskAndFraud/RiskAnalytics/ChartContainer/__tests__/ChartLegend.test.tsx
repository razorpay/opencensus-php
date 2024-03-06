import React from 'react';

import ChartLegend from 'merchant/views/RiskAndFraud/RiskAnalytics/ChartContainer/ChartLegend';
import {
  CHART_OPTIONS_MAPPING,
  LEGEND_COLORS_MAPPING,
} from 'merchant/views/RiskAndFraud/RiskAnalytics/ChartContainer/constants';
import {
  FRAUD,
  DISPUTES,
  RISK_DECLINED,
  DEFAULT_METRIC,
  METRIC_VALUE,
  METRIC_COUNT,
} from 'merchant/views/RiskAndFraud/RiskAnalytics/constants';
import { AnalyticsEntity, MetricOptions } from 'merchant/views/RiskAndFraud/RiskAnalytics/types';
import { render, screen } from 'test-utils';

const entities = [FRAUD, DISPUTES, RISK_DECLINED];
const metrics = [METRIC_VALUE, METRIC_COUNT];

describe('ChartLegend', () => {
  const defaultProps = { entity: FRAUD, metric: DEFAULT_METRIC };

  const renderComponent = (props = {}) => render(<ChartLegend {...defaultProps} {...props} />);

  // Helper function to get expected legends based on entity and metric
  const getExpectedLegends = (entity: AnalyticsEntity, metric: MetricOptions) => {
    return CHART_OPTIONS_MAPPING[entity][metric].map((option) => option.label);
  };

  test('should render chart legend w/o error', () => {
    renderComponent();
    expect(screen.queryByTestId('chart-legends')).toBeVisible();
  });

  test('should render chart legend with appropriate bgColor and icon', () => {
    renderComponent();
    const colors = Object.values(LEGEND_COLORS_MAPPING);
    const indicators = screen.getAllByTestId('legend-indicator');

    indicators.forEach((el, index) => {
      expect(el).toBeInTheDocument();
      expect(el).toHaveStyle(`background-color: ${colors[index]}`);
    });
  });

  entities.forEach((entity) => {
    metrics.forEach((metric) => {
      test(`should render chart legend for each ${entity} and metric ${metric}`, () => {
        renderComponent({ entity, metric });
        const expectedLegends = getExpectedLegends(entity, metric);
        expectedLegends.forEach((legendText) => {
          const textRegex = new RegExp(`^${legendText}`);
          expect(screen.getByText(textRegex)).toBeInTheDocument();
        });
      });
    });
  });
});
