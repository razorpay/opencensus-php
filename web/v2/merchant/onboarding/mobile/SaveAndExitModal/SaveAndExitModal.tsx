import React from 'react';
import styled from 'styled-components';
import View from '@razorpay/blade/src/atoms/View';
import Space from '@razorpay/blade/src/atoms/Space';
import Button from '@razorpay/blade/src/atoms/Button';
import Link from '@commander/shield/src/shared/Link';
import Text from '@razorpay/blade/src/atoms/Text';
import { Modal, ModalBody } from '../../../../components/Modal';

export interface ExitPopupProps {
  onClose: () => void;
  isOpen: boolean;
  onContinueFillingDetailsClick?: () => void;
  exitToDashBoardLink?: string;
}

const Container = styled(View)`
  margin: auto;
  text-align: center;
`;

const ExitPopup: React.FC<ExitPopupProps> = ({
  isOpen,
  onClose,
  onContinueFillingDetailsClick,
  exitToDashBoardLink,
}) => {
  return (
    <Modal isOpen={isOpen} onClose={onClose}>
      <ModalBody>
        <Container>
          <Space margin={[1, 0, 1, 0]}>
            <View>
              <Text size="medium" weight="bold" _letterSpacing="small" align="center">
                Are you sure you want to exit?
              </Text>
            </View>
          </Space>
          <Space margin={[0, 0, 4, 0]}>
            <View>
              <Text size="medium" align="center">
                You are just few steps away. Fill the remaining details and start accepting payments
                now
              </Text>
            </View>
          </Space>
          <Button
            onClick={onContinueFillingDetailsClick ? onContinueFillingDetailsClick : onClose}
            block
          >
            Continue filling Details
          </Button>
          <Space margin={[2, 0, 0, 0]}>
            <View>
              <Link href={exitToDashBoardLink ? exitToDashBoardLink : '/app/dashboard'}>
                Exit to dashboard
              </Link>
            </View>
          </Space>
        </Container>
      </ModalBody>
    </Modal>
  );
};

export default ExitPopup;
