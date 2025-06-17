import { Theme } from '@razorpay/blade/components';
import styled from 'styled-components';

export const PartnerExclusiveGradientContainer = styled.div(
  ({ isPartnerPricing }: { isPartnerPricing: boolean }) => `
  background: ${
    isPartnerPricing
      ? 'linear-gradient(to right, hsla(36, 56%, 86%, 1), hsla(222, 48%, 95%, 0))'
      : 'transparent'
  };
`,
);

export const PartnerExclusiveBannerGradientContainer = styled.div(
  ({ isPartnerPricing }: { isPartnerPricing: boolean }) => `
  background: ${
    isPartnerPricing
      ? 'linear-gradient(to right, hsla(36, 100%, 26%, 1), hsla(230, 49%, 17%, 0))'
      : 'transparent'
  };
`,
);

export const PartnerExclusivePriceImage = styled.img(
  ({ theme }: { theme: Theme }) => `
  margin-bottom:-${theme.spacing[3]}px;
  `,
);
