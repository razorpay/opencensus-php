import styled from 'styled-components';
import {
  AbsoluteWrapper,
  BASE_FIELD_MARGIN,
  BASE_FIELD_PADDING,
  BASE_FIELD_WIDTH,
  FlexCentered,
  flexCentered,
} from 'merchant_common/views/Reports/components/styled';
import { reportsTheme } from 'merchant_common/views/Reports/configs';
import { BaseValidationStyledProps } from 'merchant_common/views/Reports/components/types';

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
    const { FIELD_SHADOW_BLUR_RADIUS, FIELD_BORDER_DEFAULT_COLOR, FIELD_BORDER_RADIUS } =
      reportsTheme(theme);
    return `
   background: white;
   margin-top: 4px;
   margin-right: 6px;
   box-shadow: 0px 0px ${FIELD_SHADOW_BLUR_RADIUS} ${FIELD_BORDER_DEFAULT_COLOR};
   ${flexCentered}
   height: 90px;
   width: 202px;
   border-radius: ${FIELD_BORDER_RADIUS};
   animation: slideFromTop 70ms linear;

      @keyframes slideFromTop {
        0% {
          transform: translateY(-8px);
          opacity: 0.3;
        }
        100% {
          transform: translateY(0px);
          opacity: 1;
        }
      }
   `;
  }}
`;

export const TimePickerWrapper = styled(AbsoluteWrapper)`
  z-index: 3;
  transform: translate(-50%, 100%);
  bottom: 0px;
`;

export const PickerAbsWrapper = styled(AbsoluteWrapper)`
  z-index: 1;
  left: 0px;
  right: 0px;
  top: calc(100% + 8px);
`;

export const SelectedRangeInputField = styled.div<BaseValidationStyledProps>`
  ${BASE_FIELD_MARGIN}
  ${BASE_FIELD_PADDING}
  display: flex;
  justify-content: space-between;
  align-items: center;
  min-width: 220px;
  cursor: default;
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

export const TimePickerS = styled(FlexCentered)`
  ${BASE_FIELD_WIDTH}
  ${({ theme }) => {
    const { FIELD_SHADOW, FIELD_SHADOW_BLUR_RADIUS } = reportsTheme(theme);
    return `
      padding: ${theme.spacing[5]}px;
      border-radius: 4px;
      box-shadow: 0px 0px ${FIELD_SHADOW_BLUR_RADIUS} ${FIELD_SHADOW};
      transition: box-shadow 0.2s ease;
      background-color: #ffffff;

      animation: slideFromTop 70ms linear;

      @keyframes slideFromTop {
        0% {
          transform: translateY(-8px);
          opacity: 0.3;
        }
        100% {
          transform: translateY(0px);
          opacity: 1;
        }
      }
  `;
  }}
`;
