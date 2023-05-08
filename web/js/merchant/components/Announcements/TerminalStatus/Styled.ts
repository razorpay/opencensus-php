import { Theme } from '@razorpay/blade/components';
import styled from 'styled-components';

const getBorderColor = (theme, status) => {
  if (status === 'success') {
    return theme.colors.feedback.background.positive.highContrast;
  }
  if (status === 'rejected') {
    return theme.colors.feedback.background.negative.highContrast;
  }

  return theme.colors.feedback.background.notice.highContrast;
};

export const Banner = styled.div(
  ({ status, theme }: { status: string; theme: Theme }) => `
  background: ${theme.colors.surface.background.level2.lowContrast};
  box-shadow: 0px 4px 10px ${theme.shadows.color.level[1]};
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

export const Title = styled.p(
  ({ theme }) => `
  color: ${theme.colors.brand.gray[200].highContrast};
  font-size: ${theme.typography.fonts.size[200]}px;
  font-weight: ${theme.typography.fonts.weight.bold};
`,
);

export const Description = styled.p(
  ({ theme }) => `
  color: ${theme.colors.brand.gray[200].highContrast};
  font-weight: ${theme.typography.fonts.weight.regular};
  font-size: ${theme.typography.fonts.size[100]}px;
  margin-bottom: ${theme.spacing[4]}px;
`,
);
