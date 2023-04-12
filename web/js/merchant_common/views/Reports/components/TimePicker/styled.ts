import styled from 'styled-components';
import { AbsoluteWrapper, flexCentered } from 'merchant_common/views/Reports/components/styled';
import { reportsTheme } from 'merchant_common/views/Reports/configs';

export const SelectedRangeInfoBadge = styled.div`
  background: #f2f4f8;
  border-radius: ${({ theme }) => reportsTheme(theme).FIELD_BORDER_RADIUS};
  padding: 8px;
  display: flex;
  cursor: pointer;
  justify-content: space-between;
  align-items: center;
`;

export const TimePickerContainer = styled.div`
  position: relative;
`;

export const TimePickerRow = styled.div`
  ${flexCentered}
  flex-direction: column;
  margin: 10px;
`;

export const TimePicker = styled.div`
  ${({ theme }) => {
    const {
      FIELD_BORDER_WIDTH,
      FIELD_FOCUS_COLOR_L3,
      FIELD_SHADOW_BLUR_RADIUS,
      FIELD_BORDER_DEFAULT_COLOR,
      FIELD_BORDER_RADIUS,
    } = reportsTheme(theme);
    return `
   background: #ffffff;
   border: ${FIELD_BORDER_WIDTH} solid ${FIELD_FOCUS_COLOR_L3};
   box-shadow: 0 0 ${FIELD_SHADOW_BLUR_RADIUS} ${FIELD_BORDER_DEFAULT_COLOR};
   ${flexCentered}
   height: 90px;
   width: 200px;
   border-radius: ${FIELD_BORDER_RADIUS};
   `;
  }}
`;

export const TimePickerWrapper = styled(AbsoluteWrapper)`
  z-index: 3;
  transform: translate(-50%, 100%);
  bottom: 0;
`;
