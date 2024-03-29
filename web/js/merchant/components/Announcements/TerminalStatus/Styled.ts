import { Theme } from '@razorpay/blade/components';
import styled from 'styled-components';

const getBorderColor = (theme, status) => {
  if (status === 'success') {
    return theme.colors.feedback.background.positive.intense;
  }
  if (status === 'rejected') {
    return theme.colors.feedback.background.negative.intense;
  }

  return theme.colors.feedback.background.notice.intense;
};

export const Banner = styled.div(
  ({ status, theme }: { status: string; theme: Theme }) => `
  background: ${theme.colors.surface.background.gray.intense};
  box-shadow: ${theme.elevation.lowRaised};
  border-radius: ${theme.border.radius.medium}px;
  border-top: 7px solid ${getBorderColor(theme, status)};
  margin: ${theme.spacing[5]}px;
  padding: ${theme.spacing[6]}px;
  display: flex;
  align-items: center;

  div {
    margin-right: ${theme.spacing[3]}px;
  }
`,
);

export const Description = styled.p(
  ({ theme }) => `
  color: ${theme.colors.surface.text.gray.subtle};
  font-weight: ${theme.typography.fonts.weight.regular};
  font-size: ${theme.typography.fonts.size[100]}px;
  margin-bottom: ${theme.spacing[4]}px;
`,
);
