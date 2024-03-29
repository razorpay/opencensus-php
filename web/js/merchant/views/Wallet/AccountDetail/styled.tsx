import styled from 'styled-components';

export const SummaryContainer = styled.div`
  &&& {
    padding: 15px 24px;
  }
`;

export const VerticalDivider = styled.div<{ height: string }>(
  ({ height, theme }) => `
border: ${theme.border.width.thin}px solid ${theme.colors.surface.border.gray.muted};
margin: 0 ${theme.spacing[3]}px;
height: ${height};
`,
);
