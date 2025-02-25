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
import Button from '../Button';
import Modal from '../Modal';
import DesktopOnlyView from '../responsiveView/DesktopOnlyView';
import MobileOnlyView from '../responsiveView/MobileOnlyView';

const CloseIconView = styled(View)`
  position: absolute;
  right: 20px;
  top: 20px;
  cursor: pointer;
`;

const TermsAndConditionModal = ({ closeModal, handleAccept }) => {
  return (
    <Modal>
      <CloseIconView onClick={closeModal}>
        <Close size="xlarge" fill="shade.980" />
      </CloseIconView>
      <Space margin={[2, 0, 0, 0]}>
        <View>
          <Heading weight="bold" size="xlarge">
            Terms & Conditions
          </Heading>
          <Space padding={[0.5, 0]}>
            <Text size="large" color="shade.980">
              I have read and understood the{' '}
              <a href="https://razorpay.com/terms" target="_blank" rel="noopener noreferrer">
                Terms &amp; Conditions
              </a>
              ,{' '}
              <a href="https://razorpay.com/agreement" target="_blank" rel="noopener noreferrer">
                Merchant Agreement
              </a>{' '}
              and the{' '}
              <a href="https://razorpay.com/privacy" target="_blank" rel="noopener noreferrer">
                Privacy Policy
              </a>
              {'. '}
              By accepting, I agree to abide by the rules at all times.
            </Text>
          </Space>
          <Space padding={[0.5, 0]}>
            <Text size="medium" weight="bold" color="shade.980">
              Note: You will need to 'Accept' the above conditions to be able to Login on your
              dashboard and perform dashboard related activities.
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
                  <Button variant="secondary" onClick={closeModal}>
                    Cancel
                  </Button>
                </Space>
              </Size>
              <Size width="100%">
                <Button onClick={handleAccept}>Accept</Button>
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
                  <Button variant="secondary" onClick={closeModal}>
                    Cancel
                  </Button>
                </Space>
              </Size>
              <Size width="100%">
                <Button onClick={handleAccept}>Accept</Button>
              </Size>
            </View>
          </Flex>
        </Space>
      </MobileOnlyView>
    </Modal>
  );
};

TermsAndConditionModal.propTypes = {
  closeModal: PropTypes.func.isRequired,
  handleAccept: PropTypes.func.isRequired,
};

export default TermsAndConditionModal;
