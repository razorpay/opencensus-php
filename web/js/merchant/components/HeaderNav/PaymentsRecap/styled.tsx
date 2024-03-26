import { Theme } from '@razorpay/blade/components';
import styled, { css, keyframes } from 'styled-components';

export const CarouselContainer = styled.div`
  background-color: #000000;
`;

export const PaymentsRecapContainer = styled.div`
  background-color: #000000;
  padding: 15px 0px;
  padding-top: 30px;
`;

export const StyledTextSubHeading = styled.p<{
  fontSize?: string;
}>`
  font-family: 'Nova Square', sans-serif;
  font-weight: 400;
  font-style: normal;
  color: ${(props) => props.color || '#eeeeee'};
  font-size: ${(props) => props.fontSize || '16px'};
`;

export const StyledTextHeading = styled.p<{
  fontSize?: string;
}>`
  font-family: 'Shrikhand', serif;
  font-weight: 400;
  font-style: italic;
  color: ${(props) => props.color || '#eeeeee'};
  font-size: ${(props) => props.fontSize || '16px'};
  line-height: ${(props) => props.fontSize || '16px'};
`;

export const SocialShareBottomSheet = styled.div(
  (_) => `
  [data-blade-component='bottom-sheet'] {
    background-color: rgb(28,40,56);
  }
  [data-testid='bottomsheet-backdrop']{
    background-color: transparent;
  }
`,
);

export const StyledIcon = styled.div(
  ({ theme }: { theme: Theme }) => `
  display: grid;
  place-items: center;
  background-color: #FFFFFF;
  height: ${theme.spacing[10]}px;
  width: ${theme.spacing[10]}px;
  border-radius: ${theme.spacing[2]}px;
  cursor: pointer;
`,
);

const animateBgPosition = keyframes`
  from { background-position: -600px; }
  to { background-position: 0; }
`;

const shimmerAnimation = () => css`
  ${animateBgPosition} 5s infinite linear;
`;

export const BannerBtn = styled.button<{
  isMobileBanner: boolean;
}>`
  position: absolute;
  top: 50%;
  transform: translate(-50%, -50%);
  left: ${(props) => (props.isMobileBanner ? '80%' : '75%')};
  font-size: ${(props) => (props.isMobileBanner ? '10px' : '14px')};
  min-height: ${(props) => (props.isMobileBanner ? '30px' : '36px')};
  padding: ${(props) => (props.isMobileBanner ? '0px 10px' : '0px 15px')};
  width: max-content;
  cursor: pointer;
  background: linear-gradient(
    130deg,
    rgba(255, 211, 54, 1) 0%,
    rgba(255, 211, 54, 1) 45%,
    rgba(255, 255, 255, 1) 50%,
    rgba(255, 211, 54, 1) 65%,
    rgba(255, 211, 54, 1) 100%
  );
  background-color: rgba(255, 211, 54, 1);
  backgroundrepeat: no-repeat;
  backgroundposition: 0px;
  backgroundsize: 300%;
  color: #2d2a26;
  font-weight: 600;
  border-width: 1px;
  border-radius: 2px;
  border-style: solid;
  animation: ${shimmerAnimation};
`;
