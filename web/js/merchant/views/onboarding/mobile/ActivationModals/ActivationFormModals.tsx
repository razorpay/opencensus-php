import React, { useEffect } from 'react';
import styled from 'styled-components';
import { withRouter } from 'common/deprecated/withRouter';
import type { RouteComponentProps } from 'common/deprecated/RouteComponentProps';
import View from '@razorpay/blade-old/src/atoms/View';
import Space from '@razorpay/blade-old/src/atoms/Space';
import Text from '@razorpay/blade-old/src/atoms/Text';
import { Modal } from 'common/components/Modal';
import { getModalContent, ModalTypeT } from './ModalContent';
import useTrackEvents from 'merchant/hooks/useTrackEvents';
import { isMobileDevice } from 'merchant/components/Home/data';
import { isNewNcActivationStatus } from 'merchant/views/onboarding/mobile/services/utils';

interface ActivationModalPropsT extends RouteComponentProps {
  isOpen: boolean;
  modalType: ModalTypeT;
  closeModal: () => void;
  dedupeStatus?: string;
  activationData?: any;
}

const Container = styled(View)`
  margin: ${(props) => (props.isNewNC ? '16px auto' : '40px auto 20px')};
  text-align: center;
`;

const ModalBody = styled(Text)`
  margin-top: ${({ theme }) => theme.bladeOld.spacings.large};
  margin-bottom: ${({ theme }) => theme.bladeOld.spacings.large};
  margin-left: ${({ theme }) => theme.bladeOld.spacings.large};
  margin-right: ${({ theme }) => theme.bladeOld.spacings.large};
`;

const ActivationModal: React.FC<ActivationModalPropsT> = ({
  isOpen,
  modalType,
  closeModal,
  history,
  activationData,
  dedupeStatus,
}) => {
  const { title, description, image, button, additionalDesc, pill } = getModalContent(
    modalType,
    closeModal,
    history,
    activationData,
    dedupeStatus,
  );
  const trackEvents = useTrackEvents();
  const newNC = [
    'needs_clarification_payments_settlement_enabled',
    'needs_clarification_with_payments_enabled',
    'needs_clarification_with_payment_disabled',
  ];
  const isNewNC = isNewNcActivationStatus(modalType);

  useEffect(() => {
    trackEvents({
      objectName: 'Modal CTA',
      actionName: 'Displayed',
      screen: 'home page',
      properties: {
        'Modal Label': title,
      },
    });
    if (isNewNC) {
      trackEvents({
        objectName: 'NC Entry Modal',
        actionName: 'Loaded',
        screen: 'home page',
        properties: {
          formName: title,
          activationState: modalType,
          funnelStage: 'NC',
          ncCount: `${activationData?.kyc_clarification_reasons?.nc_count}`,
          deviceType: isMobileDevice(768) ? 'mweb' : 'dweb',
        },
        toCleverTap: true,
      });
    }
  }, []);

  if (!title) {
    return null;
  }

  const canShowCloseButton = () => {
    if (
      ['poi_initiated', 'payment_enable', 'payment_disable', 'under_review', ...newNC].includes(
        modalType,
      )
    ) {
      return false;
    }
    return true;
  };

  const Pill = styled.span`
    background: #d12d2d;
    border-radius: 12px;
    padding: 4px 12px;
    color: white;
    margin-bottom: 16px;
    display: block;
    width: fit-content;
    font-size: 10px;
  `;

  return (
    <Modal
      onClose={() => {
        if (['dedupe', 'tnc'].includes(modalType)) history.push('/');
        trackEvents({
          objectName: 'Modal CTA',
          actionName: 'Closed',
          screen: 'home page',
          properties: {
            'CTA Label': title,
          },
        });

        const isSessionExpired = window.session_id !== window.sessionStorage.getItem('isNewNc');

        if (isNewNC) {
          if (isSessionExpired && !!window.session_id) {
            window.sessionStorage.setItem('isNewNc', window.session_id || '');
          }
        }

        closeModal();
      }}
      isOpen={isOpen}
      closeable={canShowCloseButton()}
    >
      <ModalBody>
        <Container isNewNC={isNewNC}>
          {image}
          <Space margin={[1.5, 0, 0]}>
            <View>
              {pill && <Pill>{pill}</Pill>}
              <Space margin={[1, 0]}>
                <View>
                  <Text
                    size="large"
                    weight="bold"
                    _letterSpacing="small"
                    align={pill ? 'start' : 'center'}
                    _lineHeight="large"
                  >
                    {title}
                  </Text>
                </View>
              </Space>
              <Text
                size="medium"
                align={['under_review', 'tnc', ...newNC].includes(modalType) ? 'justify' : 'center'}
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
