import styled from 'styled-components';
import { reportsTheme } from 'merchant_common/views/Reports/configs';

export const FormContainer = styled.form``;

export const CollapsibleFormWrapper = styled.div(({ theme }) => {
  return `
  margin: ${theme.spacing[5]}px 0;
  display: flex;
  flex-direction: row;
`;
});

export const CollapsibleFormContent = styled.div`
  ${({ theme }) => {
    const { FIELD_BORDER_WIDTH, FIELD_BORDER_DEFAULT_COLOR } = reportsTheme(theme);
    return `
    margin-top: 10px;
    border-top: ${FIELD_BORDER_WIDTH} solid ${FIELD_BORDER_DEFAULT_COLOR};
    padding: 10px 0;
    > div:last-of-type {
      margin-bottom: 0 !important;
    }
  `;
  }}
`;

export const CollapsibleFormContentWrapper = styled.div<{
  active?: boolean;
  validation?: boolean;
  isComplete?: boolean;
  isExpandDisabled?: boolean;
}>`
  ${({ theme, active, validation, isComplete, isExpandDisabled }) => {
    const {
      FIELD_FOCUS_COLOR_L3,
      FIELD_BORDER_ERROR_COLOR,
      FIELD_BORDER_DEFAULT_COLOR,
      FIELD_BORDER_WIDTH,
      HOVER_BG_COLOR,
      DISABLED_BG_COLOR,
    } = reportsTheme(theme);

    return `
      border-radius: 2px;
      width: 100%;
      background-color: ${isExpandDisabled ? DISABLED_BG_COLOR : 'transparent'};
      cursor: ${active || isExpandDisabled ? 'default' : 'pointer'};
      border: ${FIELD_BORDER_WIDTH} solid
          ${
            validation
              ? active || isComplete
                ? FIELD_FOCUS_COLOR_L3
                : FIELD_BORDER_DEFAULT_COLOR
              : FIELD_BORDER_ERROR_COLOR
          } !important;
      padding: 16px;
      ${
        !isExpandDisabled &&
        `
        &:hover {
          background-color: ${active ? 'transparent' : HOVER_BG_COLOR};
        }
        `
      }
      transition: background-color 0.3s ease;
      `;
  }}
`;

export const CollapsibleFormChildWrapper = styled.div`
  ${({ theme }) => {
    const { FIELD_BORDER_RADIUS, FIELD_WRAPPER_BG_COLOR } = reportsTheme(theme);
    return `
        border-radius: ${FIELD_BORDER_RADIUS};
        background: ${FIELD_WRAPPER_BG_COLOR};
        margin-bottom: ${theme.spacing[5]}px;
        margin-top: 5px;
        padding: 10px;
  `;
  }}
`;

export const CollapsibleFormHeader = styled.div(
  () => `
    display: flex;
    justify-content: space-between;
    align-items: center;
`,
);

export const CollapsibleFormLabel = styled.div``;

export const FocusIndicator = styled.div<{
  active?: boolean;
  validation?: boolean;
  isComplete?: boolean;
  disabled?: boolean;
}>(({ theme, active, validation, isComplete, disabled }) => {
  const {
    FIELD_FOCUS_COLOR_L2,
    DISABLED_BG_COLOR,
    FIELD_FOCUS_COLOR_L3,
    FIELD_BORDER_ERROR_COLOR,
    FIELD_BORDER_RADIUS,
  } = reportsTheme(theme);
  return `
  width: 4px;
  border-radius: ${FIELD_BORDER_RADIUS};
  margin-right: ${theme.spacing[5]}px;
  background-color: ${
    disabled
      ? DISABLED_BG_COLOR
      : validation
      ? active || isComplete
        ? FIELD_FOCUS_COLOR_L3
        : FIELD_FOCUS_COLOR_L2
      : FIELD_BORDER_ERROR_COLOR
  } !important;
  `;
});
