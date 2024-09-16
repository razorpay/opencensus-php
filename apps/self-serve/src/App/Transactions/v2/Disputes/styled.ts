import { Theme } from '@razorpay/blade/components';
import styled from 'styled-components';

const getGraphSectionBackgroundColor = (theme, status) => {
  if (status === 'open') {
    return theme.colors.feedback.icon.negative.intense;
  }
  if (status === 'under_review') {
    return theme.colors.feedback.background.notice.intense;
  }
  if (status === 'won') {
    return theme.colors.feedback.background.positive.intense;
  }
  if (status === 'lost') {
    return theme.colors.surface.icon.gray.muted;
  }
  if (status === 'closed') {
    return theme.colors.interactive.text.neutral.subtle;
  }

  return theme.colors.interactive.text.neutral.subtle;
};

export const GraphSection = styled.div(
  ({
    status,
    theme,
    distributionPercentage,
  }: {
    theme: Theme;
    status: string;
    distributionPercentage: number;
  }) => `
      height: 28px;
      width: ${distributionPercentage}%;
      background: ${getGraphSectionBackgroundColor(theme, status)};
      opacity: 0.8;
      &:hover {
        opacity: 1;
      }
    `,
);

export const DisputeIndicators = styled.div(
  ({ status, theme }: { theme: Theme; status: string }) => `
      height: 10px;
      width: 10px;
      border-radius: ${theme.border.radius.round};
      background: ${getGraphSectionBackgroundColor(theme, status)};
      opacity: 0.4;

    `,
);
