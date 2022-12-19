import styled from 'styled-components';
import View from '@razorpay/blade-old/src/atoms/View';

const NavContainer = styled(View)`
  background: #2f3344;
  height: 100vh;
  width: 200px;
`;

const ShimmerBar = styled(View)`
  height: 16px;
  width: 100%;
  background: rgba(255, 255, 255, 0.3);
  background-image: linear-gradient(
    90deg,
    rgba(204, 204, 204, 0.1) 0%,
    #cccccc 50%,
    /* #cccccc 54%, */ rgba(204, 204, 204, 0.1) 100%
  );
  opacity: 0.3;
  border-radius: 2px;
  background-size: 800px 100px;
  animation: placeHolderShimmer 1.2s forwards infinite linear;

  @keyframes placeHolderShimmer {
    0% {
      background-position: -400px 0;
    }
    100% {
      background-position: 400px 0;
    }
  }
`;

const ShimmerGroup = styled(View)`
  display: flex;
  flex-direction: column;
  row-gap: 15px;
  padding-top: 10px;
  margin: 0 16px;
  padding-bottom: 8px;
`;

export { NavContainer, ShimmerBar, ShimmerGroup };
