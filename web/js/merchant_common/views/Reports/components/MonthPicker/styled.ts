import styled from 'styled-components';
import {
  CalendarField,
  CalendarInput,
  DateTimeRangeContainer as StyledDateTimeRangeContainer,
} from 'merchant_common/views/Reports/components/DateTimeRangePicker/styled';
import { BASE_FIELD_PADDING } from 'merchant_common/views/Reports/components/styled';
import { BaseValidationStyledProps } from 'merchant_common/views/Reports/components/types';
import { reportsTheme } from 'merchant_common/views/Reports/configs';

export const SelectedMonthInfo = styled.div<BaseValidationStyledProps>`
  ${BASE_FIELD_PADDING}
  display: flex;
  justify-content: space-between;
  align-items: center;
  ${({ theme, focused, validation }) => {
    const {
      FIELD_BORDER_DEFAULT_COLOR,
      FIELD_BORDER_RADIUS,
      FIELD_FOCUS_COLOR_L1,
      FIELD_BG_COLOR,
      FIELD_FOCUS_COLOR_L3,
      NEGATIVE_BORDER,
      NEGATIVE_BG,
      HOVER_BG_COLOR_L3,
    } = reportsTheme(theme);
    const bgColor = validation ? (focused ? FIELD_FOCUS_COLOR_L1 : FIELD_BG_COLOR) : NEGATIVE_BG;
    return `
  transition: background-color border-color 0.3s ${theme.motion.easing.standard.revealing};
  border-bottom: 1px solid ${
    validation ? (focused ? FIELD_FOCUS_COLOR_L3 : FIELD_BORDER_DEFAULT_COLOR) : NEGATIVE_BORDER
  };
  border-top-left-radius: ${FIELD_BORDER_RADIUS};
  border-top-right-radius: ${FIELD_BORDER_RADIUS};
  background-color: ${bgColor};
  &: hover{
    background-color: ${focused || !validation ? bgColor : HOVER_BG_COLOR_L3};
  }
`;
  }}
`;
export const MonthContainer = styled(StyledDateTimeRangeContainer)<BaseValidationStyledProps>``;

export const YearPickerStyled = styled.div`
  margin-top: 15px;
  margin: 10px;
  padding: 15px 0px;
`;

export const MonthField = styled(CalendarField)`
  width: 100%;
`;

export const MonthInput = styled(CalendarInput)<{ open: boolean; label?: string }>``;
