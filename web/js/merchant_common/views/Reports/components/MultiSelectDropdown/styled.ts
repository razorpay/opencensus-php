import styled, { css } from 'styled-components';
import {
  BASE_FIELD_BORDER_RADIUS,
  BASE_FIELD_PADDING,
  BASE_FIELD_WIDTH,
  FlexCentered,
  AbsoluteWrapper as AW,
  ScrollSafeMargin,
  BASE_FIELD_MARGIN,
  ScrollableContainer,
} from 'merchant_common/views/Reports/components/styled';
import { reportsTheme } from 'merchant_common/views/Reports/configs';

export const BaseOption = styled.div<{ disabled: boolean; selected?: boolean }>(
  ({ theme, disabled, selected }) => {
    const { HOVER_BG_COLOR_L2 } = reportsTheme(theme);
    return `
    display: flex;
    cursor: pointer;
    background-color: ${selected ? HOVER_BG_COLOR_L2 : 'transparent'};
    &:hover {
      background-color: ${disabled ? 'transparent' : HOVER_BG_COLOR_L2};
    }
`;
  },
);

export const DefaultOption = styled.div`
  display: flex;
  justify-content: left;
  align-items: center;
`;

export const InputContainer = styled.div<{
  shouldShowDropDown: boolean;
  validation: boolean;
  isDisabled: boolean;
}>`
  ${BASE_FIELD_MARGIN}
  ${({ theme, shouldShowDropDown, validation, isDisabled }) => {
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

    const bgColor = validation
      ? shouldShowDropDown
        ? FIELD_FOCUS_COLOR_L1
        : FIELD_BG_COLOR
      : NEGATIVE_BG;

    return `
      transition: background-color border-color 0.3s ${theme.motion.easing.emphasized};
      border-bottom: 1px solid ${
        isDisabled
          ? 'transparent'
          : validation
          ? shouldShowDropDown
            ? FIELD_FOCUS_COLOR_L3
            : FIELD_BORDER_DEFAULT_COLOR
          : NEGATIVE_BORDER
      };
      border-top-left-radius: ${FIELD_BORDER_RADIUS};
      border-top-right-radius: ${FIELD_BORDER_RADIUS};
      background-color: ${bgColor};
      &: hover{
        background-color: ${
          isDisabled
            ? 'transparent'
            : shouldShowDropDown || !validation
            ? bgColor
            : HOVER_BG_COLOR_L3
        };
      }
    `;
  }}
`;

export const baseSelectedStyles = css<{ shouldShowDropDown: boolean }>`
  ${BASE_FIELD_PADDING}
  ${BASE_FIELD_WIDTH}
  ${BASE_FIELD_BORDER_RADIUS}
  background-color: transparent;
  border: none;
  &: disabled {
    cursor: not-allowed;
  }
`;

// processed and fileid null
export const MultiSelectQueryInput = styled.input<{ shouldShowDropDown: boolean }>`
  ${baseSelectedStyles}
`;

export const SelectionOptionsContainer = styled.div`
  display: flex;
  flex-wrap: wrap;
  padding: 9px 9px 0 9px;
`;

export const SelectedOption = styled.div`
  border-radius: 12px;
  background-color: ${({ theme }) => theme.colors.surface.background.primary.subtle};
  display: flex;
  flex-direction: row;
  align-items: center;
  justify-content: center;
  padding: 3px 8px;
  margin-bottom: 5px;
  margin-right: 5px;
`;

export const DropdownIconWrapper = styled(FlexCentered)`
  margin: 0 13px;
`;

export const SingleSelectedOption = styled.div<{ shouldShowDropDown: boolean }>`
  ${baseSelectedStyles}
`;

export const InfoPanel = styled.div<{ shouldShowDropDown: boolean; validation: boolean }>`
  display: flex;
  flex-direction: column;
  min-width: 185px;
`;

export const DropdownItemList = styled(ScrollSafeMargin)<{ validation: boolean }>`
  ${BASE_FIELD_WIDTH}
  ${({ theme }) => {
    const { FIELD_SHADOW, FIELD_SHADOW_BLUR_RADIUS } = reportsTheme(theme);
    return `
  border-radius: 4px;
  box-shadow: 0px 0px ${FIELD_SHADOW_BLUR_RADIUS} ${FIELD_SHADOW};
  transition: box-shadow 0.2s ease;
  background-color: #ffffff;
  padding: 8px;
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

export const AbsoluteWrapper = styled(AW)`
  z-index: 1;
  left: 0;
  right: 0;
  top: calc(100% + 8px);
`;

export const SelectContainer = styled.div<{ itemHeight: number }>`
  display: flex;
  justify-content: center;
  align-items: center;
  width: 100%;
  height: ${({ itemHeight }) => itemHeight}px;
`;

export const NonVirtualList = styled(ScrollableContainer)<{ scrollbarColor?: string }>`
  max-height: 160px;
`;
