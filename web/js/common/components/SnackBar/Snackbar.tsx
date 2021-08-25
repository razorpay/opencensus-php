import React from 'react';
import styled from 'styled-components';
import { Transition } from 'react-transition-group';
import Icon from '@razorpay/blade-old/src/atoms/Icon';
import Space from '@razorpay/blade-old/src/atoms/Space';
import View from '@razorpay/blade-old/src/atoms/View';
import Text from '@razorpay/blade-old/src/atoms/Text';
import Flex from '@razorpay/blade-old/src/atoms/Flex';
import { getColor } from '@razorpay/blade-old/src/_helpers/theme';
import Button from '@razorpay/blade-old/src/atoms/Button';

export interface SnackbarT {
  icon: string;
  color: string;
  type: string;
  onClose: () => void;
  message: string;
  shouldAnimateIn: boolean;
}

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
        return '97px';
      case 'entered':
        return '97px';
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

const SnackbarText = styled(Text)`
  word-break: break-word;
`;

const Snackbar: React.FC<SnackbarT> = ({
  icon = 'success',
  type = 'success',
  color = 'positive.900',
  onClose,
  message,
  shouldAnimateIn = false,
}) => {
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
                        <Icon name={icon} size="medium" fill="light.900" />
                      </View>
                    </Flex>
                    <Space padding={[0, 1]}>
                      <SnackbarText color="light.900" _lineHeight="medium">
                        {message}
                      </SnackbarText>
                    </Space>
                  </View>
                </Flex>
                <Button
                  size="xsmall"
                  variant="tertiary"
                  icon="close"
                  iconAlign="left"
                  align="center"
                  onClick={onClose}
                  variantColor="light"
                  testID="ds-snackbar"
                />
              </View>
            </Flex>
          </StyledView>
        </Space>
      )}
    </Transition>
  );
};

export default Snackbar;
