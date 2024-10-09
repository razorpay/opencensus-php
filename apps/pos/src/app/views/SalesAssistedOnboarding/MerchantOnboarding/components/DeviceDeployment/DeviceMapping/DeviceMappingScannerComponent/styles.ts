import { Theme } from '@razorpay/blade/components';
import styled from 'styled-components';

export const StyledContainer = styled.div<{ theme: Theme; centerVertically?: boolean }>(
  ({ theme, centerVertically }) => `
  background: ${theme.colors.surface.icon.staticBlack.normal};
  min-height: 94vh;
  padding: ${theme.spacing[9]}px;
  display: flex;
  flex-direction: column;
  gap: ${theme.spacing[8]}px;
  align-items: center;
  ${centerVertically ? 'justify-content: center;' : ''}
`,
);

export const StyledCard = styled.div(
  ({ theme }: { theme: Theme }) => `
  background: ${theme.colors.surface.text.gray.subtle};
  border-radius:${theme.spacing[4]}px;
  padding:${theme.spacing[3]}px ${theme.spacing[5]}px;
  margin-bottom: ${theme.spacing[11]}px;
`,
);

export const StyledDot = styled.div(
  ({ theme }: { theme: Theme }) => `
    width:${theme.spacing[3]}px;
    height:${theme.spacing[3]}px;
    border-radius:50%;
    margin-top:${theme.spacing[3]}px;
    background:${theme.colors.interactive.text.primary.normal}
  `,
);

export const StyledLine = styled.div(
  ({ theme }: { theme: Theme }) => `
  height: 1px;
  background-color: ${theme.colors.interactive.border.neutral.highlighted};
  display: flex;
  flex: 1;
`,
);

export const DeviceMappingSuccessContainer = styled.div(
  ({ theme }: { theme: Theme }) => `
  background: linear-gradient(180deg, rgba(245, 255, 249, 0.8) 0%, ${theme.colors.surface.background.gray.intense} 100%);
  height: 60vh;
`,
);

export const ScannerButton = styled.button`
  border: none;
  outline: none;
  background-color: transparent;
`;
