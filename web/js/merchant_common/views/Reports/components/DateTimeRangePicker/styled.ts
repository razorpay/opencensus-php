import styled, { css } from 'styled-components';
import {
  Button,
  FlexJustifyContentCenter,
  flexCentered,
  AbsoluteWrapper as AS,
  BASE_FIELD_MARGIN,
  BASE_FIELD_PADDING,
  BASE_FIELD_BORDER_RADIUS,
  BASE_FIELD_BACKGROUND_COLOR,
  BASE_FIELD_WIDTH,
  ScrollSafeMargin,
  BASE_FIELD_BORDER,
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

export const SelectedDateValue = styled.div`
  ${BASE_FIELD_PADDING}
  ${BASE_FIELD_BORDER_RADIUS}
  ${BASE_FIELD_BACKGROUND_COLOR}
  ${BASE_FIELD_WIDTH}
  cursor: pointer;
  ${({ theme }) => {
    const { FIELD_BORDER_WIDTH, FIELD_BORDER_DEFAULT_COLOR } = reportsTheme(theme);
    return `
      border: ${FIELD_BORDER_WIDTH} solid ${FIELD_BORDER_DEFAULT_COLOR};
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
export const AbsoluteWrapper = styled(AS)`
  z-index: 100;
  ${({ theme }) => `
  @media (max-width: ${theme.breakpoints.m}px) {
    position: fixed;
    left: 0;
    right: 0;
    bottom: 0;
    animation: blendIn 150ms linear;
    & ${ScrollSafeMargin}{
      margin: 0;
    }
    @keyframes blendIn {
      0% {
        transform: translateY(20px);
        opacity: 0.3;
      };
      100% {
        transform: translateY(0);
        opacity: 1;
      };
    }
  }
`}
`;

export const DateTimeRangeContainer = styled(ScrollSafeMargin)<BaseValidationStyledProps>`
  ${BASE_FIELD_BORDER}
  ${({ theme }) => {
    const { FIELD_BORDER_RADIUS, FIELD_SHADOW, FIELD_SHADOW_BLUR_RADIUS } = reportsTheme(theme);
    return `
    background: #ffffff;
    border-radius: ${FIELD_BORDER_RADIUS};
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
  cursor: pointer;
  margin-right: 5px;
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

export const DateGridWrapper = styled.div`
  margin: 10px;
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
