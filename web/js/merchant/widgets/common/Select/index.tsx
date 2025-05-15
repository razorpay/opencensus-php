import React, { useState } from 'react';
import {
  Dropdown,
  SelectInput,
  DropdownOverlay,
  ActionList,
  Box,
  ActionListItem,
  DatePicker,
} from '@razorpay/blade/components';
import moment from 'moment';
import { connect } from 'react-redux';

import { useMobile } from 'common/hooks/useMobile';
import { toggleHelpWidget } from 'merchant/reducers/session';
import { durationOptionsMap } from 'merchant/widgets/InsightsChart/utils';
import { getDateRangeValues, track } from 'merchant/widgets/utils';

import { SelectChangeEvent, SelectProps } from './types';

function Select({
  value,
  onChange,
  values,
  default_value,
  analyticsProperties,
  customRange,
  toggleHelpWidget,
  variables,
  label = '',
}: SelectProps) {
  const isMobile = useMobile();

  const [key, setKey] = useState('');
  const [dates, setDates] = useState<[Date, Date]>(
    getDateRangeValues(value, variables?.date_time?.custom),
  );
  const [isDatePickerOpen, setIsDatePickerOpen] = useState(false);

  const handleOptionChange = (e: SelectChangeEvent, custom_range?: [Date, Date]) => {
    const selected = e.values[0];
    setKey(selected);
    if (selected === 'custom_range') {
      if (!custom_range) {
        if (isMobile) {
          toggleHelpWidget?.({ showWidget: false });
          setIsDatePickerOpen(true);
          return;
        }
      } else {
        setDates(custom_range);
      }
    } else {
      setDates(getDateRangeValues(selected));
    }
    onChange?.(e.values, custom_range);
    const { screen, ...rest } = analyticsProperties;
    track({
      objectName: 'select',
      actionName: 'clicked',
      screen,
      properties: { ...rest, value: selected },
    });
  };

  const handleDateOpenChange = ({ isOpen }) => {
    setIsDatePickerOpen(isOpen);
    if (isMobile && !isOpen && toggleHelpWidget) {
      toggleHelpWidget({ showWidget: true });
    }
  };

  return (
    <>
      {customRange ? (
        <Box width={isMobile ? '0px' : '350px'} overflow={isMobile ? 'hidden' : undefined}>
          <DatePicker
            key={key}
            // @ts-expect-error problem with blade types
            selectionType="range"
            allowSingleDateInRange
            isOpen={isDatePickerOpen}
            defaultValue={dates}
            onOpenChange={handleDateOpenChange}
            minDate={moment().subtract(45, 'days').toDate()}
            maxDate={moment().toDate()}
            visibility={isMobile && !isDatePickerOpen ? 'hidden' : 'visible'}
            onApply={(custom_range) =>
              handleOptionChange({ values: ['custom_range'] }, custom_range)
            }
          />
        </Box>
      ) : null}
      <Box width="150px">
        <Dropdown selectionType="single" testID="date-picker-component">
          <SelectInput
            label={label}
            accessibilityLabel="Date picker"
            placeholder="Duration"
            defaultValue={default_value}
            value={value}
            name="date"
            onChange={handleOptionChange}
          />
          <DropdownOverlay>
            <ActionList>
              {values.map((value) => {
                return (
                  <ActionListItem
                    title={durationOptionsMap[value]}
                    value={value}
                    key={`duration-${value}`}
                  />
                );
              })}
              {customRange ? (
                <ActionListItem title="Custom" value="custom_range" key="duration-custom_range" />
              ) : null}
            </ActionList>
          </DropdownOverlay>
        </Dropdown>
      </Box>
    </>
  );
}

export default connect(null, { toggleHelpWidget })(Select);
