import React from 'react';
import PropTypes from 'prop-types';
import styled from 'styled-components';
import View from '@razorpay/blade-old/src/atoms/View';
import Flex from '@razorpay/blade-old/src/atoms/Flex';
import Space from '@razorpay/blade-old/src/atoms/Space';

const ScrollView = styled(View)`
  overflow-x: hidden;
  overflow-y: auto;
  overflow-y: overlay;
  scrollbar-width: thin;
  &::-webkit-scrollbar {
    width: 20px;
  }
  &::-webkit-scrollbar-thumb {
    border: 8px solid rgba(0, 0, 0, 0);
    background-clip: padding-box;
    border-radius: 10px;
    background-color: ${(props) => props.theme.colors.shade[930]};
  }
`;

const ScreenContent = ({ children }) => {
  return (
    <Space padding={[0, 4, 2.5, 4]}>
      <Flex flexDirection="column" flexGrow={1}>
        <ScrollView>{children}</ScrollView>
      </Flex>
    </Space>
  );
};

ScreenContent.propTypes = {
  children: PropTypes.node,
};

export default ScreenContent;
