import React from 'react';
import styled from 'styled-components';
import View from '@razorpay/blade-old/src/atoms/View';

const StyledLoader = styled(View)`
  border: 3px solid ${(props) => props.theme.colors.primary[700]};
  border-top: 3px solid ${(props) => props.theme.colors.background[600]};
  border-radius: 50%;
  width: 32px;
  height: 32px;
  animation: spin 0.6s linear infinite;
  left: 50%;
  bottom: 0;
  margin-left: -20px;
  margin-bottom: 100px;
  position: absolute;

  @keyframes spin {
    0% {
      transform: rotate(0deg);
    }
    100% {
      transform: rotate(360deg);
    }
  }
`;

const StyledView = styled(View)`
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: ${(props) => props.theme.colors.tone[940]};
  z-index: 2;
`;

const Loader = () => {
  return (
    <StyledView>
      <StyledLoader />
    </StyledView>
  );
};

export default Loader;
