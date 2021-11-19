import React, { useState, useEffect } from 'react';
import styled from 'styled-components';
import View from '@razorpay/blade-old/src/atoms/View';
import Space from '@razorpay/blade-old/src/atoms/Space';
import { Motion, spring, presets } from 'react-motion';
import { FullPageLoader } from 'common/components/Loader';
import useActivation from '../../hooks/useActivation';
import WhitelistedSteps from './WhitelistedSteps';
import GreylistedSteps from './GreylistedSteps';
import ActivationProgressHeader from './ActivationProgressHeader';
import { checkIfDedupe, isUnregisteredBusiness, isL1Submitted } from '../../services/utils';
import { useApp } from 'common/context/App';
import { ActivationModal, ModalTypeT } from '../../ActivationModals';
import { switchMode } from 'common/services/mode';
import useTrackEvents from 'merchant/hooks/useTrackEvents';
import usePartnerActivation from '../../hooks/usePartnerActivation';
import AccessBlockedSteps from './AccessBlockedSteps';

const Screen = styled(View)`
  background-color: #f9fbfe;
  min-height: 100vh;
`;
const StyledSeparator = styled(View)`
  height: 1px;
  width: 100%;
  background-color: rgba(22, 47, 86, 0.1);
`;

const ScreenContainer = styled.div.attrs((props) => ({
  style: {
    opacity: props.$opacity,
    transform: `translateY(${props.$y}%)`,
  },
}))`
  min-height: 100%;
`;

const ActivationProgress: React.FC = () => {
  const { status, data } = useActivation();
  const [isModalOpen, setIsModalOpen] = useState<boolean>(false);
  const [modalType, setModalType] = useState<ModalTypeT>('');
  const { user, experiments } = useApp();
  const trackEvents = useTrackEvents();

  useEffect(() => {
    if (data) {
      trackEvents({
        objectName: `${isL1Submitted(data.activation_form_milestone) ? 'L2' : 'L1'} Form`,
        actionName: 'Loaded',
        screen: 'onboarding',
      });
    }
  }, [trackEvents]);
  const { shouldBlockMerchantKYC } = usePartnerActivation();

  if (status === 'loading') {
    return <FullPageLoader />;
  }

  if (status === 'error') {
    return <View> Something went wrong </View>;
  }
  const isInstantActivationEnabled = experiments.isInstantActivationEnabled;
  const isDedupe = checkIfDedupe({ ...data, isInstantActivationEnabled }) === 'blocked';

  const openModal = (activationData: any) => {
    if (activationData.activation_form_milestone === 'L1') {
      if (activationData.activated && activationData.activation_status === 'instantly_activated') {
        setModalType('payment_enable');
        switchMode(user.current, 'live');
      } else {
        setModalType('payment_disable');
      }
      setIsModalOpen(true);
    }
  };

  let Steps = WhitelistedSteps;
  if (
    (!isDedupe &&
      (!isUnregisteredBusiness(data.business_type) ||
        data.poi_verification_status !== 'initiated' ||
        experiments.isL2AllowedForPoiInitiated) &&
      data.activation_form_milestone === 'L1') ||
    ((isDedupe || !!data.submitted) && data.activation_form_milestone === 'L2') ||
    !isInstantActivationEnabled
  ) {
    Steps = GreylistedSteps;
  }

  return (
    <>
      <Motion
        defaultStyle={{ y: 100, opacity: 0 }}
        style={{
          y: spring(0, { ...presets.gentle, precision: 0.1 }),
          opacity: spring(1, { ...presets.gentle, precision: 0.1 }),
        }}
      >
        {(styles) => (
          <ScreenContainer $y={styles.y} $opacity={styles.opacity}>
            <ActivationProgressHeader progress={data.activation_progress} />
            <StyledSeparator />
            <Space padding={[2]}>
              <Screen>
                {shouldBlockMerchantKYC() ? (
                  <AccessBlockedSteps />
                ) : (
                  <Steps showL1Modal={(activationData) => openModal(activationData)} />
                )}
              </Screen>
            </Space>
          </ScreenContainer>
        )}
      </Motion>
      <ActivationModal
        isOpen={isModalOpen}
        modalType={modalType}
        closeModal={() => setIsModalOpen(false)}
        activationData={data}
      />
    </>
  );
};

export default ActivationProgress;
