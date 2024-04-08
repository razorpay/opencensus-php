import { Theme } from '@razorpay/blade/components';
import styled, { css } from 'styled-components';

export const StyledInputBox = styled.div(
  ({
    isDeviceInBreakpoint,
    isMobile,
    theme,
    isRTUXHomepage,
  }: {
    theme: Theme;
    isDeviceInBreakpoint: boolean;
    isMobile: boolean;
    isRTUXHomepage: boolean;
  }) => `
    height: ${isMobile ? theme.spacing[9] : theme.spacing[8]}px;
    width: ${isMobile ? '100%' : isDeviceInBreakpoint ? '200px' : '348px'};
    display: flex;
    align-items: center;
    gap: 6px;
    background: ${
      isRTUXHomepage
        ? theme.colors.surface.background.gray.subtle
        : isMobile
        ? theme.colors.interactive.text.staticWhite.normal
        : theme.colors.feedback.background.neutral.subtle
    };
    border: 1px solid rgba(121, 135, 156, 0.17);
    border-radius: ${theme.spacing[2]}px;
    padding: 6px 14px;
    transition: width 400ms linear;
    &:focus-within {
      background: ${theme.colors.interactive.text.staticWhite.normal};
      border: 1px solid ${theme.colors.interactive.border.primary.default};
      box-shadow: 0px 3px 8px rgba(21, 45, 75, 0.1), 0px 0px 1px rgba(21, 45, 75, 0.1);
      z-index: 999;
      ${
        !isMobile &&
        isDeviceInBreakpoint &&
        css`
          width: 348px;
          position: absolute;
          left: 0;
          top: -6px;
        `
      }
    }
  `,
);

export const StyledBaseInput = styled.input(
  ({ theme }: { theme: Theme }) => `
  width: 100%;
  outline: none;
  border: none;
  font-weight: 400;
  font-size: 14px;
  color: ${theme.colors.surface.text.gray.normal};
  background: transparent;
  &:placeholder-shown {
    text-overflow: ellipsis;
  }
`,
);

export const CloseButton = styled.div`
  cursor: pointer;
  display: flex;
  align-items: center;
`;
