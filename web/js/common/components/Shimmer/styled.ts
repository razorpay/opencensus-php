import styled, { css, keyframes } from 'styled-components';

const getVariantStyles = ({ variant, borderRadius }) => {
  switch (variant) {
    case 'circular':
      return css`
        border-radius: 50%;
      `;
    case 'rounded':
      return css`
        border-radius: ${borderRadius};
      `;
    default:
      return css``;
  }
};

const shimmerEffectFrame = keyframes`
  0% {
    background-position: -400px 0;
  }
  100% {
    background-position: 400px 0;
  }
`;

export const ShimmerBar = styled.span`
  height: 20px;
  width: 100%;
  background: rgba(255, 255, 255, 0.3);
  background-image: linear-gradient(
    90deg,
    rgba(204, 204, 204, 0.1) 0%,
    #cccccc 50%,
    rgba(204, 204, 204, 0.1) 100%
  );
  opacity: 0.3;
  border-radius: 2px;
  ${({ variant, borderRadius }) => getVariantStyles({ variant, borderRadius })}
  background-size: 800px 100px;
  animation-name: ${shimmerEffectFrame};
  animation-duration: 1.2s;
  animation-iteration-count: infinite;
  animation-timing-function: linear;
  display: block;
`;
