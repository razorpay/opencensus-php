import styled, { css, keyframes } from 'styled-components';
import { reportsTheme } from 'merchant_common/views/Reports/configs';
import { BaseValidationStyledProps } from './types';

export const MarginDivider = styled.div(({ theme }) => {
  const { MARGIN_DIVIDER } = reportsTheme(theme);
  return `
      margin-top: ${MARGIN_DIVIDER}px;
      margin-bottom: ${MARGIN_DIVIDER}px;
    `;
});

export const flexCentered = css`
  display: flex;
  justify-content: center;
  align-items: center;
`;

export const Icon = styled.img<{ size: string }>`
  width: ${(p) => p.size};
  height: ${(p) => p.size};
  object-fit: contain;
`;

export const FlexCentered = styled.div`
  ${flexCentered}
`;

export const FlexJustifyContentCenter = styled.div`
  display: flex;
  justify-content: center;
`;

export const ModalMask = styled.div`
  background-color: ${({ theme }) => reportsTheme(theme).MODAL_BG_MASK_COLOR};
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  z-index: 9999;
  overflow: hidden;
  transition: opacity 250ms ease-in-out;
`;

export const ClickableButton = styled.button`
  background: transparent;
  border: none;
`;

export const DefaultButton = styled.button<{ transparent: boolean }>(
  ({ transparent }) => `
  ${flexCentered};
  flex-direction: row;
  border: none;
  ${transparent && 'background: transparent;'}
`,
);

export const skeletonKeyFrame = keyframes`
    0% {
      background-color: hsl(200, 20%, 80%);
    }
    100% {
      background-color: hsl(200, 20%, 95%);
    }
`;

export const Skeleton = styled.div`
  animation: ${skeletonKeyFrame} 1s linear infinite alternate;
`;

export const Clickable = styled.div`
  cursor: pointer;
`;

export const commonFieldStyle = css``;

export const Block = styled.div<{ m?: number; p?: number }>`
  ${({ m = 0, p = 0 }) => `
      margin: ${m}px;
      padding: ${p}px;
  `}
  ${flexCentered}
`;

export const Button = styled.button`
  background: transparent;
  border: none;
  outline: none;
  pointer: cursor;
  ${flexCentered}
`;

// field styles
export const BASE_FIELD_PADDING = css`
  padding: ${({ theme }) => reportsTheme(theme).FIELD_PADDING};
`;

export const BASE_FIELD_BORDER_RADIUS = css`
  border-radius: ${({ theme }) => reportsTheme(theme).FIELD_BORDER_RADIUS};
`;

export const BASE_FIELD_BORDER = css<BaseValidationStyledProps>`
  ${({ theme, validation, focused }) => {
    const {
      FIELD_BORDER_ERROR_COLOR,
      FIELD_BORDER_DEFAULT_COLOR,
      FIELD_BORDER_WIDTH,
      FIELD_FOCUS_COLOR_L3,
    } = reportsTheme(theme);

    return `
  border: ${FIELD_BORDER_WIDTH} solid ${
      validation
        ? focused
          ? FIELD_FOCUS_COLOR_L3
          : FIELD_BORDER_DEFAULT_COLOR
        : FIELD_BORDER_ERROR_COLOR
    };
  `;
  }}
`;

export const BASE_FIELD_COLOR = css`
  ${({ theme }) => {
    const { FIELD_INPUT_TEXT_COLOR, FIELD_INPUT_PLACEHOLDER_COLOR } = reportsTheme(theme);
    return `
    color: ${FIELD_INPUT_TEXT_COLOR};
    ::placeholder {
      color: ${FIELD_INPUT_PLACEHOLDER_COLOR};
    }
    `;
  }}
`;

export const BASE_FIELD_BACKGROUND_COLOR = css`
  ${({ theme }) => {
    const { FIELD_BG_COLOR } = reportsTheme(theme);
    return `
      background: ${FIELD_BG_COLOR};
  `;
  }}
`;

export const BASE_FIELD_WIDTH = css`
  width: 100%;
`;

export const BASE_FIELD_MARGIN = css<BaseValidationStyledProps>`
  margin: ${({ label, theme }) =>
    label
      ? reportsTheme(theme).FIELD_WITH_LABEL_MARGIN
      : reportsTheme(theme).FIELD_WITHOUT_LABEL_MARGIN};
`;

export const FULL_BASE_FIELD_STYLE = css<BaseValidationStyledProps>`
  ${BASE_FIELD_PADDING}
  ${BASE_FIELD_BORDER_RADIUS}
  ${BASE_FIELD_BORDER}
  ${BASE_FIELD_COLOR}
  ${BASE_FIELD_BACKGROUND_COLOR}
  ${BASE_FIELD_WIDTH}
  ${BASE_FIELD_MARGIN}
`;

// absolute wrapper
export const AbsoluteWrapper = styled.div`
  position: absolute;
`;

export const ScrollSafeMargin = styled.div`
  margin-bottom: 30px;
`;

export const ToggleVisibility = styled.div<{ disabled: boolean }>`
  ${({ disabled }) =>
    disabled
      ? `
  visibility: hidden;
  `
      : ``}
`;

export const ScrollableContainer = styled.div<{ scrollbarColor?: string }>`
  overflow-y: auto;
  scrollbar-width: 8px;
  & ::-webkit-scrollbar {
    width: 8px;
  }
  ${({ scrollbarColor = 'lightgray' }) => `
  scrollbar-color: ${scrollbarColor} transparent;
  & ::-webkit-scrollbar-thumb {
    border-radius: 10px;
    background-color: ${scrollbarColor};
  }
  `}
`;

export const CenteredEmptyContainer = styled.div`
  width: 100%;
  height: calc(100vh - 100px);
  background-color: #fff;
  flex-direction: column;
  ${flexCentered}
`;

export const FieldLabelWrapper = styled.div`
  margin-bottom: 9px;
  position: relative;
`;
