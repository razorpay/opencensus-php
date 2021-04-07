import React, { useState } from 'react';
import styled from 'styled-components';
import View from '@razorpay/blade/src/atoms/View';
import Space from '@razorpay/blade/src/atoms/Space';
import Button from '@razorpay/blade/src/atoms/Button';
import Text from '@razorpay/blade/src/atoms/Text';
import { Modal, ModalBody } from 'v2/components/Modal';
import { analyticsTrack } from '../../../../services/tracking/segment';
import { useApp } from 'v2/context/App';

export interface DedupePropsT {
  isOpen: boolean;
}

const Container = styled(View)`
  margin: auto;
  text-align: center;
`;

const Dedupe: React.FC<DedupePropsT> = ({ isOpen }) => {
  const [shouldCloseModal, setCloseModal] = useState(false);
  const { user } = useApp();
  const backToDashboard = () => {
    window.location.href = '/app/dashboard';
  };
  const openCustomerSupport = () => {
    analyticsTrack({
      objectName: 'SignUp',
      actionName: 'contact support',
      screen: 'home page',
      eventAction: 'initiated',
      user,
    });
    window.rzpTicketSystem.openModal('#ticket');
    setCloseModal(true);
  };
  return (
    <Modal
      onClose={() => {}}
      isOpen={shouldCloseModal ? !shouldCloseModal : isOpen}
      closeable={false}
    >
      <ModalBody>
        <Container>
          {/* pending from designs */}
          {/* <img src={BannerModal} /> */}
          <View>
            <Space margin={[1, 0, 1, 0]}>
              <View>
                <Text
                  size="medium"
                  weight="bold"
                  _letterSpacing="small"
                  align="center"
                  _lineHeight="medium"
                >
                  Clarification required
                </Text>
              </View>
            </Space>

            <Text size="medium" align="center">
              We need some clarification regarding your submitted details. Please contact support to
              activate your account
            </Text>
          </View>
          <Space margin={[2, 0, 2, 0]}>
            <View>
              <Button onClick={openCustomerSupport} block>
                Contact Support
              </Button>
              <Space margin={[1, 0, 0, 0]}>
                <View>
                  <Button onClick={backToDashboard} block variant="tertiary">
                    I'll Do It Later
                  </Button>
                </View>
              </Space>
            </View>
          </Space>
        </Container>
      </ModalBody>
    </Modal>
  );
};

export default Dedupe;
