import React, { useEffect, useMemo, useState } from 'react';
import { Box } from '@razorpay/blade/components';
import qs from 'query-string';

import DateRangePickerV2, { generatePresets } from 'common/ui/DateRangePickerV2';
import { DATE_RANGE_PRESETS, ORDERS_STATUS } from 'merchant/views/GCMS/shared/constants';

import { StyledFilterDiv } from './StyledDiv';
import { trackOrdersFiltersCleared, trackOrdersFiltersClicked } from './events';

interface OrdersFilterProps {
  onSearch: ({ date, status, resellerName, orderId }) => void;
  isResellerOrderFilter?: boolean;
}

export const presetsForCalendar = generatePresets(DATE_RANGE_PRESETS);
const allTimePresetName = DATE_RANGE_PRESETS[0][0];
const getEmptyDate = () => ({ from: '', to: '' });

const OrdersFilter = ({ onSearch, isResellerOrderFilter = false }: OrdersFilterProps) => {
  const [selectedPreset, setSelectedPreset] = useState({
    name: allTimePresetName,
    value: DATE_RANGE_PRESETS[0][1],
  });
  const [date, setDate] = useState(getEmptyDate());
  const [status, setStatus] = useState('all');
  const [resellerName, setResellerName] = useState('');
  const [orderId, setOrderId] = useState('');

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
  }, [status]);

  const onClear = () => {
    setSelectedPreset(presetsForCalendar[0]);
    setStatus('all');
    setResellerName('');
    setOrderId('');
    onSearch({ date: { from: '', to: '' }, status: 'all', resellerName: '', orderId: '' });
    trackOrdersFiltersCleared();
  };

  const handleStatusChange = (value) => {
    setStatus(value);
  };

  const handleResellerNameChange = (value) => {
    setResellerName(value);
  };

  const handleOrderIdChange = (value) => {
    setOrderId(value);
  };

  const handleSearch = () => {
    onSearch({ date, status, resellerName, orderId });
    trackOrdersFiltersClicked({
      orderId,
      resellerName,
      durationStartDate: date?.from,
      durationEndDate: date?.to,
      status,
    });
  };

  return (
    <StyledFilterDiv>
      <div className={`gcms-orders-filter-group ${isAllTimeFilter && 'all-time-filter-selected'}`}>
        <Box paddingY={'spacing.4'} display={'flex'}>
          <div className="form-group list-filter-item">
            <label>Order Id</label>
            <input
              name="order_id"
              className="form-control input-sm"
              data-testid="order_id"
              value={orderId}
              onChange={(e) => {
                handleOrderIdChange(e.target.value);
              }}
            />
          </div>
          {!isResellerOrderFilter ? (
            <div className="form-group list-filter-item">
              <label>Reseller Name</label>
              <input
                name="reseller_name"
                className="form-control input-sm"
                data-testid="reseller_name"
                onChange={(e) => {
                  handleResellerNameChange(e.target.value);
                }}
              />
            </div>
          ) : null}
          <div className="form-group datepicker-group">
            <label>Duration</label>
            <DateRangePickerV2
              presets={presetsToShow}
              setSelectedPreset={onSelectPreset}
              onDatesChange={onDatesChange}
              selectedPreset={selectedPreset}
            />
          </div>
          <div className="form-group list-filter-item">
            <label>Status</label>
            <select
              name="status"
              className="form-control input-sm"
              onChange={(e) => handleStatusChange(e.target.value)}
              id="status-dropdown"
              defaultValue={status}
              data-testid="status"
            >
              {Object.values(ORDERS_STATUS).map((status) => (
                <option
                  key={status.value}
                  value={status.value}
                  data-testid={`option-${status.value}`}
                >
                  {status.label}
                </option>
              ))}
            </select>
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

export default OrdersFilter;
