import React from 'react';
import styled from 'styled-components';
import View from '@razorpay/blade/src/atoms/View';
import Size from '@razorpay/blade/src/atoms/Size';
import { makePxValue } from '@razorpay/blade/src/_helpers/theme';

const StyledLoader = styled(View)`
  border: 3px solid ${(props) => props.theme.colors.primary[700]};
  border-top: 3px solid ${(props) => props.theme.colors.background[600]};
  border-radius: 50%;
  width: ${(props) => makePxValue(props.width)};
  height: ${(props) => makePxValue(props.height)};
  animation: spin 0.6s linear infinite;
  @keyframes spin {
    0% {
      transform: rotate(0deg);
    }
    100% {
      transform: rotate(360deg);
    }
  }
`;

interface LoaderPropsT {
  height?: number | string;
  width?: number | string;
}

const Loader: React.FC<LoaderPropsT> = ({ width = 4, height = 4 }) => {
  return (
    <Size width={width} height={height}>
      <StyledLoader />
    </Size>
  );
};

export default Loader;
