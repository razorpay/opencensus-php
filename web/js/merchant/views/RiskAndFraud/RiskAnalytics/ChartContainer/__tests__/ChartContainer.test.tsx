import React from 'react';

import { render, screen } from 'test-utils';

import { FRAUD, DEFAULT_METRIC } from '../../constants';
import ChartContainer from '../ChartContainer';
import { DEFAULT_CHART_OPTIONS } from '../constants';
import { ChartContainerProps, SelectedGraphOption } from '../types';

jest.mock('react-chartjs-2', () => ({
  Bar: jest.fn(() => <div data-testid="mocked-bar-chart" />),
  Line: jest.fn(() => <div data-testid="mocked-line-chart" />),
}));

describe('ChartContainer', () => {
  const mockProps: ChartContainerProps = {
    isLoading: false,
    isError: false,
    entity: FRAUD,
    dateRange: {
      startDate: null,
      endDate: null,
      preset: { label: 'Last 2 weeks', value: '14d', duration: 14, unit: 'days' },
    },
    selectedInterval: 'day',
    metric: DEFAULT_METRIC,
    graphOptions: DEFAULT_CHART_OPTIONS[FRAUD] as SelectedGraphOption[],
    queryData: [],
    handleInterval: jest.fn(),
  };

  const renderComponent = (props = {}) => render(<ChartContainer {...mockProps} {...props} />);

  test('should render ChartContainer without error', () => {
    renderComponent();
    const chartContainerComponent = screen.getByTestId('chart-container');
    expect(chartContainerComponent).toBeInTheDocument();
  });

  test('renders ChartInterval and ChartLegend', () => {
    renderComponent();
    const chartInterval = screen.getByTestId('chart-interval');
    expect(chartInterval).toBeInTheDocument();
    const chartLegend = screen.getByTestId('chart-legends');
    expect(chartLegend).toBeInTheDocument();
  });

  test.each([
    { prop: 'isLoading', expectedComponent: 'chart-loader' },
    { prop: 'isError', expectedComponent: 'chart-error' },
  ])('renders $expectedComponent when $prop is true', ({ prop, expectedComponent }) => {
    renderComponent({ [prop]: true });
    const component = screen.getByTestId(expectedComponent);
    expect(component).toBeInTheDocument();
  });
});
