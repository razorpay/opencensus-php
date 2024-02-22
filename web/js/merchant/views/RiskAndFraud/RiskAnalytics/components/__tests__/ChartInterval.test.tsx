import React from 'react';

import ChartInterval, {
  ChartIntervalProps,
} from 'merchant/views/RiskAndFraud/RiskAnalytics/components/ChartInterval';
import { render, screen, userEvent } from 'test-utils';

describe('ChartInterval', () => {
  const handleIntervalMock = jest.fn();
  const defaultProps: ChartIntervalProps = {
    isLoading: false,
    dateRange: {
      startDate: null,
      endDate: null,
      preset: { label: 'Last 2 weeks', value: '14d', duration: 14, unit: 'days' },
    },
    selectedInterval: 'day',
    handleInterval: handleIntervalMock,
  };

  const renderComponent = (props = {}) => {
    const mergedProps = { ...defaultProps, ...props };
    render(<ChartInterval {...mergedProps} />);
  };

  beforeEach(() => {
    handleIntervalMock.mockClear();
  });

  test('renders ChartInterval component', () => {
    renderComponent();
    const chartIntervalComponent = screen.getByTestId('chart-interval');
    expect(chartIntervalComponent).toBeInTheDocument();
  });

  test('renders RadioButtonGroup with correct options and selected value', () => {
    renderComponent();
    const radioButtons = screen.getAllByRole('radio', { hidden: true }) as HTMLInputElement[];
    expect(radioButtons.length).toBe(2);
    expect(radioButtons[0].value).toEqual('day');
    expect(radioButtons[1].value).toEqual('week');
    expect(screen.getByLabelText('Daily')).toBeChecked();
  });

  test('disable button when disabled is true', () => {
    renderComponent();
    const secondOption = screen.getByLabelText('Weekly');
    expect(secondOption).toBeDisabled();
  });

  test('handles interval change and triggers handleInterval', async () => {
    renderComponent({
      dateRange: {
        preset: { label: 'Last 1 month', value: '30d', duration: 30, unit: 'days' },
      },
    });
    const secondOption = screen.getByLabelText('Weekly');
    await userEvent.click(secondOption);
    expect(handleIntervalMock).toHaveBeenCalledWith('week');
    expect(secondOption).toBeChecked();
  });

  test('disables RadioButtonGroup when isLoading is true', () => {
    renderComponent({ isLoading: true });
    const radioButtons = screen.getAllByRole('radio', { hidden: true }) as HTMLInputElement[];
    expect(radioButtons.every((button) => button.disabled)).toBe(true);
  });

  test('renders correct options and handles default case', () => {
    renderComponent({
      dateRange: {
        preset: { label: 'Custom Range', value: 'invalid', duration: 7, unit: 'days' },
      },
    });
    const radioButtons = screen.getAllByRole('radio', { hidden: true }) as HTMLInputElement[];
    expect(radioButtons.length).toBe(2);
    expect(radioButtons[0].value).toEqual('day');
    expect(radioButtons[1].value).toEqual('week');
    expect(screen.getByLabelText('Daily')).toBeChecked();
    expect(screen.getByLabelText('Weekly')).toBeDisabled();
  });
});
