import React from 'react';
import styled from 'styled-components';
import View from '@razorpay/blade/src/atoms/View';
import Space from '@razorpay/blade/src/atoms/Space';
import Button from '@razorpay/blade/src/atoms/Button';
import Text from '@razorpay/blade/src/atoms/Text';
import { Modal, ModalBody } from '../../../../components/Modal';
import BannerModal from './Frame.svg';

export interface SubmitFromPropsT {
  isOpen: boolean;
  hasClarificationReasons: boolean;
}

const Container = styled(View)`
  margin: auto;
  text-align: center;
`;

const SubmitFrom: React.FC<SubmitFromPropsT> = ({ isOpen, hasClarificationReasons }) => {
  const backToDashboard = () => {
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
                  You have submitted all the details.
                </Text>
              </View>
            </Space>

            <Text size="medium" align="justify">
              Your documents and KYC detail are under review. It's now our responsibility to make
              sure your documents are processed. It usually takes{' '}
              {hasClarificationReasons ? ' 3 ' : ' 3 - 4 '} working days for our team to review your
              documents. We will reach out to you if we need any clarification.
            </Text>
          </View>
          <Space margin={[2, 0, 2, 0]}>
            <View>
              <Button onClick={() => backToDashboard()} block>
                Back To Dashboard
              </Button>
            </View>
          </Space>
        </Container>
      </ModalBody>
    </Modal>
  );
};

export default SubmitFrom;
