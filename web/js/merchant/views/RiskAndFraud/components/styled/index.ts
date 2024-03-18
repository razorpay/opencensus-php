import { Theme } from '@razorpay/blade/components';
import styled, { css } from 'styled-components';

export const StyledButtonText = styled.button`
  color: ${({ theme }) => theme.colors.brand.primary['500']};
  background: transparent;
  border: none;
  outline: none;
  cursor: pointer;
`;

export const TooltipWrapper = styled.span`
  & > div {
    vertical-align: middle;
    cursor: pointer;
  }
`;

export const StyledTabButton = styled.button(
  ({ theme, isActive }: { theme: Theme; isActive: boolean }) => `
    display: flex;
    flex: 1;
    flex-direction: column;
    padding: ${theme.spacing[7]}px ${theme.spacing[6]}px ${theme.spacing[7]}px ${
    theme.spacing[6]
  }px;
    height: 140px;
    background-color: ${
      isActive
        ? theme.colors.surface.background.level2.lowContrast
        : theme.colors.surface.background.level3.lowContrast
    };
    border-color: ${theme.colors.surface.border.subtle.lowContrast};
    border-width: 0px;
    border-right-width: ${theme.border.width.thick}px;
    border-style: solid;
    cursor: pointer;
    pointer-events: all;
    &:hover {
      background-color: ${theme.colors.surface.background.level2.lowContrast};
    }
    &:first-child {
      border-top-left-radius: ${theme.border.radius.medium}px;
      border-bottom-left-radius: ${theme.border.radius.medium}px;
    }

    &:last-child {
      border-top-right-radius: ${theme.border.radius.medium}px;
      border-bottom-right-radius: ${theme.border.radius.medium}px;
      border-right-width: 0px
    }
  `,
);

const getOverlayStyles = css`
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: #ffffff80;
  display: flex;
  align-items: center;
  justify-content: center;
`;

export const StyledChartLoader = styled.div(
  ({ theme }: { theme: Theme }) => `
    ${getOverlayStyles}

    &::after {
      content: 'Loading...';
      font-size: ${theme.typography.fonts.size[200]}px;
      font-weight: ${theme.typography.fonts.weight.bold};
      color: ${theme.colors.surface.text.normal.lowContrast};
    }
  `,
);

export const StyledChartError = styled.div(
  ({ theme }: { theme: Theme }) => `
    ${getOverlayStyles}

    &::after {
      content: 'Fetching failed! Try later';
      font-size: ${theme.typography.fonts.size[200]}px;
      font-weight: ${theme.typography.fonts.weight.bold};
      color: ${theme.colors.surface.text.normal.lowContrast};
    }
  `,
);
