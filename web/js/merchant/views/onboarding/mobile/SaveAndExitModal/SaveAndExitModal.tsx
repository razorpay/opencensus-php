import React, { useEffect } from 'react';
import styled from 'styled-components';
import View from '@razorpay/blade-old/src/atoms/View';
import Space from '@razorpay/blade-old/src/atoms/Space';
import Button from '@razorpay/blade-old/src/atoms/Button';
import Link from '@razorpay/commander-shield/src/shared/Link';
import Text from '@razorpay/blade-old/src/atoms/Text';
import { Modal, ModalBody } from 'common/components/Modal';
import useTrackEvents from 'merchant/hooks/useTrackEvents';

export interface ExitPopupProps {
  onClose: () => void;
  isOpen: boolean;
  exitToDashBoardLink?: string;
}

const Container = styled(View)`
  margin: auto;
  text-align: center;
`;

const ExitPopup: React.FC<ExitPopupProps> = ({ isOpen, onClose, exitToDashBoardLink }) => {
  const trackEvents = useTrackEvents();
  useEffect(() => {
    trackEvents({
      objectName: 'Modal CTA',
      actionName: 'Clicked',
      screen: 'home page',
      properties: {
        'Modal Label': 'Are you sure you want to exit?',
      },
    });
  }, []);
  return (
    <Modal
      isOpen={isOpen}
      onClose={() => {
        trackEvents({
          objectName: 'Modal',
          actionName: 'Closed',
          screen: 'home page',
          properties: {
            'CTA Label': 'Continue filling Details',
          },
        });
        onClose();
      }}
      closeable={false}
    >
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
            size="large"
            onClick={() => {
              trackEvents({
                objectName: 'Modal CTA',
                actionName: 'Clicked',
                screen: 'home page',
                properties: {
                  'CTA Label': 'Continue filling Details',
                  'Modal Label': 'Are you sure you want to exit?',
                },
              });
              onClose();
            }}
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
