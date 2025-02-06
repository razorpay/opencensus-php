import { Theme } from '@razorpay/blade/components';
import styled from 'styled-components';

export const StyledCardContainer = styled.div(({ theme }: { theme: Theme }) => {
  return `
    border-radius:  ${theme.border.radius.large}px;
    border: 1px solid ${theme.colors.surface.border.gray.muted};
   `;
});

export const StyledPaymentCard = styled.div(
  ({
    theme,
    hasBottomBorder,
    isDisabled,
    isLoading,
  }: {
    theme: Theme;
    hasBottomBorder: boolean;
    isDisabled: boolean;
    isLoading: boolean;
  }) => {
    return `
    padding:${theme.spacing[5]}px ${theme.spacing[4]}px;
    display:flex;
    align-items:center;
    gap:${theme.spacing[5]}px;
    border-bottom: ${hasBottomBorder ? `1px solid ${theme.colors.surface.border.gray.muted}` : ''} ;
    cursor: ${isDisabled || isLoading ? 'not-allowed' : 'pointer'};
    pointer-events: ${isDisabled || isLoading ? 'none' : 'auto'};
   `;
  },
);
