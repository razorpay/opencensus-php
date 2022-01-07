import React, { useState } from 'react';
import styled from 'styled-components';
import View from '@razorpay/blade-old/src/atoms/View';
import shallow from 'zustand/shallow';
import { withRouter, RouteComponentProps } from 'react-router-dom';
import useActivation from '../../hooks/useActivation';
import { isVisible, useActivationFormState } from '../../context/store';
import OnboardingStepCard from '../../OnboardingStepCard';
import {
  isL1Submitted,
  getPoiVerificationStatus,
  checkIfDedupe,
  hasSelectedBlacklistCategory,
  isUnregisteredBusiness,
  getCompanyPanVerificationStatus,
} from '../../services/utils';
import { ActivationModal, ModalTypeT } from '../../ActivationModals';
import useBusinessCategory from '../../hooks/useBusinessCategory';
import { useApp } from 'common/context/App';
import { analyticsTrack } from 'common/services/tracking/segment';

const Screen = styled(View)`
  background-color: #f9fbfe;
`;

const WhitelistedSteps: React.FC<RouteComponentProps & { showL1Modal: (data: any) => void }> = ({
  history,
  showL1Modal,
}) => {
  const { data, postData } = useActivation();
  const { user, experiments } = useApp();
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
  const isL1Acknowledge = useActivationFormState((state) => state.is_l1_acknowledge);
  const [isModalOpen, setIsModalOpen] = useState<boolean>(false);
  const [modalType, setModalType] = useState<ModalTypeT>('');

  const isL1AllTabComplete =
    (isContactDetailsCompleted ||
      (experiments.isEmailNonMandatoryOnL2Form && !user.user?.signup_via_email)) &&
    isBusinessOverviewCompleted &&
    isBusinessDetailsCompleted &&
    (isL1Acknowledge || !experiments.isSyncExperimentEnabled);

  const isBlackListCategory =
    status === 'success' && hasSelectedBlacklistCategory(data, businessCategoriesData);

  const isInstantActivationEnabled = experiments.isInstantActivationEnabled;

  const onClick = (step) => {
    setActiveTabId(step);
    history.push('/onboarding/form');
  };

  const submitL1 = () => {
    analyticsTrack({
      objectName: 'SignUp L1',
      actionName: 'submit form',
      screen: 'home page',
      user,
      eventAction: 'initiated',
      properties: {
        clickSource: 'submit-and-verify',
      },
      activationType: 'act',
    });
    postData({ activation_form_milestone: 'L1' }).then((res) => {
      if (res && res.activation_form_milestone === 'L1') {
        const dedupeStatus = checkIfDedupe({ ...res, isInstantActivationEnabled });
        if (dedupeStatus === 'blocked') {
          setModalType('dedupe');
        } else if (
          isUnregisteredBusiness(res.business_type) &&
          res.poi_verification_status === 'initiated' &&
          experiments.canSkipPoiValidation &&
          !experiments.isL2AllowedForPoiInitiated
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
    !data.submitted &&
    getPoiVerificationStatus(data?.poi_verification_status) &&
    (!experiments.canSkipPoiValidation || experiments.isSyncExperimentEnabled);

  const isCompanyPanInvalid =
    isVisible('company_pan', data) &&
    !data.submitted &&
    experiments.isSyncExperimentEnabled &&
    getCompanyPanVerificationStatus(data?.company_pan_verification_status);

  const canL1Submit =
    !isL1Submitted(data.activation_form_milestone) && (!isL1AllTabComplete || isBlackListCategory);

  const canShowCTA =
    data.poi_verification_status !== 'initiated' ||
    (experiments.isSyncExperimentEnabled && !isL1Submitted(data.activation_form_milestone));

  const steps = [
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
      isComplete: isBusinessDetailsCompleted && !shouldShowPoiError && !isCompanyPanInvalid,
      hasErrorText:
        shouldShowPoiError || isCompanyPanInvalid ? 'Unable to verify your PAN. Please update' : '',
    },
  ];
  if (
    (experiments.isEmailMandatoryOnL1 || experiments.isEmailNonMandatoryOnL1) &&
    !user.user?.signup_via_email
  )
    [steps[0], steps[1], steps[2]] = [steps[1], steps[2], steps[0]];

  //remove contact details from step
  if (experiments.isEmailNonMandatoryOnL2Form && !user.user?.signup_via_email) steps.shift();

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
          data.poi_verification_status === 'initiated' && data.activation_form_milestone === 'L1'
            ? 'You have submitted all the details. Our team is reviewing them'
            : ''
        }
        steps={steps}
        showSettlement={true}
        onCTAClick={onCTAClick}
        showCTA={canShowCTA}
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
