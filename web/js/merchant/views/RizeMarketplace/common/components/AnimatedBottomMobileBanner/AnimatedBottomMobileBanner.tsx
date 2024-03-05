import { Theme } from '@razorpay/blade/components';
import styled from 'styled-components';

interface AnimatedBottomMobileBannerProps {
  theme: Theme;
  isVisible: boolean;
}

const AnimatedBottomMobileBanner = styled.div(
  ({ theme, isVisible }: AnimatedBottomMobileBannerProps) => `
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  /**
   * This high z-index is to overlay the mobile filters section on top of the "Help" button.
   * "Help" button in context of Rize marketplace is irrelevant as the support does not provide help with the marketplace.
   * Check with design for more clarifications.
   */
  z-index: 99999;
  transform: ${isVisible ? 'none' : 'translateY(100%)'};
  
  /**
   * transition property is not supported in Blade Box component
   */
  transition: transform ${theme.motion.duration.moderate}ms ${
    theme.motion.easing.standard.revealing
  };
  
  @media (min-width: ${theme.breakpoints.l}px) {
    display: none;
  }
`,
);

export default AnimatedBottomMobileBanner;
