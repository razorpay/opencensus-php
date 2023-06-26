import { Theme } from '@razorpay/blade/components';
import styled from 'styled-components';

export const BulletPointsLeftContainer = styled.div(
  ({ theme }: { theme: Theme }) => `
    background: ${theme.colors.feedback.background.neutral.highContrast};
    border-radius: ${theme.border.radius.large}px;
    padding: ${theme.spacing[6]}px;
    max-width: 340px;
    `,
);

export const BulletPointsRightContainer = styled.div(
  ({ theme }: { theme: Theme }) => `
    border: ${theme.border.width.thin}px solid ${theme.colors.surface.text.muted.lowContrast};
    border-radius: ${theme.border.radius.large}px;
    padding: ${theme.spacing[6]}px;
    `,
);

export const BankingXHeadingV2 = styled.div(
  ({ theme }: { theme: Theme }) => `
    color: ${theme.colors.surface.text.normal.highContrast};
    font-size: ${theme.typography.fonts.size[800]}px;
    font-weight: ${theme.typography.fonts.weight.bold};
    line-height: 40px;
    `,
);

export const BankingXSubHeadingV2 = styled.div(
  ({ theme }: { theme: Theme }) => `
    color: ${theme.colors.brand.primary[500]};
    font-size: ${theme.typography.fonts.size[800]}px;
    font-weight: ${theme.typography.fonts.weight.bold};
    line-height: 40px;
    `,
);
