import React, { useEffect, useMemo, useState } from 'react';
import {
  Box,
  Button,
  TextInput,
  Dropdown,
  DropdownOverlay,
  ActionList,
  ActionListItem,
  SelectInput,
  Text,
} from '@razorpay/blade/components';
import qs from 'query-string';

import { DATE_RANGE_PRESETS, ORDERS_STATUS } from 'merchant/views/GCMS/shared/constants';

import { StyledFilterDiv } from './StyledDiv';
import { trackOrdersFiltersCleared, trackOrdersFiltersClicked } from './events';
import DateRangePickerV2, { generatePresets } from '../shared/DateRangePickerV2';

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
        <Box paddingY="spacing.4" marginLeft="-13px" display="flex">
          <div className="form-group list-filter-item">
            <TextInput
              label="Order ID"
              labelPosition="top"
              name="order_id"
              onChange={(e) => {
                handleOrderIdChange(e.value);
              }}
              showClearButton
              type="url"
              validationState="none"
              testID="order_id"
            />
          </div>
          {!isResellerOrderFilter ? (
            <div className="form-group list-filter-item">
              <TextInput
                label="Reseller Name"
                labelPosition="top"
                name="reseller_name"
                onChange={(e) => {
                  handleResellerNameChange(e.value);
                }}
                type="url"
                validationState="none"
                showClearButton
                testID="reseller_name"
              />
            </div>
          ) : null}
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
          <div className="form-group list-filter-item">
            <Dropdown>
              <SelectInput
                label="Status"
                labelPosition="top"
                name="status"
                onChange={(e) => handleStatusChange(e.values?.[0])}
                placeholder="Select Option"
                validationState="none"
                isRequired={true}
                testID="status"
                defaultValue={status}
              />
              <DropdownOverlay>
                <ActionList>
                  {Object.values(ORDERS_STATUS).map((status) => (
                    <ActionListItem
                      key={status.value}
                      title={status.label}
                      value={status.value}
                      testID={`option-${status.value}`}
                    />
                  ))}
                </ActionList>
              </DropdownOverlay>
            </Dropdown>
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

export default OrdersFilter;
