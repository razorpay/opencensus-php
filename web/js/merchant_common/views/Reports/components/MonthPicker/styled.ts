import styled from 'styled-components';
import {
  CalendarField,
  CalendarInput,
  DateTimeRangeContainer as StyledDateTimeRangeContainer,
} from 'merchant_common/views/Reports/components/DateTimeRangePicker/styled';
import { FULL_BASE_FIELD_STYLE } from 'merchant_common/views/Reports/components/styled';
import { BaseValidationStyledProps } from 'merchant_common/views/Reports/components/types';

export const SelectedMonthInfo = styled.div<BaseValidationStyledProps>`
  ${FULL_BASE_FIELD_STYLE}
  cursor: pointer;
  width: 100%;
`;

export const MonthContainer = styled(StyledDateTimeRangeContainer)<BaseValidationStyledProps>``;

export const YearPickerStyled = styled.div`
  margin-top: 15px;
  margin: 10px;
`;

export const MonthField = styled(CalendarField)`
  width: 100%;
`;

export const MonthInput = styled(CalendarInput)<{ open: boolean; label?: string }>``;
