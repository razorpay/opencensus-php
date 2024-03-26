import React from 'react';

import StatsOverview from 'merchant/views/RiskAndFraud/RiskAnalytics/StatsOverview';
import { STATS_CONFIG_MAPPING } from 'merchant/views/RiskAndFraud/RiskAnalytics/StatsOverview/constants';
import { StatsOverviewProps } from 'merchant/views/RiskAndFraud/RiskAnalytics/StatsOverview/types';
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

import { mockFraudStats, mockRatios } from './mocks';

describe('StatsOverview', () => {
  let mockProps: StatsOverviewProps;
  const {
    total_payment_amount,
    entity_payment_amount,
    total_entity_count,
    total_payment_count,
    entity_payment_ratio,
  } = mockFraudStats;

  beforeEach(() => {
    mockProps = {
      isLoading: false,
      entity: FRAUD,
      metric: DEFAULT_METRIC,
      ratios: mockRatios,
      stats: mockFraudStats,
    };
  });

  afterEach(() => {
    jest.clearAllMocks();
  });

  const renderComponent = (props: Partial<StatsOverviewProps> = {}) => {
    const mergedProps = { ...mockProps, ...props };
    return render(<StatsOverview {...mergedProps} />);
  };

  const assertLabelsAndPosition = (entity: AnalyticsEntity, metric: MetricOptions) => {
    renderComponent({ entity, metric });
    const renderedLabels = STATS_CONFIG_MAPPING[entity][metric].map(({ label }) => label);
    const actualLabels = screen.getAllByTestId('stats-label').map((element) => element.textContent);
    expect(actualLabels).toEqual(renderedLabels);
  };

  test('should render StatsOverview without error', () => {
    renderComponent();
    expect(screen.getByTestId('stats-overview')).toBeVisible();
  });

  test('renders without children when entity and metric are undefined', () => {
    renderComponent({ entity: undefined, metric: undefined });
    const statsContainer = screen.getByTestId('stats-overview');
    expect(statsContainer).toBeInTheDocument();
    expect(statsContainer).toBeEmptyDOMElement();
  });

  test.each([
    [FRAUD, METRIC_VALUE],
    [FRAUD, METRIC_COUNT],
    [DISPUTES, METRIC_VALUE],
    [DISPUTES, METRIC_COUNT],
    [RISK_DECLINED, METRIC_VALUE],
    [RISK_DECLINED, METRIC_COUNT],
  ])('assert labels and position respective to entity %s and metric %s', (entity, metric) => {
    assertLabelsAndPosition(entity as AnalyticsEntity, metric as MetricOptions);
  });

  test('should render labels, value and threshold text entity fraud and metric amount', () => {
    renderComponent({ entity: FRAUD, metric: METRIC_VALUE });

    const totalPaymentAmountElement = screen.getByTestId('total_payment_amount');
    expect(totalPaymentAmountElement).toHaveTextContent(
      `₹${total_payment_amount.value}.${total_payment_amount.decimal}`,
    );

    const entityPaymentAmountElement = screen.getByTestId('entity_payment_amount');
    expect(entityPaymentAmountElement).toHaveTextContent(
      `₹${entity_payment_amount.value}.${entity_payment_amount.decimal}`,
    );

    const entityPaymentRatioElement = screen.getByTestId('entity_payment_ratio');
    expect(entityPaymentRatioElement).toHaveTextContent(`${entity_payment_ratio}%`);

    const entityRatioThreshold = screen.getByTestId('entity_ratio_threshold');
    expect(entityRatioThreshold).toHaveTextContent(/Higher than industry average/);

    const totalEntityCountElement = screen.getByTestId('total_entity_count');
    expect(totalEntityCountElement).toHaveTextContent(`${total_entity_count}`);
  });

  test('should render labels, value and threshold text entity fraud and metric count', () => {
    renderComponent({ entity: FRAUD, metric: METRIC_COUNT });

    const totalPaymentAmountElement = screen.getByTestId('total_payment_count');
    expect(totalPaymentAmountElement).toHaveTextContent(`${total_payment_count}`);

    const totalEntityCountElement = screen.getByTestId('total_entity_count');
    expect(totalEntityCountElement).toHaveTextContent(`${total_entity_count}`);

    const entityPaymentRatioElement = screen.getByTestId('entity_payment_ratio');
    expect(entityPaymentRatioElement).toHaveTextContent(`${entity_payment_ratio}%`);

    const entityRatioThreshold = screen.getByTestId('entity_ratio_threshold');
    expect(entityRatioThreshold).toHaveTextContent(/Higher than industry average/);

    const entityPaymentAmountElement = screen.getByTestId('entity_payment_amount');
    expect(entityPaymentAmountElement).toHaveTextContent(
      `₹${entity_payment_amount.value}.${entity_payment_amount.decimal}`,
    );
  });

  test('should not render threshold text', () => {
    renderComponent({
      entity: FRAUD,
      metric: METRIC_VALUE,
      stats: { ...mockFraudStats, entity_payment_ratio: '0.0023' },
    });

    const entityPaymentRatioElement = screen.getByTestId('entity_payment_ratio');
    expect(entityPaymentRatioElement).toHaveTextContent('0.0023%');

    const entityRatioThreshold = screen.queryByTestId('entity_ratio_threshold');
    expect(entityRatioThreshold).toBeNull();
  });

  test('should render dividers', () => {
    renderComponent({ entity: FRAUD, metric: METRIC_VALUE });
    const dividers = screen.getAllByTestId('stats-divider');
    expect(dividers.length).toBe(3);
  });

  test('should render dividers', () => {
    renderComponent({ entity: undefined, metric: undefined });
    const dividers = screen.queryAllByTestId('stats-divider');
    expect(dividers.length).toBe(0);
  });

  test('should render StatsLoader', () => {
    renderComponent({ isLoading: true });
    const loaders = screen.queryAllByTestId('loading-shimmer');
    expect(loaders.length).toBe(4);
  });
});
