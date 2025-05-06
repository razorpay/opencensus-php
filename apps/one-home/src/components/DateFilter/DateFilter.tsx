import React, { useState, useCallback, useMemo } from 'react';
import {
  Box,
  Dropdown,
  DropdownOverlay,
  SelectInput,
  ActionList,
  ActionListItem,
  useTheme,
  Link,
  ChevronDownIcon,
  BottomSheet,
  BottomSheetHeader,
  BottomSheetBody,
} from '@razorpay/blade/components';
import { useBreakpoint } from '@razorpay/blade/utils';
import { FilterOption, DateFilterProps } from './types';

const dateFilterOptions: FilterOption[] = [
  {
    key: 'yesterday',
    value: 'Yesterday',
  },
  {
    key: 'last_7_days',
    value: 'This Week',
  },
  {
    key: 'last_30_days',
    value: 'Last 30 Days',
  },
];

const createOptionsMap = (options: FilterOption[]): Record<string, FilterOption> => {
  const optionsObject: Record<string, FilterOption> = {};
  options.forEach((option) => {
    optionsObject[option.key] = option;
  });
  return optionsObject;
};

const DateFilterDropDown: React.FC<DateFilterProps> = ({
  options,
  selected,
  onChange,
  isDisabled,
}) => {
  const handleOnChange = useCallback(
    ({ name, values }: { name?: string; values: string[] }) => {
      onChange(values[0]);
    },
    [onChange],
  );

  return (
    <Box width="144px">
      <Dropdown selectionType="single">
        <SelectInput
          label="" // Empty label added to satisfy the required prop for type definition
          defaultValue={selected}
          name="action"
          onChange={handleOnChange}
          size="medium"
          isDisabled={isDisabled}
        />
        <DropdownOverlay>
          <ActionList>
            {options.map((option, index) => (
              <ActionListItem
                key={`${option.key}_${index}`}
                title={option.value}
                value={option.key}
              />
            ))}
          </ActionList>
        </DropdownOverlay>
      </Dropdown>
    </Box>
  );
};

const DateFilterBottomsheet: React.FC<DateFilterProps> = ({
  options = [],
  selected,
  onChange,
  isDisabled,
}) => {
  const [isBottomsheetOpen, setIsBottomsheetOpen] = useState(false);

  const handleOpenBottomsheet = useCallback(() => {
    setIsBottomsheetOpen(true);
  }, []);

  const optionsMap = useMemo(() => createOptionsMap(options), [options]);
  const selectedOption = optionsMap[selected] || options[0];

  return (
    <>
      <Link
        size="small"
        icon={ChevronDownIcon}
        iconPosition="right"
        variant="button"
        isDisabled={isDisabled}
        onClick={handleOpenBottomsheet}
      >
        {selectedOption.value}
      </Link>
      <BottomSheet isOpen={isBottomsheetOpen} onDismiss={() => setIsBottomsheetOpen(false)}>
        <BottomSheetHeader title="Select date range" />
        <BottomSheetBody>
          <ActionList>
            {options.map((option) => (
              <ActionListItem
                isSelected={option.key === selected}
                key={option.key}
                title={option.value}
                value={option.key}
                onClick={() => {
                  setIsBottomsheetOpen(false);
                  onChange(option.key);
                }}
              />
            ))}
          </ActionList>
        </BottomSheetBody>
      </BottomSheet>
    </>
  );
};

export const DateFilter: React.FC<DateFilterProps> = ({
  options = dateFilterOptions,
  selected,
  onChange,
  isDisabled = false,
}) => {
  const { theme } = useTheme();
  const { matchedDeviceType } = useBreakpoint({
    breakpoints: theme.breakpoints,
  });

  const isMobile = matchedDeviceType === 'mobile';

  const memoizedOptions = useMemo(() => options, []);

  return isMobile ? (
    <DateFilterBottomsheet
      options={memoizedOptions}
      selected={selected}
      onChange={onChange}
      isDisabled={isDisabled}
    />
  ) : (
    <DateFilterDropDown
      options={memoizedOptions}
      selected={selected}
      onChange={onChange}
      isDisabled={isDisabled}
    />
  );
};

export default DateFilter;
