import { Theme } from '@razorpay/blade/components';
import styled from 'styled-components';

const DealBox = styled.div(
  ({ theme }: { theme: Theme }) => `
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: ${theme.typography.fonts.weight.bold};
  text-align: center;
  width: 100%;
  padding: ${theme.spacing[3]}px ${theme.spacing[5]}px;

  border-radius: ${theme.border.radius.medium}px;
  border: 1px dashed ${theme.colors.feedback.background.positive.intense};

  color: ${theme.colors.feedback.background.positive.intense};
  background-color: ${theme.colors.feedback.background.positive.subtle};

  @media (min-width: ${theme.breakpoints.l}px) {
    max-width: 190px;
  }
`,
);

export default DealBox;
