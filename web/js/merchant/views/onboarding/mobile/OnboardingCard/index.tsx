import React, { useState, useEffect } from 'react';
import { getColor } from '@razorpay/blade-old/src/_helpers/theme';
import Button from '@razorpay/blade-old/src/atoms/Button';
import Flex from '@razorpay/blade-old/src/atoms/Flex';
import Space from '@razorpay/blade-old/src/atoms/Space';
import Text from '@razorpay/blade-old/src/atoms/Text';
import View from '@razorpay/blade-old/src/atoms/View';
import { spacings } from '@razorpay/blade-old/src/tokens';
import FormIcon from 'assets/onboarding/form_icon.svg';
import styled from 'styled-components';

import Card from 'common/components/Card';
import { ProgressBar } from 'common/components/ProgressBar';
import { useApp } from 'common/context/App';
import useTrackEvents from 'merchant/hooks/useTrackEvents';
import { ActivationModal, ModalTypeT } from 'merchant/views/onboarding/mobile/ActivationModals';
import { EASY_ONBOARDING } from 'merchant/views/onboarding/mobile/Constants/OnboardingConstants';
import { IReferee } from 'merchant/views/onboarding/mobile/Screens/Home';
import useActivation from 'merchant/views/onboarding/mobile/hooks/useActivation';
import useEligibility from 'merchant/views/onboarding/mobile/hooks/useEligibility';
import useEscalation from 'merchant/views/onboarding/mobile/hooks/useEscalation';
import {
  checkIfDedupe,
  isUnregisteredBusiness,
  setLocalStorage,
} from 'merchant/views/onboarding/mobile/services/utils';

import CurrentActivationProgress from './CurrentActivationProgress';
import OnboardingCardShimmer from './OnboardingCardShimmer';

const Separator = styled(View)`
  height: 1px;
  background-color: ${({ theme }) => getColor(theme, 'cloud.950')};
  margin: ${(props) =>
    props.$onboardingMilestone === null
      ? `${spacings.xlarge} 0 ${spacings.xxlarge}`
      : `${spacings.large} 0`};
`;

const HeadingContainer = styled(View)`
  width: 100%;
`;

const AccountBlock = styled(View)`
  background: #edf0f5;
  border-radius: 4px;
  display: inline-block;
  padding: 4px 8px;
`;

const MccPendingSubDescription = styled(View)`
  font-size: 12px;
  color: #1f890e;
`;

const CloseIcon = styled(View)`
  color: #818fa4;
  font-size: 14px;
  position: absolute;
  right: 25px;
  top: 5px;
`;

const WrapperView = styled(View)`
  position: relative;
`;

