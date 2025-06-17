import styled from 'styled-components';
import { Theme } from '@razorpay/blade/components';

export const SalesPocBannerContainer = styled.div(
  ({ theme }: { theme: Theme }) => `
        margin-bottom: ${theme.spacing[5]}px;
        padding: ${theme.spacing[5]}px ${theme.spacing[7]}px;
        border: ${theme.border.width.thin}px solid ${theme.colors.surface.border.gray.muted};
        background: linear-gradient(90deg, rgba(115, 186, 205, 0.09) -3.9%, rgba(55, 197, 235, 0.01) 53.55%, rgba(0, 140, 177, 0.00) 94.21%);
    `,
);
