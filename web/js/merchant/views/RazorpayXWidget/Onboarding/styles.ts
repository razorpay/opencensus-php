import { Theme } from '@razorpay/blade/components';
import styled from 'styled-components';

export const BulletPointsLeftContainer = styled.div(
  ({ theme }: { theme: Theme }) => `
    background: ${theme.colors.feedback.border.neutral.subtle};
    border-radius: ${theme.border.radius.large}px;
    padding: ${theme.spacing[6]}px;
    max-width: 340px;
    `,
);

export const BulletPointsRightContainer = styled.div(
  ({ theme }: { theme: Theme }) => `
    border: ${theme.border.width.thin}px solid ${theme.colors.surface.text.gray.muted};
    border-radius: ${theme.border.radius.large}px;
    padding: ${theme.spacing[6]}px;
    `,
);

export const BankingXHeadingV2 = styled.div(
  ({ theme }: { theme: Theme }) => `
    color: ${theme.colors.surface.text.staticWhite.normal};
    font-size: ${theme.typography.fonts.size[600]}px;
    font-weight: ${theme.typography.fonts.weight.bold};
    line-height: 40px;
    `,
);

export const BankingXSubHeadingV2 = styled.div(
  ({ theme }: { theme: Theme }) => `
    color: ${theme.colors.surface.background.primary.intense};
    font-size: ${theme.typography.fonts.size[600]}px;
    font-weight: ${theme.typography.fonts.weight.bold};
    line-height: 40px;
    `,
);