interface IOnboardingCardProps {
  referee: IReferee | undefined;
}
const OnboardingCard: React.FC<IOnboardingCardProps> = ({ referee }) => {
  const { user, experiments } = useApp();
  const isSignupWithEasyOnboarding = user?.user?.signup_campaign === EASY_ONBOARDING;
  const { status: activationQueryStatus, data: activationData } = useActivation();
  const { status: escalationsStatus, data: escalationsData } = useEscalation();
  const trackEvents = useTrackEvents();
  const eligibilityData = useEligibility();

  const [isModalOpen, setIsModalOpen] = useState<boolean>(false);
  const [modalType, setModalType] = useState<ModalTypeT>('');
  const isInstantActivationEnabled = experiments.isInstantActivationEnabled;
  const dedupeStatus = checkIfDedupe({ ...activationData, isInstantActivationEnabled });

  const isL2Submitted =
    activationData?.activation_form_milestone === 'L2' || activationData?.submitted;
  const isDedupe =
    dedupeStatus === 'blocked' ||
    (activationData?.activation_flow === 'blacklist' &&
      isSignupWithEasyOnboarding &&
      isL2Submitted);
  const [isModalClosed, setIsModalClosed] = useState(false);

  useEffect(() => {
    const isSessionExpired = window.session_id !== window.sessionStorage.getItem('isNewNc');
    let canShowModals: any = localStorage.getItem(`${user.current}--mweb_modal`);
    canShowModals = JSON.parse(canShowModals);

    if (activationQueryStatus === 'success' && isInstantActivationEnabled) {
      const status = activationData.activation_status;
      const isUnreg = isUnregisteredBusiness(activationData.business_type);
      const isPoifailed = ['failed', 'incorrect_details', 'not_matched'].includes(
        activationData.poi_verification_status,
      );
      if (
        !canShowModals?.dedupe &&
        isDedupe &&
        isL2Submitted &&
        !activationData.activated &&
        (activationData.activation_status !== 'rejected' ||
          activationData.activation_status !== 'activated')
      ) {
        setModalType('dedupe');
        setIsModalOpen(true);
        setLocalStorage(`${user.current}--mweb_modal`, { ...canShowModals, dedupe: true });
      }
      if (activationData.activation_form_milestone === 'L1' && !isDedupe) {
        if (isUnreg && isPoifailed && !canShowModals?.poi_failed) {
          setModalType('payment_disable');
          setIsModalOpen(true);
          setLocalStorage(`${user.current}--mweb_modal`, { ...canShowModals, poi_failed: true });
        } else if (
          isUnreg &&
          activationData.poi_verification_status === 'verified' &&
          !!activationData.activated &&
          !canShowModals?.poi_verified
        ) {
          setModalType('payment_enable');
          setIsModalOpen(true);
          setLocalStorage(`${user.current}--mweb_modal`, { ...canShowModals, poi_verified: true });
        }
      }
      if (activationData.submitted && !isDedupe) {
        if (
          activationData.merchant.hold_funds &&
          activationData.isHardLimitReached &&
          !canShowModals?.settlement_onhold
        ) {
          setModalType('settelment_onhold');
          setIsModalOpen(true);
          setLocalStorage(`${user.current}--mweb_modal`, {
            ...canShowModals,
            settlement_onhold: true,
          });
        } else if (
          activationData.activation_status === 'needs_clarification' &&
          eligibilityData?.nc_revamp_enabled &&
          isSessionExpired
        ) {
          if (activationData.activated && !activationData.merchant.hold_funds) {
            setModalType('needs_clarification_payments_settlement_enabled');
            setIsModalOpen(true);
            setLocalStorage(`${user.current}--mweb_modal`, { ...canShowModals, nc: true });
          } else if (activationData.activated && activationData.merchant.hold_funds) {
            setModalType('needs_clarification_with_payments_enabled');
            setIsModalOpen(true);
            setLocalStorage(`${user.current}--mweb_modal`, { ...canShowModals, nc: true });
          } else if (!activationData.activated) {
            setModalType('needs_clarification_with_payment_disabled');
            setIsModalOpen(true);
            setLocalStorage(`${user.current}--mweb_modal`, { ...canShowModals, nc: true });
          }
        } else if (status === 'needs_clarification' && !canShowModals?.nc) {
          setModalType('needs_clarification');
          setIsModalOpen(true);
          setLocalStorage(`${user.current}--mweb_modal`, { ...canShowModals, nc: true });
        } else if (!canShowModals?.rejected && status === 'rejected') {
          setModalType('rejected');
          setIsModalOpen(true);
          setLocalStorage(`${user.current}--mweb_modal`, { ...canShowModals, rejected: true });
        }
      }
    } else if (activationQueryStatus === 'success') {
      if (
        activationData.activation_status === 'needs_clarification' &&
        eligibilityData?.nc_revamp_enabled &&
        isSessionExpired
      ) {
        if (activationData.activated && !activationData.merchant.hold_funds) {
          setModalType('needs_clarification_payments_settlement_enabled');
          setIsModalOpen(true);
          setLocalStorage(`${user.current}--mweb_modal`, { ...canShowModals, nc: true });
        } else if (activationData.activated && activationData.merchant.hold_funds) {
          setModalType('needs_clarification_with_payments_enabled');
          setIsModalOpen(true);
          setLocalStorage(`${user.current}--mweb_modal`, { ...canShowModals, nc: true });
        } else if (!activationData.activated) {
          setModalType('needs_clarification_with_payment_disabled');
          setIsModalOpen(true);
          setLocalStorage(`${user.current}--mweb_modal`, { ...canShowModals, nc: true });
        }
      }
    }
  }, [activationQueryStatus, eligibilityData]);

  const isActivationmccPending =
    experiments.isActivationMccPendingProgressbarDisabled &&
    user.activation_progress === 90 &&
    user.activation_status === 'activated_mcc_pending';

  useEffect(() => {
    if (isActivationmccPending) {
      trackEvents({
        objectName: 'Onboarding progress bar',
        actionName: 'hidden',
        screen: 'home page',
        properties: {
          merchantStatus: user.activation_status,
        },
      });
    }
  }, [isActivationmccPending]);

  if (activationQueryStatus === 'loading' || escalationsStatus === 'loading') {
    return <OnboardingCardShimmer />;
  }

  if (
    activationQueryStatus === 'error' ||
    (escalationsStatus === 'error' && isInstantActivationEnabled)
  ) {
    return <div>Something went wrong</div>;
  }

  if (isModalClosed) {
    return null;
  }

  return (
    <WrapperView>
      <Card padding={isActivationmccPending ? [2, 4, 2, 2] : [2]} margin={[2]}>
        {!eligibilityData?.nc_revamp_enabled &&
          activationData.activation_status !== 'needs_clarification' && (
            <>
              <Flex
                flexDirection="row"
                justifyContent="space-between"
                alignItems={isActivationmccPending ? 'center' : 'flex-start'}
              >
                <View>
                  <HeadingContainer>
                    <Space margin={[0, 0, 0.5, 0]}>
                      <HeadingContainer>
                        {isActivationmccPending ? (
                          <Text size="large" weight="bold">
                            Congratulations !
                          </Text>
                        ) : (
                          <Text size="large" weight="bold">
                            Account Activation
                          </Text>
                        )}
                      </HeadingContainer>
                    </Space>
                    {((isDedupe && !activationData.activated) ||
                      activationData.activation_status === 'rejected') &&
                    activationData.submitted &&
                    !isActivationmccPending &&
                    isInstantActivationEnabled ? (
                      <AccountBlock>Paused</AccountBlock>
                    ) : isActivationmccPending ? (
                      <MccPendingSubDescription>
                        Now you can accept unlimited payments. Settlements to your bank account have
                        been enabled.
                      </MccPendingSubDescription>
                    ) : (
                      <>
                        <Space margin={[1, 2, 0, 0]}>
                          <Text size="small" color="positive.960" weight="bold">
                            {activationData.activation_progress}% complete
                          </Text>
                        </Space>
                        <Space margin={[0, 2, 0, 0]}>
                          <View>
                            <ProgressBar
                              progressBarCompletedColor="primary.700"
                              progressBarBackgroundColor="primary.200"
                              percentDone={activationData.activation_progress}
                              height="6px"
                            />
                          </View>
                        </Space>
                      </>
                    )}
                  </HeadingContainer>
                  <img src={FormIcon} alt="fill_activation_form_icon" />
                  {isActivationmccPending ? (
                    <CloseIcon>
                      <Button
                        variant="tertiary"
                        size="small"
                        variantColor="shade"
                        icon="close"
                        onClick={() => setIsModalClosed(true)}
                      />
                    </CloseIcon>
                  ) : null}
                </View>
              </Flex>

              <Separator $onboardingMilestone={activationData.activation_form_milestone} />
            </>
          )}
        <CurrentActivationProgress
          data={activationData}
          escalation={escalationsData}
          referee={referee}
        />

        <ActivationModal
          isOpen={isModalOpen}
          modalType={modalType}
          closeModal={() => setIsModalOpen(false)}
          dedupeStatus={dedupeStatus}
          activationData={activationData}
        />
      </Card>
    </WrapperView>
  );
};

export default OnboardingCard;
