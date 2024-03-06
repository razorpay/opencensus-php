import { Theme } from '@razorpay/blade/components';
import styled, { css } from 'styled-components';

export const StyledButtonText = styled.button`
  color: ${({ theme }) => theme.colors.brand.primary['500']};
  background: transparent;
  border: none;
  outline: none;
  cursor: pointer;
`;

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
