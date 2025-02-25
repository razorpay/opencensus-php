import React from 'react';
import PropTypes from 'prop-types';
import Flex from '@razorpay/blade-old/src/atoms/Flex';
import View from '@razorpay/blade-old/src/atoms/View';
import Size from '@razorpay/blade-old/src/atoms/Size';
import ScreenContent from './ScreenContent';
import ScreenFooter from './ScreenFooter';

const Screen = ({ children }) => {
  return (
    <Size height="100%">
      <Flex flexDirection="column">
        <View>{children}</View>
      </Flex>
    </Size>
  );
};

Screen.propTypes = {
  children: PropTypes.node,
};

Screen.Content = ScreenContent;
Screen.Footer = ScreenFooter;

export default Screen;
