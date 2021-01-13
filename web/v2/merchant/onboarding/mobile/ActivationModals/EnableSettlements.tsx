import React from 'react';
import styled from 'styled-components';
import View from '@razorpay/blade/src/atoms/View';
import Space from '@razorpay/blade/src/atoms/Space';
import Button from '@razorpay/blade/src/atoms/Button';
import Link from '@commander/shield/src/shared/Link';
import Text from '@razorpay/blade/src/atoms/Text';
import { useApp } from 'v2/context/App';
import { switchMode } from 'v2/services/mode';
import { Modal, ModalBody } from '../../../../components/Modal';
import BannerModal from './Frame.svg';

export interface EnableSettlementsModalPropsT {
  isOpen: boolean;
}

const Container = styled(View)`
  margin: auto;
  text-align: center;
`;

const EnableSettlementsModal: React.FC<EnableSettlementsModalPropsT> = ({ isOpen }) => {
  const { user } = useApp();
  const onEnableSettlementsClick = () => {
    switchMode(user.current, 'live');
    window.location.href = '/app/onboarding/steps';
  };
  const onExploreToAcceptPaymentsLinkClick = () => {
    window.location.href = '/';
  };
  return (
    <Modal onClose={() => {}} isOpen={isOpen} closeable={false}>
      <ModalBody>
        <Container>
          <img src={BannerModal} />
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
                  Live Payments Enabled!
                </Text>
              </View>
            </Space>

            <Text size="medium" align="center">
              Congratulations! You can start accepting payments from your customers now but you
              would need to enable settlements for the payments to be settled to your account
            </Text>
          </View>
          <Space margin={[2, 0, 0, 0]}>
            <View>
              <Button size="large" onClick={() => onEnableSettlementsClick()} block>
                Enable settlements
              </Button>
            </View>
          </Space>

          <Space margin={[2, 0, 2, 0]}>
            <View>
              <Link weight="bold" onClick={() => onExploreToAcceptPaymentsLinkClick()}>
                Explore products to accept payments
              </Link>
            </View>
          </Space>
        </Container>
      </ModalBody>
    </Modal>
  );
};

export default EnableSettlementsModal;
