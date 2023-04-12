import styled, { css } from 'styled-components';
import {
  BASE_FIELD_BORDER_RADIUS,
  BASE_FIELD_PADDING,
  BASE_FIELD_WIDTH,
  FlexCentered,
  AbsoluteWrapper as AW,
  ScrollSafeMargin,
  BASE_FIELD_BACKGROUND_COLOR,
  BASE_FIELD_MARGIN,
  ScrollableContainer,
} from 'merchant_common/views/Reports/components/styled';
import { reportsTheme } from 'merchant_common/views/Reports/configs';

export const BaseOption = styled.div<{ disabled: boolean; selected?: boolean }>(
  ({ theme, disabled, selected }) => {
    const { FIELD_FOCUS_COLOR_L1 } = reportsTheme(theme);
    return `
    display: flex;
    cursor: pointer;
    background-color: ${selected ? FIELD_FOCUS_COLOR_L1 : 'transparent'};
    &:hover {
      background-color: ${disabled ? 'transparent' : FIELD_FOCUS_COLOR_L1};
    }
`;
  },
);

export const DefaultOption = styled.div`
  display: flex;
  justify-content: left;
  align-items: center;
`;

export const InputContainer = styled.div`
  ${BASE_FIELD_BACKGROUND_COLOR}
  ${BASE_FIELD_MARGIN}
  position: relative;
`;

export const baseSelectedStyles = css<{ shouldShowDropDown: boolean }>`
  ${BASE_FIELD_PADDING}
  ${BASE_FIELD_WIDTH}
  ${BASE_FIELD_BORDER_RADIUS}
  border: none;
  ${({ shouldShowDropDown }) =>
    shouldShowDropDown ? `border-bottom-left-radius: 0; border-bottom-right-radius: 0;` : ``}
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
  background-color: ${({ theme }) => theme.colors.brand.primary[300]};
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
  ${({ theme, validation, shouldShowDropDown }) => {
    const {
      FIELD_BORDER_RADIUS,
      FIELD_BORDER_WIDTH,
      FIELD_BORDER_ERROR_COLOR,
      FIELD_FOCUS_COLOR_L3,
      FIELD_SHADOW,
      FIELD_SHADOW_BLUR_RADIUS,
      FIELD_BORDER_DEFAULT_COLOR,
    } = reportsTheme(theme);

    return `
  border:${FIELD_BORDER_WIDTH} solid ${
      !validation
        ? FIELD_BORDER_ERROR_COLOR
        : shouldShowDropDown
        ? FIELD_FOCUS_COLOR_L3
        : FIELD_BORDER_DEFAULT_COLOR
    };
      border-bottom: ${FIELD_BORDER_WIDTH} solid ${
      !validation && !shouldShowDropDown
        ? FIELD_BORDER_ERROR_COLOR
        : shouldShowDropDown
        ? 'transparent'
        : FIELD_BORDER_DEFAULT_COLOR
    };
      border-right-radius: 0;
      border-radius: ${
        shouldShowDropDown
          ? `${FIELD_BORDER_RADIUS} ${FIELD_BORDER_RADIUS} 0 0`
          : FIELD_BORDER_RADIUS
      };
      cursor: pointer;
      box-shadow: 0 0 ${FIELD_SHADOW_BLUR_RADIUS} ${
      shouldShowDropDown ? FIELD_SHADOW : 'transparent'
    };
      transition: box-shadow 0.2s ease;
      `;
  }}
`;

export const DropdownItemList = styled(ScrollSafeMargin)<{ validation: boolean }>`
  ${BASE_FIELD_WIDTH}
  ${BASE_FIELD_BACKGROUND_COLOR}
${({ theme, validation }) => {
    const {
      FIELD_BORDER_RADIUS,
      FIELD_BORDER_WIDTH,
      FIELD_BORDER_ERROR_COLOR,
      FIELD_FOCUS_COLOR_L3,
      FIELD_SHADOW,
      FIELD_SHADOW_BLUR_RADIUS,
    } = reportsTheme(theme);
    return `
    border-bottom-left-radius: ${FIELD_BORDER_RADIUS};
    border-bottom-right-radius: ${FIELD_BORDER_RADIUS};
    border: ${FIELD_BORDER_WIDTH} solid ${
      !validation ? FIELD_BORDER_ERROR_COLOR : FIELD_FOCUS_COLOR_L3
    };
    border-top-width: 0;
    border-bottom-width: ${FIELD_BORDER_WIDTH};
    box-shadow: 0 10px ${FIELD_SHADOW_BLUR_RADIUS} ${FIELD_SHADOW};
    transition: box-shadow 0.2s ease;
  `;
  }}
`;

export const AbsoluteWrapper = styled(AW)`
  z-index: 1;
  left: 0;
  right: 0;
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
