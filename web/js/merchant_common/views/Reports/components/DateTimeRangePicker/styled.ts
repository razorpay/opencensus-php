import styled, { css } from 'styled-components';
import {
  Button,
  FlexJustifyContentCenter,
  flexCentered,
  AbsoluteWrapper as AS,
  BASE_FIELD_MARGIN,
  BASE_FIELD_BORDER_RADIUS,
  ScrollSafeMargin,
  BASE_FIELD_PADDING,
} from 'merchant_common/views/Reports/components/styled';
import { reportsTheme } from 'merchant_common/views/Reports/configs';
import { BaseValidationStyledProps } from 'merchant_common/views/Reports/components/types';

const baseSectionCss = css`
  padding: 8px;
  display: flex;
  justify-content: space-between;
`;

export const CalendarField = styled.div`
  position: relative;
`;

export const CalendarInput = styled.div<BaseValidationStyledProps>`
  ${BASE_FIELD_MARGIN}
  position: relative;
  overflow: ${({ open }) => (open ? `visible` : `hidden`)};
`;

export const SelectedDateValue = styled.div<BaseValidationStyledProps>`
  ${BASE_FIELD_MARGIN}
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

export const Date = styled.td<{
  size?: number;
  isFocused?: boolean;
  inBetween?: boolean;
  isHovered?: boolean;
  disabled?: boolean;
  isStartDate?: boolean;
  isEndDate?: boolean;
  isSingleDaySelected?: boolean;
}>`
  width: ${({ size }) => size}px;
  height: ${({ size }) => size}px;
  background-color: ${({ theme, isFocused, inBetween, isHovered, disabled }) => {
    const { FIELD_FOCUS_COLOR_L1, FIELD_FOCUS_COLOR_L2, FIELD_FOCUS_COLOR_L3 } =
      reportsTheme(theme);
    return disabled
      ? '#ffffff'
      : isFocused
      ? FIELD_FOCUS_COLOR_L3
      : inBetween
      ? FIELD_FOCUS_COLOR_L2
      : isHovered
      ? FIELD_FOCUS_COLOR_L1
      : '#ffffff';
  }};
  cursor: ${({ disabled }) => (disabled ? 'default' : 'pointer')};
  text-align: center;
  border-radius: ${({ isStartDate, isEndDate, isSingleDaySelected }) =>
    isSingleDaySelected ? '8px' : isStartDate ? '8px 0 0 8px' : isEndDate ? '0 8px 8px 0' : '0'};
`;

// PORTAL
// PORTAL
export const AbsoluteWrapper = styled(AS)<{ topOffset?: number }>`
  z-index: 100;
  animation: slideFromTop 100ms linear;

  @keyframes slideFromTop {
    0% {
      transform: translateY(-10px);
      opacity: 0.3;
    }
    100% {
      transform: translateY(0px);
      opacity: 1;
    }
  }

  @keyframes slideFromBottom {
    0% {
      transform: translateY(20px);
      opacity: 0.3;
    }
    100% {
      transform: translateY(0px);
      opacity: 1;
    }
  }

  ${({ theme, topOffset = 28 }) => `
  top: calc(100% + ${topOffset}px);
  @media (max-width: ${theme.breakpoints.m}px) {
    position: fixed;
    top: unset;
    left: 0px;
    right: 0px;
    bottom: 0px;
    animation: slideFromBottom 100ms linear;
    & ${ScrollSafeMargin}{
      margin: 0px;
    }
  }
`};
`;

export const DateTimeRangeContainer = styled(ScrollSafeMargin)<BaseValidationStyledProps>`
  border-radius: 4px;
  ${({ theme }) => {
    const { FIELD_SHADOW, FIELD_SHADOW_BLUR_RADIUS } = reportsTheme(theme);
    return `
    background: #ffffff;
    box-shadow: 0 0 ${FIELD_SHADOW_BLUR_RADIUS} ${FIELD_SHADOW};
    padding: 0;
  `;
  }}
`;

export const SelectedRangeInfo = styled.div`
  display: flex;
  ${({ theme }) => `
    @media (max-width: ${theme.breakpoints.s}px) {
      flex-direction: column;
    }
 `}
`;

export const SelectedRangeInfoBadge = styled.div`
  background: #f2f4f8;
  border-radius: 2px;
  padding: 8px;
  display: flex;
  margin-right: 5px;
  border-bottom: 1px solid #f2f4f8;
`;

export const RangeSection = styled.div`
  ${flexCentered}
  margin: 5px;
  border-radius: ${({ theme }) => reportsTheme(theme).FIELD_BORDER_RADIUS};
`;

export const RangeSectionHeader = styled.div`
  display: flex;
  justify-content: space-between;
  width: 100%;
  margin-bottom: 15px;
`;

export const CalendarContent = styled.main``;

export const CalendarHeader = styled.div`
  padding: 10px;
  ${({ theme }) => {
    const { FIELD_BORDER_RADIUS, FIELD_WRAPPER_BG_COLOR } = reportsTheme(theme);
    return `
    border-radius: ${FIELD_BORDER_RADIUS};
    background-color: ${FIELD_WRAPPER_BG_COLOR};
    `;
  }}
`;

export const CalendarFooter = styled.div`
  ${baseSectionCss}
  flex-wrap: wrap;
`;

export const NavigationContainer = styled.div<{ disabled: boolean }>`
  display: flex;
  justify-content: space-between;
  width: 100%;
  ${({ disabled }) =>
    disabled
      ? `
  visibility:hidden;
  pointer-events: none;
  `
      : ``}
`;

export const DateRangeContainer = styled(FlexJustifyContentCenter)``;

export const DateGridWrapper = styled.div<{ topOffset?: number; animate?: boolean }>`
  margin: 10px;

  @keyframes slideFromTop {
    0% {
      transform: translateY(-10px);
      opacity: 0;
    }
    100% {
      transform: translateY(0px);
      opacity: 1;
    }
  }

  @keyframes slideFromBottom {
    0% {
      transform: translateY(10px);
      opacity: 0;
    }
    100% {
      transform: translateY(0px);
      opacity: 1;
    }
  }

  ${({ theme, animate }) => `
  ${
    animate &&
    `
  animation: slideFromTop 250ms linear;
  @media (max-width: ${theme.breakpoints.m}px) {
    animation: slideFromBottom 220ms linear;
  }
  `
  }
`}
`;

export const Table = styled.table<{ size: number }>`
  border-collapse: collapse;
  width: ${({ size }) => size}px;
`;

export const Th = styled.th`
  text-align: center;
`;

export const Td = styled(Date)<{ disabled: boolean }>`
  &:hover {
    background-color: ${({ theme, disabled }) =>
      disabled ? 'transparent' : reportsTheme(theme).FIELD_FOCUS_COLOR_L1};
  }
`;

export const HeaderButton = styled(Button)<{ disabled: boolean }>`
  width: 100%;
  padding: 5px;
  ${BASE_FIELD_BORDER_RADIUS}
  &:hover {
    background-color: ${({ theme, disabled }) =>
      disabled ? 'transparent' : reportsTheme(theme).TABLE_EVEN_BG_COLOR};
  }
`;
