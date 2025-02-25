import React from 'react';
import styled from 'styled-components';
import View from '@razorpay/blade-old/src/atoms/View';
import Size from '@razorpay/blade-old/src/atoms/Size';
import Space from '@razorpay/blade-old/src/atoms/Space';
import Flex from '@razorpay/blade-old/src/atoms/Flex';
import { makePxValue } from '@razorpay/blade-old/src/_helpers/theme';

const StyledLoader = styled(View)`
  border: 3px solid ${(props) => props.theme.bladeOld.colors.primary[700]};
  border-top: 3px solid ${(props) => props.theme.bladeOld.colors.background[600]};
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

const FullPageView = styled(View)`
  position: absolute;
  top: 0;
  bottom: 0;
  left: 0;
  right: 0;
`;

const MainContentFullPageView = styled(View)`
  position: absolute;
  top: 0;
  bottom: 0;
  left: 0;
  right: 0;
  margin-left: 248px;
`;

export interface LoaderPropsT {
  height?: number | string;
  width?: number | string;
}

const Loader: React.FC<LoaderPropsT> = ({ width = 4, height = 4 }) => {
  return (
    <Size width={width} height={height}>
      <StyledLoader role="loader" />
    </Size>
  );
};

export const CenterLoader: React.FC<LoaderPropsT & { margin?: [number, number] }> = ({
  margin = [6, 2],
  ...LoaderProps
}) => {
  return (
    <Flex flexDirection="row" justifyContent="center">
      <Space margin={margin}>
        <View>
          <Loader {...LoaderProps} />
        </View>
      </Space>
    </Flex>
  );
};

export const FullPageLoader: React.FC<LoaderPropsT> = ({ ...LoaderProps }) => {
  return (
    <Flex flexDirection="column" justifyContent="center" alignItems="center">
      <FullPageView>
        <Loader {...LoaderProps} />
      </FullPageView>
    </Flex>
  );
};

export const FullPageLoaderCenterToMainContent: React.FC<LoaderPropsT> = ({ ...LoaderProps }) => {
  return (
    <Flex flexDirection="column" justifyContent="center" alignItems="center">
      <MainContentFullPageView>
        <Loader {...LoaderProps} />
      </MainContentFullPageView>
    </Flex>
  );
};

export default Loader;
