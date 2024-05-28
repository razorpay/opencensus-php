import { Theme } from '@razorpay/blade/components';
import styled, { css } from 'styled-components';

const ScrollStyles = css`
  overflow-y: scroll;
  overflow-x: hidden;
  -ms-overflow-style: none;
  &::-webkit-scrollbar {
    display: none;
  }
`;

export const ProductListingOverlay = styled.div(
  ({ theme }: { theme: Theme }) => `
  position: fixed;
  width: 100%;
  left: ${theme.spacing[0]}px;
  bottom: ${theme.spacing[0]}px;
  background: ${theme.colors.interactive.text.staticWhite.normal};
  z-index: 9999;
  border-radius: ${theme.spacing[2]}px ${theme.spacing[2]}px ${theme.spacing[0]}px ${theme.spacing[0]}px;
  height: calc(100% - 121px);
  border: 1px solid rgba(121, 135, 156, 0.09);
  box-shadow: 0px 3px 8px rgba(21, 45, 75, 0.1), 0px 0px 1px rgba(21, 45, 75, 0.1);
  ${ScrollStyles};
`,
);

export const StyledProductListingContainer = styled.div(
  ({
    isDeviceInBreakpoint,
    isMobile,
    theme,
  }: {
    theme: Theme;
    isDeviceInBreakpoint: boolean;
    isMobile: boolean;
  }) => `
    position: absolute;
    width: 360px;
    max-height: 228px;
    padding: ${theme.spacing[3]}px;
    background: ${theme.colors.interactive.text.staticWhite.normal};
    display: flex;
    flex-direction: column;
    gap: ${theme.spacing[2]}px;
    border: 1px solid rgba(121, 135, 156, 0.09);
    box-shadow: 0px 3px 8px rgba(21, 45, 75, 0.1), 0px 0px 1px rgba(21, 45, 75, 0.1);
    border-radius: ${theme.spacing[2]}px;
    margin-top: ${isDeviceInBreakpoint && !isMobile ? theme.spacing[9] : 10}px;
    ${ScrollStyles};
  `,
);

export const StyledList = styled.div(
  ({ theme }: { theme: Theme }) => `
  display: flex;
  gap: ${theme.spacing[3]}px;
  align-items: center;
  padding: ${theme.spacing[3]}px 9px;
  border-radius: ${theme.spacing[2]}px;
  background: ${theme.colors.interactive.text.staticWhite.normal};
  white-space: nowrap;
  cursor: pointer;
  &:hover,
  &:active {
    background: rgba(121, 135, 156, 0.09);
  }
`,
);

export const ProductTag = styled.div(
  ({ theme }: { theme: Theme }) => `
  height: ${theme.spacing[6]}px;
  padding: ${theme.spacing[0]}px ${theme.spacing[3]}px;
  display: flex;
  align-items: center;
  background: ${theme.colors.surface.background.gray.subtle};
  border-radius: 3px;
  overflow: hidden;
  box-sizing: border-box;
  ${StyledList}:hover & {
    border: 1px solid rgba(121, 135, 156, 0.09);
  }
  p {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }
`,
);

export const Icon = styled.i(
  ({ theme }: { theme: Theme }) => `
  color: #8895a8;
  margin-top: ${theme.spacing[2]}px;
  width: 14px;
`,
);

export const StyledText = styled.div`
  p {
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
    max-width: 175px;
  }
`;
