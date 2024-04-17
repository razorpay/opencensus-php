import styled from 'styled-components';

export const ColorBox = styled.div<{ backgroundColor: string }>(
  ({ backgroundColor, theme }) => `
  width: ${theme.spacing[4]}px;
  height: ${theme.spacing[4]}px;
  background-color: ${backgroundColor};
`,
);
