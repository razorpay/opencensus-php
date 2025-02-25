import React from 'react';
import PropTypes from 'prop-types';
import styled from 'styled-components';
import View from '@razorpay/blade-old/src/atoms/View';
import Flex from '@razorpay/blade-old/src/atoms/Flex';
import Space from '@razorpay/blade-old/src/atoms/Space';
import Size from '@razorpay/blade-old/src/atoms/Size';

const OverlayContainer = styled(View)`
  position: fixed;
  top: 0;
  bottom: 0;
  width: 100%;
  left: 0;
  right: 0;
  background: ${(props) => props.theme.colors.shade[950]};
  z-index: 10;
`;

const ContentContainer = styled(View)`
  background: ${(props) => props.theme.colors.background[100]};
  border-radius: 4px;
  margin: auto;
  position: relative;
  @media (max-width: 768px) {
    max-width: 312px;
  }
`;

const Modal = ({ children }) => {
  return (
    <Flex justifyContent="space-between" flexDirection="column">
      <OverlayContainer>
        <Flex justifyContent="space-between" flexDirection="column">
          <Space padding={[3.5, 5]}>
            <Size maxWidth="464px">
              <ContentContainer>{children}</ContentContainer>
            </Size>
          </Space>
        </Flex>
      </OverlayContainer>
    </Flex>
  );
};

Modal.propTypes = {
  children: PropTypes.node.isRequired,
};

export default Modal;
