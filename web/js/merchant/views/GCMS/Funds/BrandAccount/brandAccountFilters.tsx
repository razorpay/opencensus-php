import React, { useEffect, useMemo, useState } from 'react';
import { Box } from '@razorpay/blade/components';
import qs from 'query-string';

import DateRangePickerV2, { generatePresets } from 'common/ui/DateRangePickerV2';
import { DATE_RANGE_PRESETS } from 'merchant/views/GCMS/Funds/constants';
import { trackBrandFilterCleared, trackBrandFilterClicked } from 'merchant/views/GCMS/Funds/events';

import { StyledFilterDiv } from './StyledDiv';

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
          <div className="form-group list-filter-item">
            <label>Reference Id</label>
            <input
              name="reference_id"
              className="form-control input-sm"
              data-testid="reference_id"
              value={referenceId}
              onChange={(e) => {
                handleReferenceIdChange(e.target.value);
              }}
            />
          </div>
          <div className="form-group datepicker-group">
            <label>Duration</label>
            <DateRangePickerV2
              presets={presetsToShow}
              setSelectedPreset={onSelectPreset}
              onDatesChange={onDatesChange}
              selectedPreset={selectedPreset}
            />
          </div>

          <div className="list-filter-item btn-toolbar">
            <button className="btn btn-primary btn-sm" onClick={handleSearch}>
              Search
            </button>
            <button className="btn btn-sm btn-text" onClick={onClear}>
              Clear
            </button>
          </div>
        </Box>
      </div>
    </StyledFilterDiv>
  );
};

export default BrandAccountFilters;
