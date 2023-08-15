import React from 'react';
import { Block } from 'merchant_common/views/Reports/components/styled';
import {
  ChevronDownIcon,
  ChevronUpIcon,
  IconButton,
  Text,
} from 'merchant_common/views/Reports/components';
import { TimeInfoPropsType } from 'merchant_common/views/Reports/components/TimePicker/types';
import { TimePickerRow } from 'merchant_common/views/Reports/components/TimePicker/styled';

export const TimeInfo = ({
  chevUpClick,
  chevDownClick,
  children,
  ariaLabel,
  role,
}: TimeInfoPropsType): JSX.Element => {
  return (
    <TimePickerRow aria-label={ariaLabel}>
      <IconButton
        icon={ChevronUpIcon}
        accessibilityLabel={`${role} Up`}
        onClick={chevUpClick}
        contrast="low"
      />
      <Block
        aria-label={`${role} -> ${children}`}
        style={{
          width: 36,
          height: 36,
        }}
      >
        <Text variant="body" size="medium" weight="regular">
          {children}
        </Text>
      </Block>

      <IconButton
        icon={ChevronDownIcon}
        accessibilityLabel={`${role} Down`}
        onClick={chevDownClick}
        contrast="low"
      />
    </TimePickerRow>
  );
};
