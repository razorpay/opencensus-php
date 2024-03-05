import { Theme } from '@razorpay/blade/components';
import styled from 'styled-components';

export const OfferStripContainer = styled.div(
  ({ theme, type }: { theme: Theme; type: 'light' | 'dark' }) => `
    padding: ${theme.spacing[3]}px;
    border-radius: ${theme.border.radius.medium}px;
    width: fit-content;
    display: flex;
    background: ${
      type === 'dark'
        ? theme.colors.brand.primary[700]
        : 'linear-gradient(90deg, rgba(21, 102, 241, 0.18) 0.31%, rgba(21, 102, 241, 0.00) 108.4%)'
    };
    `,
);
