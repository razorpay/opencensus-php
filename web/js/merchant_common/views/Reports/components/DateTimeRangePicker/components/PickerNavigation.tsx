import React from 'react';
import { NavigationContainer } from 'merchant_common/views/Reports/components/DateTimeRangePicker/styled';
import {
  IconButton,
  ChevronLeftIcon,
  ChevronRightIcon,
} from 'merchant_common/views/Reports/components';

export const PickerNavigation = ({
  viewMode,
  isPrevClickAllowed,
  isNextClickAllowed,
  onPrevClick,
  onNextClick,
}) => {
  return (
    <NavigationContainer disabled={viewMode === 'month'}>
      {isPrevClickAllowed ? (
        <IconButton
          icon={ChevronLeftIcon}
          accessibilityLabel="Previous Range"
          onClick={onPrevClick}
          size="large"
        />
      ) : (
        <div />
      )}
      {isNextClickAllowed ? (
        <IconButton
          icon={ChevronRightIcon}
          accessibilityLabel="Next Range"
          onClick={onNextClick}
          size="large"
        />
      ) : (
        <div />
      )}
    </NavigationContainer>
  );
};
