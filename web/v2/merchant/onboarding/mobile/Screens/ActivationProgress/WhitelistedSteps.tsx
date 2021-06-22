import React, { useState } from 'react';
import styled from 'styled-components';
import View from '@razorpay/blade-old/src/atoms/View';
import shallow from 'zustand/shallow';
import { withRouter, RouteComponentProps } from 'react-router-dom';
import useActivation from '../../hooks/useActivation';
import { useActivationFormState } from '../../context/store';
import OnboardingStepCard from '../../OnboardingStepCard';
import {
  isL1Submitted,
  getPoiVerificationStatus,
  checkIfDedupe,
  hasSelectedBlacklistCategory,
  isUnregisteredBusiness,
} from 'v2/merchant/onboarding/mobile/services/utils';
import { ActivationModal, ModalTypeT } from 'v2/merchant/onboarding/mobile/ActivationModals';
import useBusinessCategory from '../../hooks/useBusinessCategory';
import { useApp } from 'v2/context/App';

const Screen = styled(View)`
  background-color: #f9fbfe;
`;

const WhitelistedSteps: React.FC<RouteComponentProps & { showL1Modal: (data: any) => void }> = ({
  history,
  showL1Modal,
}) => {
  const { data, postData } = useActivation();
  const { experiments } = useApp();
  const [status, businessCategoriesData] = useBusinessCategory('');
  const {
    isContactDetailsCompleted,
    isBusinessOverviewCompleted,
    isBusinessDetailsCompleted,
    setActiveTabId,
  } = useActivationFormState(
    (state) => ({
      isContactDetailsCompleted: state.isContactDetailsCompleted,
      isBusinessOverviewCompleted: state.isBusinessOverviewCompleted,
      isBusinessDetailsCompleted: state.isBusinessDetailsCompleted,
      setActiveTabId: state.setActiveTabId,
    }),
    shallow,
  );
  const [isModalOpen, setIsModalOpen] = useState<boolean>(false);
  const [modalType, setModalType] = useState<ModalTypeT>('');

  const isL1AllTabComplete =
    isContactDetailsCompleted && isBusinessOverviewCompleted && isBusinessDetailsCompleted;

  const isBlackListCategory =
    status === 'success' && hasSelectedBlacklistCategory(data, businessCategoriesData);

  const isInstantActivationEnabled = experiments.isInstantActivationEnabled;
  const isDedupe = checkIfDedupe({ ...data, isInstantActivationEnabled }) === 'blocked';

  const onClick = (step) => {
    setActiveTabId(step);
    history.push('/onboarding/form');
  };

  const submitL1 = () => {
    postData({ activation_form_milestone: 'L1' }).then((res) => {
      if (res && res.activation_form_milestone === 'L1') {
        const dedupeStatus = checkIfDedupe({ ...res, isInstantActivationEnabled });
        if (dedupeStatus === 'blocked') {
          setModalType('dedupe');
        } else if (
          isUnregisteredBusiness(res.business_type) &&
          res.poi_verification_status === 'initiated' &&
          experiments.canSkipPoiValidation
        ) {
          setModalType('poi_initiated');
        } else {
          showL1Modal(res);
        }
        setIsModalOpen(true);
      }
    });
  };

  const onCTAClick = () => {
    if (!isL1Submitted(data.activation_form_milestone)) {
      submitL1();
    }
  };

  const shouldShowPoiError =
    !isL1Submitted(data.activation_form_milestone) &&
    getPoiVerificationStatus(data) &&
    !experiments.canSkipPoiValidation;

  const canL1Submit =
    !isL1Submitted(data.activation_form_milestone) &&
    (!isL1AllTabComplete || isBlackListCategory || shouldShowPoiError);

  return (
    <Screen>
      <OnboardingStepCard
        title="Submit KYC details"
        subtitle={
          !isL1Submitted(data.activation_form_milestone)
            ? 'Submit these details to accept payments and receive settlements in your account'
            : 'Submit all the details and get your KYC approved to complete account activation and enable settlements'
        }
        info={
          data.poi_verification_status === 'initiated'
            ? 'You have submitted all the details. Our team is reviewing them'
            : ''
        }
        errorInfo={
          isDedupe
            ? 'We can’t support your business because it doesn’t meet our compliance requirements'
            : ''
        }
        steps={[
          {
            name: 'Contact Details',
            id: 'contact_details',
            onClick,
            isComplete: isContactDetailsCompleted,
          },
          {
            name: 'Business Overview',
            id: 'business_overview',
            onClick,
            isComplete: isBusinessOverviewCompleted,
          },
          {
            name: 'Business Details',
            id: 'business_details',
            onClick,
            isComplete: isBusinessDetailsCompleted && !shouldShowPoiError,
            hasErrorText: shouldShowPoiError ? 'Unable to verify your PAN. Please update' : '',
          },
        ]}
        showSettlement={!isDedupe}
        onCTAClick={onCTAClick}
        showCTA={!isDedupe && data.poi_verification_status !== 'initiated'}
        canSubmitL1Form={canL1Submit}
        milestone={data.activation_form_milestone}
      />

      <ActivationModal
        isOpen={isModalOpen}
        modalType={modalType}
        closeModal={() => setIsModalOpen(false)}
        activationData={data}
      />
    </Screen>
  );
};

export default withRouter(WhitelistedSteps);
