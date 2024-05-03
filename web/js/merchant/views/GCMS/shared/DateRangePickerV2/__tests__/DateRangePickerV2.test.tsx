import 'react-dates/initialize';
import React from 'react';
import { screen, waitFor } from '@testing-library/react';

import { DATE_RANGE_PRESETS } from 'merchant/views/GCMS/shared/constants';
import { GCMSTestPageRenderer } from 'merchant/views/GCMS/shared/test-utils';
import { render, userEvent } from 'test-utils';

import DateRangePickerV2 from '../DateRangePickerV2';
import { generatePresets } from '../utils';

const variantOn = { razorpay_gcms: { variables: { result: 'on' } } };

const defaultAbExperiments = variantOn;
const mockAbExperiments = defaultAbExperiments;

jest.mock('common/splitz', () => ({
  useSplitzService: () => ({ abExperiments: mockAbExperiments }),
}));
const allTimePresetName = DATE_RANGE_PRESETS[0][0];

const selectedPreset = {
  name: allTimePresetName,
  value: DATE_RANGE_PRESETS[0][1],
};
const presetsForCalendar = generatePresets(DATE_RANGE_PRESETS);
const presetsToShow = presetsForCalendar;
const renderOrders = () => {
  render(
    <GCMSTestPageRenderer>
      <DateRangePickerV2
        presets={presetsToShow}
        setSelectedPreset={() => {}}
        onDatesChange={() => {}}
        selectedPreset={selectedPreset}
      />
    </GCMSTestPageRenderer>,
  );
};

describe('GCMS: Filters: DateRangePickerV2', () => {
  it('should render DateRangePickerV2', async () => {
    renderOrders();

    await waitFor(() => {
      expect(screen.getByText('All Time')).toBeInTheDocument();
    });
  });

  it('should show Date range Select Dropdown menu when DateRangePickerV2 clicked', async () => {
    renderOrders();

    await userEvent.click(screen.getByText('All Time'));
    await waitFor(() => {
      expect(screen.getAllByText('All Time')).toHaveLength(2);
      expect(screen.getByText('Past 7 Days')).toBeInTheDocument();
      expect(screen.getByText('Past 30 Days')).toBeInTheDocument();
      expect(screen.getByText('Past 90 Days')).toBeInTheDocument();
      expect(screen.getByText('Custom Range')).toBeInTheDocument();
    });
  });
});
