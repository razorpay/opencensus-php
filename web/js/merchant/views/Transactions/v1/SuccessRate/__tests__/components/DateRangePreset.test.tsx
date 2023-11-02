import React from 'react';
import { screen, render, userEvent, waitFor } from 'test-utils';
import { PRESETS as DATE_PRESETS } from 'merchant/views/Transactions/v1/SuccessRate/constants';
import moment from 'moment';
import { DateRangePreset } from 'merchant/views/Transactions/v1/SuccessRate/components/DateRangePreset';

jest.mock('react-datetime', () => {
  const OriginalModule = jest.requireActual('react-datetime');
  return (props) => {
    const newProps = { ...props };
    newProps.inputProps.readOnly = false;
    return <OriginalModule {...newProps} />;
  };
});

describe('<DateRangePreset/>', () => {
  const NOW = moment();
  const initProps = {
    presets: DATE_PRESETS,
    dateRange: {
      preset: { label: 'Last 6 Hours', name: '6h', value: 6, unit: 'hours' },
      endDate: NOW,
      startDate: NOW.clone().subtract(6, 'hour').startOf('hour'),
    },
    onPresetChange: jest.fn(),
    setDateRange: jest.fn(),
  };

  const renderApp = (props = initProps) => {
    render(<DateRangePreset {...props} />);
  };
  test('should render date range filter component on screen', () => {
    renderApp();
    expect(screen.getByTestId('sr-dashboard-date-filters')).toBeVisible();
  });

  test('should render date range presets on screen', async () => {
    renderApp();
    await userEvent.click(screen.getByRole('combobox') as HTMLElement);
    await waitFor(() => expect(screen.getByText('Last 24 Hours')).toBeVisible());
    expect(screen.getByText('Last 7 Days')).toBeVisible();
    expect(screen.getByText('Last 14 Days')).toBeVisible();
    expect(screen.getByText('Last 30 Days')).toBeVisible();
    expect(screen.getByText('Last 60 Days')).toBeVisible();
    expect(screen.getByText('Last 90 Days')).toBeVisible();
    expect(screen.getByText('Custom Range')).toBeVisible();
  });

  test('should fire callback with correct preset on click', async () => {
    renderApp();
    await userEvent.click(screen.getByRole('combobox') as HTMLElement, {
      pointerEventsCheck: 0,
    });

    await userEvent.click(screen.getByText('Last 24 Hours'));
    await waitFor(() => {
      expect(initProps.onPresetChange).toHaveBeenCalledWith({
        end: NOW.clone().endOf('hour'),
        option: {
          label: 'Last 24 Hours',
          name: '24h',
          value: 24,
          unit: 'hours',
        },
        start: NOW.clone().subtract(24, 'hour').startOf('hour'),
      });
    });
  });

  test('should render custom date range inputs on screen', () => {
    renderApp();
    expect(screen.getByTestId('sr-dashboard-custom-date-input-startDate')).toBeVisible();
    expect(screen.getByTestId('sr-dashboard-custom-date-input-endDate')).toBeVisible();
  });

  test('should render custom date range inputs on screen', async () => {
    const mockDate = '23-06-2023 | 12 PM';
    const isoDate = moment(mockDate, 'DD-MM-YYYY | h A');
    const customPresetObj = {
      label: 'Custom Range',
      name: 'custom',
      unit: '',
      value: 0,
    };

    renderApp();
    const startDateEl = screen.getByTestId('sr-dashboard-custom-date-input-startDate').firstChild
      ?.firstChild as HTMLInputElement;

    const endDateEl = screen.getByTestId('sr-dashboard-custom-date-input-endDate').firstChild
      ?.firstChild as HTMLInputElement;

    expect(startDateEl).toBeVisible();

    startDateEl.focus();
    await userEvent.clear(startDateEl);
    await userEvent.paste(mockDate);

    await waitFor(() => {
      expect(initProps.setDateRange).toHaveBeenCalledWith({
        endDate: expect.any(Object),
        preset: customPresetObj,
        startDate: expect.objectContaining(isoDate),
      });
    });

    endDateEl.focus();
    await userEvent.clear(endDateEl);
    await userEvent.paste(mockDate);

    await waitFor(() => {
      expect(initProps.setDateRange).toHaveBeenCalledWith({
        endDate: expect.objectContaining(isoDate),
        preset: customPresetObj,
        startDate: expect.any(Object),
      });
    });
  });
});
