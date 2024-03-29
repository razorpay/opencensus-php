import styled from 'styled-components';
import { flexCentered } from 'merchant_common/views/Reports/components/styled';
import { reportsTheme } from 'merchant_common/views/Reports/configs';

export const ExpandMore = styled.p<{ active: boolean }>(({ theme, active }) => {
  return `
      background: ${theme.colors.feedback.background.neutral.intense};
      border-radius: ${theme.border.radius.max}px;
      color: #ffffff;
      height: 20px;
      padding: ${active ? 6 : 0}px 6px;
      ${flexCentered};
      cursor: pointer;
    `;
});

export const ListEmails = styled.div(({ theme }) => {
  const { FIELD_SHADOW_BLUR_RADIUS, FIELD_SHADOW, FIELD_FOCUS_COLOR_L3, FIELD_BORDER_WIDTH } =
    reportsTheme(theme);
  return `
    position: absolute;
    max-height: 230px;
    min-width: 230px;
    overflow-x: none;
    overflow-y: auto;
    background: #ffffff;
    border-radius: 4px;
    z-index: 1;
    right: 0;
    top: ${theme.spacing[7]}px;
    border: ${FIELD_BORDER_WIDTH} solid ${FIELD_FOCUS_COLOR_L3};
    box-shadow: 0 0 ${FIELD_SHADOW_BLUR_RADIUS} ${FIELD_SHADOW};
  `;
});

export const Email = styled.div(({ theme }) => {
  return `
  padding: ${theme.spacing[4]}px;
  border-top: 0.5px solid rgba(33, 53, 84, 0.18);
  `;
});

export const ListHeader = styled.div(({ theme }) => {
  return `
  padding: ${theme.spacing[3]}px;
  `;
});
