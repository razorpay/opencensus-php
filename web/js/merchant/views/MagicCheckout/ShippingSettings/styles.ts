import styled from 'styled-components';
import { makeBorderSize } from '@razorpay/blade/utils';

export const Wrapper = styled.div(
  ({ theme }) => `
  border-width: ${makeBorderSize(theme.border.width.thin)};
  border-radius: ${makeBorderSize(theme.border.radius.medium)};
  border-color: ${theme.colors.surface.border.gray.muted};
  border-style: solid;
  padding: ${theme.spacing[6]}px;
  background: ${theme.colors.surface.background.gray.intense}
`,
);

export const Separator = styled.div<{ margin?: number }>(
  ({ theme, margin = theme.spacing[5] }) => `
  margin: ${margin}px 0;
  border-bottom: 1px solid ${theme.colors.surface.border.gray.muted}

`,
);

export const SettingsWrapper = styled.div(
  ({ theme }) => `
  padding: ${theme.spacing[5]}px ${theme.spacing[6]}px;
  background-color: #fafcff;
  flex: 3;
`,
);

export const SliderWrapper = styled.div(
  ({ theme }) => `
  background: #FFF;
  box-shadow: -4px 4px 25px 4px rgba(0, 0, 0, 0.10);
  padding: ${theme.spacing[6]}px;
  height: 100vh;
  overflow: auto;
`,
);

export const SliderItem = styled.div(
  ({ theme }) => `
  &:last-child {
    padding-bottom: 30px;
  }
  margin-bottom:${theme.spacing[6]}px;

`,
);

export const StyledErrorDiv = styled.div`
  margin-left: 24px;
`;
