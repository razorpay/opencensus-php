import React, { useEffect, useMemo, useState } from 'react';
import { Box, Button, TextInput, Text } from '@razorpay/blade/components';
import qs from 'query-string';

import { DATE_RANGE_PRESETS } from 'merchant/views/GCMS/Funds/constants';
import { trackBrandFilterCleared, trackBrandFilterClicked } from 'merchant/views/GCMS/Funds/events';

import { StyledFilterDiv } from './StyledDiv';
import DateRangePickerV2, { generatePresets } from '../../shared/DateRangePickerV2';

interface BrandAccountFiltersProps {
  onSearch: ({ date, referenceId }) => void;
}

export const presetsForCalendar = generatePresets(DATE_RANGE_PRESETS);
const allTimePresetName = DATE_RANGE_PRESETS[0][0];
const getEmptyDate = () => ({ from: '', to: '' });

const BrandAccountFilters = ({ onSearch }: BrandAccountFiltersProps) => {
  const [selectedPreset, setSelectedPreset] = useState({
    name: allTimePresetName,
    value: DATE_RANGE_PRESETS[0][1],
  });
  const [date, setDate] = useState(getEmptyDate());
  const [referenceId, setReferenceId] = useState('');

  const isAllTimeFilter = selectedPreset.name === allTimePresetName;

  const onDatesChange = (from, to) => {
    if (selectedPreset.name === allTimePresetName) {
      setDate(getEmptyDate());
    } else {
      setDate({
        from: from.unix(),
        to: to.unix(),
      });
    }
  };

  const onSelectPreset = (preset: typeof selectedPreset) => {
    if (preset.name === allTimePresetName) {
      setDate(getEmptyDate());
    }
    setSelectedPreset(preset);
  };

  useEffect(() => {
    // To pick from and to date params from query params on mount
    const queryParams = qs.parse(location.search);
    if (!(queryParams.from && queryParams.to) && !!queryParams.status) {
      setSelectedPreset(presetsForCalendar[2]);
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  const presetsToShow = useMemo(() => {
    return presetsForCalendar;
  }, []);

  const onClear = () => {
    setSelectedPreset(presetsForCalendar[0]);
    setReferenceId('');
    onSearch({ date: { from: '', to: '' }, referenceId: '' });
    trackBrandFilterCleared();
  };

  const handleReferenceIdChange = (value) => {
    setReferenceId(value);
  };

  const handleSearch = () => {
    onSearch({ date, referenceId });
    trackBrandFilterClicked({
      referenceId,
      durationStartDate: date.to,
      durationEndDate: date.from,
    });
  };

  return (
    <StyledFilterDiv>
      <div className={`gcms-funds-filter-group ${isAllTimeFilter && 'all-time-filter-selected'}`}>
        <Box paddingY="spacing.4" display="flex">
          <div className="form-group gcms-list-filter-item">
            <TextInput
              label="Reference Id"
              labelPosition="top"
              name="reference_id"
              onChange={(e) => {
                handleReferenceIdChange(e.value);
              }}
              showClearButton
              type="url"
              validationState="none"
              testID="reference_id"
              value={referenceId}
            />
          </div>
          <div className="form-group datepicker-group">
            <Text
              size="small"
              weight="semibold"
              color="surface.text.gray.subtle"
              marginBottom="spacing.3"
            >
              Order Date
            </Text>
            <DateRangePickerV2
              presets={presetsToShow}
              setSelectedPreset={onSelectPreset}
              onDatesChange={onDatesChange}
              selectedPreset={selectedPreset}
            />
          </div>

          <div className="list-filter-item btn-toolbar">
            <Button
              color="primary"
              onClick={handleSearch}
              size="medium"
              type="button"
              variant="primary"
            >
              Search
            </Button>
            <Button
              color="primary"
              onClick={onClear}
              size="medium"
              type="button"
              variant="tertiary"
              marginLeft="spacing.3"
            >
              Clear
            </Button>
          </div>
        </Box>
      </div>
    </StyledFilterDiv>
  );
};

export default BrandAccountFilters;
