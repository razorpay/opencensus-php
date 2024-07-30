import styled, { css } from 'styled-components';
import { Theme } from '@razorpay/blade/components';
import sparklePb from 'assets/pricing-bundle/sparkle-banner-mobile.svg';

interface CarouselSlideProps {
  active: boolean;
}
interface CarouselSlidesProps {
  currentSlide: number;
}

const PricingHeaderTag = styled.div(
  ({ theme }: { theme: Theme }) => `
    display: flex;
    justify-content: center;
    align-items: flex-start;
    width: 100%;
    padding: ${theme.spacing[5]}px ${theme.spacing[7]}px;
    flex-direction: column;   
    position: relative;
    z-index: 1;

    &:before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      display: block;
      width: 100%;
      height: 100%;
      background-image: url(${sparklePb}), linear-gradient(131deg, #48D08C 9.95%, #008743 108.91%);
      background-repeat: no-repeat;
      background-position: right top;
      z-index: -1;
    }
`,
);
const PricingSubHeader = styled.div(
  ({ theme }: { theme: Theme }) => `
  display: flex;
  justify-content: space-between;
  align-items: center;
  width: 100%;
  padding: ${theme.spacing[4]}px ${theme.spacing[6]}px;
  position: relative;
  z-index: 1;

  &:before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    display: block;
    width: 100%;
    height: 100%;
    opacity: .9; 
    background: ${theme.colors.feedback.background.positive.subtle};
    z-index: -1;
  }
`,
);

const PricingPlanContainer = styled.div(
  ({ theme }: { theme: Theme }) => `
  border: 1px solid ${theme.colors.interactive.border.positive.faded}; 
  box-shadow: 0px 0px 16px 0px rgba(0, 135, 67, 0.20) inset;
  border-radius: ${theme.border.radius.large}px;
  padding: ${theme.spacing[6]}px;
`,
);
const PricingPlanName = styled.div(
  ({ theme }: { theme: Theme }) => `
  display: flex;
  justify-content: flex-start;
  align-items: center;
  margin-bottom: ${theme.spacing[7]}px;
`,
);
const StylePlanIconMweb = styled.div(
  ({ theme }: { theme: Theme }) => `
  width: unset;
  margin-right: ${theme.spacing[3]}px;
`,
);

const PriceContainer = styled.div`
  h3:after {
    content: ' *';
    color: ${(props) => props.theme.colors.interactive.text.positive.normal};
  }
`;
const StyleViewMore = styled.div(
  ({ theme }: { theme: Theme }) => `
  display: flex;
  justify-content: center;
  align-items: center;
  margin-top: ${theme.spacing[7]}px;
`,
);
const PricingPlansDetail = styled.div(
  ({ theme, isViewMore }: { theme: Theme; isViewMore: boolean }) => `
  margin-bottom: ${isViewMore ? `${theme.spacing[7]}px` : `${theme.spacing[0]}px`};
`,
);
const StyleSwitchText = styled.div(
  ({ theme }: { theme: Theme }) => `
  display: flex;
  justify-content: flex-start;
  align-items: center;
  & p:last-of-type {
    text-transform: capitalize;
    margin-left: ${theme.spacing[2]}px;
  }
`,
);

const StyleLeftSlide = styled.div(
  ({ theme }: { theme: Theme }) => `
  margin-right: ${theme.spacing[6]}px;
  display: flex;
`,
);
const StyleRightSlide = styled.div(
  ({ theme }: { theme: Theme }) => `
  margin-left: ${theme.spacing[3]}px;
  display: flex;
`,
);
const StyleSlideContainer = styled.div`
  display: flex;
  overflow: hidden;
`;
const StyledCarouselDotWrapper = styled.div(
  ({ theme }: { theme: Theme }) => `
  display: flex;
  justify-content: center;
  align-items: center;
  padding: ${theme.spacing[7]}px 0;
`,
);
const StyledCarouselDot = styled.div(
  ({ theme, isActive }: { theme: Theme; isActive: boolean }) => `
    width: 12px;
    height: 12px;
    border-radius: ${theme.border.radius.round};
    margin-right: ${theme.spacing[3]}px;
    background: ${
      isActive
        ? theme.colors.interactive.text.positive.normal
        : theme.colors.surface.border.gray.muted
    };
    box-shadow: 0px 0px 12px rgba(0, 0, 0, 0.06);
`,
);

const StyledCarouselSlide = styled.div<CarouselSlideProps>`
  flex: 0 0 auto;
  opacity: ${(props) => (props.active ? 1 : 0)};
  transition: all 0.5s ease;
  width: 100%;
`;

const StyledCarouselSlides = styled.div<CarouselSlidesProps>`
  display: flex;
  width: 100%;
  ${(props) =>
    props.currentSlide &&
    css`
      transform: translateX(-${props.currentSlide * 100}%);
    `};
  transition: all 0.5s ease;
`;
export {
  PricingHeaderTag,
  PricingSubHeader,
  PricingPlansDetail,
  PricingPlanName,
  PricingPlanContainer,
  StylePlanIconMweb,
  PriceContainer,
  StyleViewMore,
  StyleSwitchText,
  StyleRightSlide,
  StyleLeftSlide,
  StyledCarouselDot,
  StyledCarouselDotWrapper,
  StyledCarouselSlide,
  StyledCarouselSlides,
  StyleSlideContainer,
};
