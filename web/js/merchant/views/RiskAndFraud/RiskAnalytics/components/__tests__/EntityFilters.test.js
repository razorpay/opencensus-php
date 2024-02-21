import moment from 'moment';

import { EntityFilters } from 'merchant/views/RiskAndFraud/RiskAnalytics/components';
import {
  FRAUD,
  DISPUTES,
  RISK_DECLINED,
  DEFAULT_METRIC,
  DEFAULT_CHART_OPTIONS,
  METRIC_OPTIONS,
  TOTAL_SALES_VALUE,
  VALUE_OF_REPORTED_ENTITY,
  ENTITY_RATIO,
} from 'merchant/views/RiskAndFraud/RiskAnalytics/constants';
import { render, screen, userEvent } from 'test-utils';

const entitiesToTest = [FRAUD, DISPUTES, RISK_DECLINED];

const handleDurationChange = jest.fn();
const handleMetricChange = jest.fn();
const handleGraphOptions = jest.fn();

describe('EntityFilters', () => {
  const defaultProps = {
    entity: FRAUD,
    dateRange: { preset: { label: 'Last 2 weeks', value: '14d', duration: 14, unit: 'days' } },
    metric: DEFAULT_METRIC,
    graphOptions: DEFAULT_CHART_OPTIONS[FRAUD],
    handleDurationChange,
    handleMetricChange,
    handleGraphOptions,
  };

  const renderComponent = (props = {}) => render(<EntityFilters {...defaultProps} {...props} />);

  beforeEach(() => {
    handleDurationChange.mockReset();
    handleMetricChange.mockReset();
    handleGraphOptions.mockReset();
  });

  entitiesToTest.forEach((entity) => {
    test(`should render entity filter options for ${entity}`, () => {
      renderComponent({ entity });
      const durationCombobox = screen.getByRole('combobox', { name: 'Duration' });
      const metricCombobox = screen.getByRole('combobox', { name: 'Metric' });
      const graphOptionsCombobox = screen.queryByRole('combobox', { name: 'Graph Options' });

      expect(durationCombobox).toBeInTheDocument();
      expect(metricCombobox).toBeInTheDocument();

      if (entity !== RISK_DECLINED) {
        expect(graphOptionsCombobox).toBeInTheDocument();
      } else if (entity === RISK_DECLINED) {
        expect(graphOptionsCombobox).toBeNull();
      }
    });
  });

  test('should not render graph options for risk declined', () => {
    renderComponent({ entity: RISK_DECLINED });
    expect(screen.queryByRole('combobox', { name: 'Graph Options' })).toBeNull();
  });

  test('should render duration with preset value', () => {
    renderComponent();
    const select = screen.getByRole('combobox', { name: 'Duration' });
    expect(select.value).toEqual('Last 2 weeks');
  });

  test('should render duration with selected preset value', async () => {
    renderComponent();
    const select = screen.getByRole('combobox', { name: 'Duration' });
    expect(select).toBeInTheDocument();
    await userEvent.click(select);
    await userEvent.click(screen.getByRole('option', { name: 'Last 1 month' }));
    const currentDate = moment().subtract(1, 'day');
    const expectedEndDate = moment(currentDate).startOf('day');
    const expectedStartDate = expectedEndDate.clone().subtract(30, 'days').startOf('day');
    expect(handleDurationChange).toHaveBeenCalledWith({
      startDate: expectedStartDate.unix(),
      endDate: expectedEndDate.unix(),
      preset: { label: 'Last 1 month', value: '30d', duration: 30, unit: 'days' },
    });
  });

  test('should render metric with default value', () => {
    renderComponent();
    const select = screen.getByRole('combobox', { name: 'Metric' });
    const defaultMetric = METRIC_OPTIONS.find((opt) => opt.value === DEFAULT_METRIC).label;
    expect(select.value).toEqual(defaultMetric);
  });

  test('should render metric with selected preset value', async () => {
    renderComponent();
    const select = screen.getByRole('combobox', { name: 'Metric' });
    expect(select).toBeInTheDocument();
    await userEvent.click(select);
    await userEvent.click(screen.getByRole('option', { name: 'absolute count' }));
    expect(handleMetricChange).toHaveBeenCalledWith('count');
  });

  test('should render metric with selected preset value', async () => {
    renderComponent();
    const select = screen.getByRole('combobox', { name: 'Graph Options' });
    expect(select).toBeInTheDocument();
    await userEvent.click(select);
    await userEvent.click(screen.getByRole('option', { name: 'Total sales value' }));
    expect(handleGraphOptions).toHaveBeenCalledWith([
      TOTAL_SALES_VALUE,
      VALUE_OF_REPORTED_ENTITY,
      ENTITY_RATIO,
    ]);
  });
});
