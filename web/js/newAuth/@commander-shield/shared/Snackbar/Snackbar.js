import React from 'react';
import styled from 'styled-components';
import PropTypes from 'prop-types';
import { Transition } from 'react-transition-group';
import Space from '@razorpay/blade-old/src/atoms/Space';
import View from '@razorpay/blade-old/src/atoms/View';
import Text from '@razorpay/blade-old/src/atoms/Text';
import Flex from '@razorpay/blade-old/src/atoms/Flex';
import { getColor } from '@razorpay/blade-old/src/_helpers/theme';
import automation from '@razorpay/blade-old/src/_helpers/automation-attributes';
import Close from '@razorpay/blade-old/src/icons/Close';
import Success from '@razorpay/blade-old/src/icons/Success';
import Button from '../Button';

const styles = {
  color() {
    return 'light.900';
  },
  backgroundColor({ theme, color }) {
    return getColor(theme, color);
  },
  fontColor({ theme }) {
    return getColor(theme, 'light.900');
  },
  opacity({ status }) {
    switch (status) {
      case 'entering':
        return 1;
      case 'entered':
        return 1;
      case 'exiting':
        return 1;
      case 'exited':
        return 1;
      default:
        return 0;
    }
  },
  bottom({ status }) {
    switch (status) {
      case 'entering':
        return '77px';
      case 'entered':
        return '77px';
      case 'exiting':
        return 0;
      case 'exited':
        return 0;
      default:
        return 0;
    }
  },
  visibility({ status }) {
    switch (status) {
      case 'entering':
        return 'visible';
      case 'entered':
        return 'visible';
      case 'exiting':
        return 'visible';
      case 'exited':
        return 'hidden';
      default:
        return 'hidden';
    }
  },
};

const StyledView = styled(View)`
  background-color: ${styles.backgroundColor};
  border-radius: 2px;
  position: absolute;
  left: 0;
  right: 0;
  z-index: 1;
  transition: bottom 400ms ease-in-out;
  opacity: ${styles.opacity};
  bottom: ${styles.bottom};
  visibility: ${styles.visibility};
`;

const Snackbar = ({ icon: Icon, type, onClose, message, color, shouldAnimateIn }) => {
  return (
    <Transition in={shouldAnimateIn} timeout={400} appear={shouldAnimateIn}>
      {(transitionState) => (
        <Space padding={[1.5]} margin={[2.5, 4]}>
          <StyledView type={type} color={color} status={transitionState}>
            <Flex alignItems="center" alignContent="center">
              <View>
                <Flex flex={1} alignItems="center" alignContent="center">
                  <View>
                    <Flex flexShrink={0}>
                      <View>
                        <Icon size="medium" fill="light.900" />
                      </View>
                    </Flex>
                    <Space padding={[0, 1]}>
                      <Text color="light.900" _lineHeight="medium">
                        {message}
                      </Text>
                    </Space>
                  </View>
                </Flex>
                <Button
                  size="xsmall"
                  variant="tertiary"
                  icon={Close}
                  iconAlign="left"
                  align="center"
                  onClick={onClose}
                  variantColor="light"
                  {...automation('ds-snackbar')}
                />
              </View>
            </Flex>
          </StyledView>
        </Space>
      )}
    </Transition>
  );
};

Snackbar.propTypes = {
  icon: PropTypes.elementType,
  color: PropTypes.string,
  type: PropTypes.string,
  onClose: PropTypes.func,
  message: PropTypes.string,
  shouldAnimateIn: PropTypes.bool,
};

Snackbar.defaultProps = {
  icon: Success,
  color: 'positive.900',
  type: 'success',
  onClose: () => {},
  shouldAnimateIn: false,
};

export default Snackbar;
