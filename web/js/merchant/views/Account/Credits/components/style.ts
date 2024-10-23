import { Theme } from '@razorpay/blade/components';
import styled, { css } from 'styled-components';

export const Flex = styled.div(
  ({
    justifyBetween,
    direction,
    spacing,
    isResponsive,
    alignItems,
  }: {
    justifyBetween?: boolean;
    theme: Theme;
    direction: 'row' | 'column';
    spacing: number;
    isResponsive?: boolean;
    alignItems: string;
  }) => `
  display: flex;
  flex-direction: row;
  width: 100%;
  gap: ${spacing}px;
  flex-direction: ${direction};
  align-items:${alignItems ?? 'unset'};
  ${
    justifyBetween &&
    css`
      justify-content: space-between;
    `
  }
  ${
    isResponsive &&
    css`
      @media screen and (max-width: 850px) {
        flex-direction: column;
        align-items: unset;
      }
    `
  }
`,
);

export const Slot = styled.div`
  @media screen and (max-width: 468px) {
    width: 100%;
  }
`;

export const SpacedDiv = styled.div(
  ({ theme }: { theme: Theme }) => `
  padding: ${theme.spacing[3]}px 0;
  display: flex;
  gap: ${theme.spacing[3]}px;
  align-items: center;
`,
);

export const BankImageContainer = styled.div(
  ({ theme, isBordered }: { theme: Theme; isBordered?: boolean }) => `
  height: 42px;
  width: 42px;
  border-radius: ${theme.spacing['2']};
  ${isBordered ? `border: 1px solid ${theme.colors.surface.border.gray.subtle};` : ''}
  overflow: hidden;
`,
);
