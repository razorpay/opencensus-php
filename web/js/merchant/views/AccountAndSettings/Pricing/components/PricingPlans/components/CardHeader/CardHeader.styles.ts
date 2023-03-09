import styled from 'styled-components';
import { Theme } from '@razorpay/blade/components';

const StyledCardHeader = styled.div(
  ({ theme }: { theme: Theme }) => `
  display: flex;
  justify-content: space-between;
  align-items: center;
  width: 100%;

  @media screen and (max-width: ${theme.breakpoints.m}px) {
    gap: ${theme.spacing[7]}px;
    flex-direction: column;
    align-items: flex-start;
  }
`,
);

const CardHeaderLeftItem = styled.div(
  ({ theme }: { theme: Theme }) => `
  display: grid;
  gap: ${theme.spacing[3]}px;
  align-items: center;
  grid-template-areas:
    'icon title status'
    '. pricing .';

  .icon {
    height: 18px;
    width: 18px;
    grid-area: icon;

    img {
      vertical-align: unset;
    }
  }

  .title {
    grid-area: title;
  }

  .status {
    grid-area: status;
  }

  .pricing {
    grid-area: pricing;
  }
`,
);

const ProgressBarContainer = styled.div(
  ({ theme }: { theme: Theme }) => `
  width: 200px;
  margin-top: -2px; // To compensate for the top margin on the progress bar

  @media screen and (max-width: ${theme.breakpoints.m}px) {
    width: 100%;
  }
`,
);

export { StyledCardHeader, CardHeaderLeftItem, ProgressBarContainer };
