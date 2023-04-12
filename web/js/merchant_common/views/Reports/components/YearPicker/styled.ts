import styled from 'styled-components';
import {
  CalendarField,
  CalendarInput,
  DateTimeRangeContainer as StyledDateTimeRangeContainer,
} from 'merchant_common/views/Reports/components/DateTimeRangePicker/styled';
import {
  FULL_BASE_FIELD_STYLE,
  FlexCentered,
} from 'merchant_common/views/Reports/components/styled';
import { BaseValidationStyledProps } from 'merchant_common/views/Reports/components/types';

export const SelectedYearInfo = styled.div<BaseValidationStyledProps>`
  ${FULL_BASE_FIELD_STYLE}
  cursor: pointer;
  width: 100%;
`;

export const YearContainer = styled(StyledDateTimeRangeContainer)<BaseValidationStyledProps>``;

export const YearPickerStyled = styled(FlexCentered)`
  margin-top: 15px;
  margin: 10px;
  flex-direction: column;
`;

export const PickerNavigationContainer = styled.div`
  width: 100%;
  display: flex;
  align-items: stretch;
  justify-content: center;
`;

export const YearField = styled(CalendarField)`
  width: 100%;
`;

export const YearInput = styled(CalendarInput)<{ open: boolean; label?: string }>``;
