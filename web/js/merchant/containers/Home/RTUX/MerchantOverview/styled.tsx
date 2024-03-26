import styled from 'styled-components';

export const MerchantOverviewWrapper = styled.div<{
  isMobile: boolean;
}>(
  ({ theme, isMobile }) => `
  border-radius: ${theme.border.radius.medium}px;
  margin: ${theme.spacing[0]}px ${isMobile ? theme.spacing[0] : theme.spacing[6]}px;
  background: linear-gradient(275.35deg, #1FC5A8 -50.79%, #5761B2 122.94%);
  overflow-x: hidden;
`,
);

export const Image = styled.img`
  width: 100px;
`;

export const MerchantOverviewDataWrapper = styled.div(({ theme }) => ({
  backgroundColor: '#f8fafc',
  borderRadius: theme.border.radius.large,
  padding: theme.spacing[7],
}));
