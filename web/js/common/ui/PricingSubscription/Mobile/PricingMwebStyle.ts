import styled, { css } from 'styled-components';
import { Theme } from '@razorpay/blade/components';
import sparklePb from 'assets/sparklePb.png';
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
    background-image: url(${sparklePb}), linear-gradient(130.97deg, #C8BFFF 9.95%, #553EDF 108.91%);
    background-repeat: no-repeat;
    background-position: right top;
    flex-direction: column;
    & > h4 {
        color: ${theme.colors.surface.background.level2.lowContrast};
    }
`,
);
const PricingSubHeader = styled.div(
  ({ theme }: { theme: Theme }) => `
  display: flex;
  justify-content: space-between;
  align-items: center;
  width: 100%;
  padding: 0 ${theme.spacing[5]}px;
  background: rgba(114, 95, 231, 0.1);
`,
);
const PricingBadge = styled.div(
  ({
    theme,
    addColor,
    addBackgroundColor,
  }: {
    theme: Theme;
    addColor?: boolean | undefined;
    addBackgroundColor?: boolean | undefined;
  }) => `
  margin: ${theme.spacing[4]}px ${theme.spacing[0]}px; 
   p {
    color: ${
      addColor
        ? theme.colors.surface.background.level2.lowContrast
        : 'linear-gradient(130.97deg, #c8bfff 9.95%, #553edf 108.91%)'
    };
    }
    & > div { 
        background: ${addBackgroundColor ? 'rgba(114, 95, 231, 0.1)' : 'rgba(255, 255, 255, 0.2)'};
    }
  }
`,
);

const PricingPlanContainer = styled.div(
  ({ theme }: { theme: Theme }) => `
  border: 1px solid #bdb3ff; // TODO: remove with blade color format
  box-shadow: inset 0px 0px 16px rgba(85, 62, 223, 0.2);
  border-radius: ${theme.border.radius.large}px;
  padding: ${theme.spacing[5]}px;
`,
);
const PricingPlanName = styled.div(
  ({ theme }: { theme: Theme }) => `
  display: flex;
  justify-content: flex-start;
  align-items: center;
  margin-bottom: ${theme.spacing[7]}px;
  // TODO: remove with blade color format
  & > h2 {
    color: #9586f2;
  }
`,
);
const StylePlanIconMweb = styled.div(
  ({ theme }: { theme: Theme }) => `
  width: unset;
  margin-right: ${theme.spacing[4]}px;
`,
);
const StyleDescription = styled.div(
  ({ theme }: { theme: Theme }) => `
  margin-bottom: ${theme.spacing[5]}px;
  & > p {
    color: ${theme.colors.surface.text.subdued.lowContrast}
  }
`,
);

const StylePrice = styled.div`
  & > h2 {
    color: #9586f2;
  }
  h2:after {
    content: ' *';
    color: #9586f2;
  }
`;
const StyleViewMore = styled.div(
  ({ theme }: { theme: Theme }) => `
  display: flex;
  justify-content: center;
  align-items: center;
  margin-top: ${theme.spacing[7]}px;
  & > p {
    margin-right: ${theme.spacing[4]}px;
    color: ${theme.colors.surface.text.subdued.lowContrast}
  }
`,
);
const PricingPlansDetail = styled.div(
  ({ theme, isViewMore }: { theme: Theme; isViewMore: boolean }) => `
  margin-bottom: ${isViewMore ? `${theme.spacing[7]}px` : `${theme.spacing[0]}px`};
  & > p:first-of-type {
    margin-right: ${theme.spacing[4]}px;
    display:inline;
    color: ${theme.colors.surface.text.subdued.lowContrast}
  }
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
        ? 'linear-gradient(126deg, #C8BFFF 9.01%, #553EDF 98.6%)'
        : theme.colors.surface.border.normal.lowContrast
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
  PricingBadge,
  PricingPlansDetail,
  PricingPlanName,
  PricingPlanContainer,
  StylePlanIconMweb,
  StyleDescription,
  StylePrice,
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
