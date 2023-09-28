import React from 'react';
import styled from 'styled-components';
import { withRouter } from 'common/deprecated/withRouter';
import View from '@razorpay/blade-old/src/atoms/View';
import Space from '@razorpay/blade-old/src/atoms/Space';
import Text from '@razorpay/blade-old/src/atoms/Text';
import { Modal, ModalBody } from 'common/components/Modal';
import { getModalContent } from 'merchant/views/onboarding/mobile/ActivationModals/ModalContent';

const Container = styled(View)`
  margin: 40px auto 20px;
  text-align: center;
`;

const ActivationModal = ({
  isOpen,
  modalType,
  closeModal,
  history,
  activationData,
  dedupeStatus,
}) => {
  const { title, description, image, button, additionalDesc } = getModalContent(
    modalType,
    closeModal,
    history,
    activationData,
    dedupeStatus,
  );

  if (!title) {
    return null;
  }

  const canShowCloseButton = () => {
    if (
      ['poi_initiated', 'payment_enable', 'payment_disable', 'under_review'].includes(modalType)
    ) {
      return false;
    }
    return true;
  };

  return (
    <Modal
      onClose={() => {
        if (['dedupe', 'tnc'].includes(modalType)) history.push('/');
        closeModal();
      }}
      isOpen={isOpen}
      closeable={canShowCloseButton()}
    >
      <ModalBody>
        <Container>
          {image}
          <Space margin={[1.5, 0, 0]}>
            <View>
              <Space margin={[1, 0]}>
                <View>
                  <Text
                    size="large"
                    weight="bold"
                    _letterSpacing="small"
                    align="center"
                    _lineHeight="large"
                  >
                    {title}
                  </Text>
                </View>
              </Space>

              <Text
                size="medium"
                align={['under_review', 'tnc'].includes(modalType) ? 'justify' : 'center'}
              >
                {description}
                {additionalDesc}
              </Text>
            </View>
          </Space>
          <Space margin={[2, 0, 2.75, 0]}>
            <View>{button}</View>
          </Space>
        </Container>
      </ModalBody>
    </Modal>
  );
};

export default withRouter(ActivationModal);
