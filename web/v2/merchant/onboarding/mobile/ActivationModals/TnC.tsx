import React from 'react';
import styled from 'styled-components';
import { withRouter, RouteComponentProps } from 'react-router-dom';
import View from '@razorpay/blade-old/src/atoms/View';
import Space from '@razorpay/blade-old/src/atoms/Space';
import Button from '@razorpay/blade-old/src/atoms/Button';
import Text from '@razorpay/blade-old/src/atoms/Text';
import { Modal, ModalBody } from 'v2/components/Modal';
import BannerModal from './Frame.svg';
import { analyticsTrack } from '../../../../services/tracking/segment';
import { useApp } from 'v2/context/App';

interface TnCPropsT extends RouteComponentProps {
  isOpen: boolean;
  onClose: () => void;
}

const Container = styled(View)`
  margin: auto;
  text-align: center;
`;

const TnCModal: React.FC<TnCPropsT> = ({ isOpen, onClose, history }) => {
  const { user } = useApp();

  return (
    <Modal onClose={onClose} isOpen={isOpen} closeable={true}>
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
                  Details have been submitted Generate TnC now
                </Text>
              </View>
            </Space>

            <Text size="medium" align="justify">
              We are reviewing your details. It usually takes 3-4 days. Please generate your terms
              and conditions page at the earliest. Your details review might get delayed if you
              don’t generate the TnC.
            </Text>
          </View>
          <Space margin={[2, 0, 2, 0]}>
            <View>
              <Button
                onClick={() => {
                  history.push('/tncform');
                  analyticsTrack({
                    objectName: 'Act',
                    actionName: 'generate page now',
                    screen: 'home page',
                    properties: { clickSource: 'post activation tnc popup' },
                    eventAction: 'initiated',
                    user,
                  });
                }}
                block
              >
                Generate Terms and conditions
              </Button>
            </View>
          </Space>
        </Container>
      </ModalBody>
    </Modal>
  );
};

export default withRouter(TnCModal);
