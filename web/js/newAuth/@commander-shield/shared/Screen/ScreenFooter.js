import React from 'react';
import PropTypes from 'prop-types';
import styled from 'styled-components';
import View from '@razorpay/blade-old/src/atoms/View';
import Space from '@razorpay/blade-old/src/atoms/Space';

const FooterView = styled(View)`
  border-top: 1px solid ${({ theme }) => theme.colors.shade[930]};
  background-color: #fff;
  position: relative;
  z-index: 2;
`;

const GradientView = styled(View)`
  position: absolute;
  pointer-events: none;
  height: 48px;
  width: 100%;
  content: '';
  left: 0;
  top: -48px;
  background: linear-gradient(
    0deg,
    #f9fbfe 0%,
    rgba(249, 251, 254, 0.6) 29.69%,
    rgba(249, 251, 254, 0) 100%
  );
`;

const WrapperView = styled(View)`
  position: relative;
`;

const ScreenFooter = ({ children }) => {
  return (
    <WrapperView>
      <GradientView />
      <FooterView>
        <Space padding={[2.5, 4]}>
          <View>{children}</View>
        </Space>
      </FooterView>
    </WrapperView>
  );
};

ScreenFooter.propTypes = {
  children: PropTypes.node,
};

export default ScreenFooter;
