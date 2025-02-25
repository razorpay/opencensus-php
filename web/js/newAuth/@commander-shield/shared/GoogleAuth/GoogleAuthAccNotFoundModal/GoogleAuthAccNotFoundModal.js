import React from 'react';
import PropTypes from 'prop-types';
import styled from 'styled-components';
import View from '@razorpay/blade-old/src/atoms/View';
import Flex from '@razorpay/blade-old/src/atoms/Flex';
import Space from '@razorpay/blade-old/src/atoms/Space';
import Size from '@razorpay/blade-old/src/atoms/Size';
import Heading from '@razorpay/blade-old/src/atoms/Heading';
import Text from '@razorpay/blade-old/src/atoms/Text';
import Close from '@razorpay/blade-old/src/icons/Close';
import Button from '../../Button';
import Modal from '../../Modal';
import DesktopOnlyView from '../../responsiveView/DesktopOnlyView';
import MobileOnlyView from '../../responsiveView/MobileOnlyView';

const CloseIconView = styled(View)`
  position: absolute;
  right: 20px;
  top: 20px;
  cursor: pointer;
`;

const InlineText = styled(Text)`
  display: inline;
  word-break: break-all;
`;

const USL_LINK =
  SHIELD_STAGE === 'production'
    ? 'https://accounts.razorpay.com/'
    : 'https://accounts.np.razorpay.in/';

const GoogleAuthAccNotFoundModal = ({ closeModal, handleRetry, handleCreateAccount, email }) => {
  return (
    <Modal>
      <CloseIconView onClick={closeModal}>
        <Close size="xlarge" fill="shade.980" />
      </CloseIconView>
      <Space margin={[2, 0, 0, 0]}>
        <View>
          <Heading weight="bold" size="xlarge">
            Creating a new account?
          </Heading>
          <Space padding={[0.5, 0]}>
            <Text size="large" color="shade.980">
              There is no Razorpay account registered with the email id
              <InlineText size="large" weight="bold" color="shade.980">
                {` ${email}`}
              </InlineText>
              . Do you wish to create a new account with this email?
            </Text>
          </Space>
        </View>
      </Space>
      <DesktopOnlyView>
        <Space margin={[5.5, 0, 0, 0]}>
          <Flex flexDirection="row" justifyContent="flex-end">
            <View>
              <Size width="100%">
                <Space margin={[0, 1, 0, 0]}>
                  <Button
                    variant="secondary"
                    onClick={() => {
                      if (window.location.hostname.includes('curlec')) {
                        window.location.href = USL_LINK;
                      } else {
                        handleCreateAccount();
                      }
                    }}
                  >
                    Create New Account
                  </Button>
                </Space>
              </Size>
              <Size width="100%">
                <Button onClick={handleRetry}>Retry Login</Button>
              </Size>
            </View>
          </Flex>
        </Space>
      </DesktopOnlyView>
      <MobileOnlyView>
        <Space margin={[5.5, 0, 0, 0]}>
          <Flex flexDirection="column-reverse" justifyContent="flex-end">
            <View>
              <Size width="100%">
                <Space margin={[1, 0, 0, 0]}>
                  <Button variant="secondary" onClick={handleCreateAccount}>
                    Create New Account
                  </Button>
                </Space>
              </Size>
              <Size width="100%">
                <Button onClick={handleRetry}>Retry Login</Button>
              </Size>
            </View>
          </Flex>
        </Space>
      </MobileOnlyView>
    </Modal>
  );
};

GoogleAuthAccNotFoundModal.propTypes = {
  handleCreateAccount: PropTypes.func,
  handleRetry: PropTypes.func,
  closeModal: PropTypes.func,
  email: PropTypes.string,
};

export default GoogleAuthAccNotFoundModal;
