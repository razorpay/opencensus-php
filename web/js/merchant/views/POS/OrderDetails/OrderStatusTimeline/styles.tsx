import styled from 'styled-components';
import { Theme } from '@razorpay/blade/components';

export const OrderStatusContainer = styled.div(
  ({
    theme,
    isActive,
    isMobile,
    isDimmed,
  }: {
    theme: Theme;
    isActive: boolean;
    isMobile?: boolean;
    isDimmed?: boolean;
  }) => `
        position: ${isMobile ? 'static' : 'static'};
        padding: ${theme.spacing[3]}px  ${theme.spacing[2]}px;
        background-color: ${
          isActive && !isDimmed
            ? theme.colors.feedback.icon.positive.lowContrast
            : isActive && isDimmed
            ? theme.colors.feedback.background.neutral.highContrast
            : 'none'
        };
        margin: ${theme.spacing[isMobile ? 0 : 3]}px ${theme.spacing[6]}px;
        font-size: ${theme.typography.fonts.size[100]};
        color: ${
          isActive || isDimmed
            ? theme.colors.surface.text.normal.highContrast
            : theme.colors.surface.text.normal.lowContrast
        };
        text-align: ${isMobile && !isActive ? 'left' : 'center'};
        border-radius: ${theme.border.radius.medium}px;
        width: 125px;
    `,
);

export const IconContainer = styled.div(
  ({ theme, status }: { theme: Theme; status: string }) => `
    display: flex;
    align-items: center;
    padding: ${theme.spacing[2]}px;
    border-radius: ${theme.border.radius.round};
    background-color: ${
      status === 'active'
        ? theme.colors.feedback.background.positive.lowContrast
        : status === 'failed'
        ? theme.colors.feedback.background.negative.lowContrast
        : theme.colors.feedback.background.neutral.lowContrast
    }
  `,
);
