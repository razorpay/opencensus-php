import { COLORS } from 'merchant/containers/Home/RTUX/colors';
import styled from 'styled-components';

export const ColorBox = styled.div<{ backgroundColor: string }>(
  ({ backgroundColor, theme }) => `
  width: ${theme.spacing[4]}px;
  height: ${theme.spacing[4]}px;
  background-color: ${backgroundColor};
`,
);

export const DoughnutWrapper = styled.div(({ theme }) => ({
  backgroundColor: COLORS.backgroundPrimarySubtle,
  padding: theme.spacing[3],
  borderRadius: theme.border.radius.medium,
  height: 'fit-content',
}));
