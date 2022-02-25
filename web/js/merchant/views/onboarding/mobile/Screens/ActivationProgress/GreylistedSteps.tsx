import React, { useState } from 'react';
import shallow from 'zustand/shallow';
import { withRouter, RouteComponentProps } from 'react-router-dom';
import View from '@razorpay/blade-old/src/atoms/View';
import OnboardingStepCard from '../../OnboardingStepCard';
import { useActivationFormState } from '../../context/store';
import useActivation from '../../hooks/useActivation';
import useConfigDetails from '../../hooks/useConfigDetails';
import { checkIfDedupe } from '../../services/utils';
import { analyticsTrack } from 'common/services/tracking/segment';
import { ActivationModal, ModalTypeT } from '../../ActivationModals';
import { useApp } from 'common/context/App';
import { getMode, switchMode } from 'common/services/mode';

const GreylistedSteps: React.FC<
  RouteComponentProps & {
    // eslint-disable-next-line react/no-unused-prop-types
    showL1Modal: (data: any) => void;
    getTabError: () => string;
    shouldShowPoiError: boolean;
    isCompanyPanInvalid: boolean;
    isGstinVerificationFailed: boolean;
    isCinVerificationFailed: boolean;
  }
> = ({
  history,
  getTabError,
  shouldShowPoiError,
  isCinVerificationFailed,
  isCompanyPanInvalid,
  isGstinVerificationFailed,
}) => {
  const { data, postData } = useActivation();
  const { data: configData } = useConfigDetails('onboarding');
  const { user, experiments, submerchantId } = useApp();
  const {
    isContactDetailsCompleted,
    isBusinessOverviewCompleted,
    isBusinessDetailsCompleted,
    isBankAndCompanyDetailsCompleted,
    isDocumentsUploadCompleted,
    setActiveTabId,
  } = useActivationFormState(
    (state) => ({
      isContactDetailsCompleted: state.isContactDetailsCompleted,
      isBusinessOverviewCompleted: state.isBusinessOverviewCompleted,
      isBusinessDetailsCompleted: state.isBusinessDetailsCompleted,
      isBankAndCompanyDetailsCompleted: state.isBankAndCompanyDetailsCompleted,
      isDocumentsUploadCompleted: state.isDocumentsUploadCompleted,
      setActiveTabId: state.setActiveTabId,
    }),
    shallow,
  );
  const activationFormUrl = experiments.isActivationFormFullView ? '/kyc' : '/activation';

  const [isModalOpen, setIsModalOpen] = useState<boolean>(false);
  const [modalType, setModalType] = useState<ModalTypeT>('');
  const isInstantActivationEnabled = experiments.isInstantActivationEnabled;
  const isTestMode = getMode(user.current) === 'test';

  const onClick = (step: string) => {
    setActiveTabId(step);
    if (submerchantId) {
      history.push(`/partners/submerchants/onboarding/acc_${submerchantId}/form`);
    } else {
      history.push('/onboarding/form');
    }
  };

  const goToNcFlow = () => {
    if (submerchantId) {
      history.push(`/partners/submerchants/acc_${submerchantId}/activation`);
    } else {
      history.push(activationFormUrl);
    }
  };

  const closeModal = () => {
    setIsModalOpen(false);
  };

  const submitL2 = () => {
    analyticsTrack({
      objectName: 'SignUp',
      actionName: 'submit form',
      screen: 'home page',
      user,
      eventAction: 'initiated',
      properties: {
        clickSource: 'submit-and-verify',
      },
    });
    const payload = isInstantActivationEnabled
      ? { activation_form_milestone: 'L2' }
      : { submit: 1 };
    postData(payload).then((res) => {
      if (res) {
        const dedupeStatus = checkIfDedupe({ ...res, isInstantActivationEnabled });
        if (res.submitted && dedupeStatus === 'blocked') {
          setModalType('dedupe');
        } else if (!res.business_website && experiments.canGenerateTnCPage) {
          setModalType('tnc');
        } else {
          setModalType('under_review');
          if (isTestMode && res.activated) {
            switchMode(user.current, 'live');
          }
        }
        setIsModalOpen(true);
      }
    });
  };

  const isAllTabCompleted =
    (isContactDetailsCompleted ||
      (experiments.isEmailNonMandatoryOnL2Form && !user.user?.signup_via_email)) &&
    isBusinessOverviewCompleted &&
    isBusinessDetailsCompleted &&
    isBankAndCompanyDetailsCompleted &&
    isDocumentsUploadCompleted;

  const dedupeStatus = checkIfDedupe({ ...data, isInstantActivationEnabled });
  const isDedupe = dedupeStatus === 'blocked';

  const canShowCTA =
    [
      'under_review',
      'activated',
      'activated_mcc_pending',
      'needs_clarification',
      'rejected',
    ].includes(data.activation_status) || isDedupe;

  const getMessageInfo = (context) => {
    switch (context.activation_status) {
      case 'under_review':
        return !isDedupe ? 'You have submitted all the details. Our team is reviewing them' : '';
      case 'activated_mcc_pending':
        return 'Payments and settlements have been enabled. We might do some periodic checks for your KYC and ask for clarifications';
      case 'needs_clarification':
        return 'Please provide clarification regarding some issues with your submitted details by on your web dashboard';
      default:
        return '';
    }
  };

  const hasBankVerificationFailed =
    data?.bank_details_verification_status &&
    !['initiated', 'verified'].includes(data?.bank_details_verification_status) &&
    experiments.isSyncBankVerificationEnabled;

  const isBankFieldDisabledField =
    experiments.isSyncBankVerificationEnabled &&
    configData?.bank_account_verification_attempt_count == 10;

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
      isComplete:
        isBusinessDetailsCompleted &&
        !shouldShowPoiError &&
        !isCompanyPanInvalid &&
        !isGstinVerificationFailed &&
        !isCinVerificationFailed,
      hasErrorText: getTabError(),
    },
    {
      name: 'Bank and Business Details',
      id: 'bank_details',
      onClick,
      isComplete: isBankAndCompanyDetailsCompleted && !hasBankVerificationFailed,
      hasErrorText: hasBankVerificationFailed
        ? isBankFieldDisabledField
          ? 'You have reached maximum limit to changed the bank account details'
          : 'Unable to verify your Bank details. Please update'
        : '',
    },
    {
      name: 'Documents Upload',
      id: 'documents',
      onClick,
      isComplete: isDocumentsUploadCompleted,
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
    <View>
      <OnboardingStepCard
        title="Submit KYC details"
        subtitle={
          data.activated
            ? 'Submit all the details and get your KYC approved to complete account activation and enable settlements'
            : 'Submit these details to accept payments and receive settlements in your account'
        }
        info={getMessageInfo(data)}
        errorInfo={
          (isDedupe && !data.activated) || data.activation_status === 'rejected'
            ? 'We can’t support your business because it doesn’t meet our compliance requirements'
            : ''
        }
        sucessInfo={
          data.activation_status === 'activated'
            ? 'KYC details have been reviewed successfully and account has been activated'
            : ''
        }
        steps={steps}
        showSettlement
        showCTA={!canShowCTA}
        canSubmitL2Form={!data.can_submit || !isAllTabCompleted}
        activationStatus={data.activation_status}
        onCTAClick={submitL2}
        milestone={data.activation_form_milestone || !isInstantActivationEnabled}
        goToNcFlow={goToNcFlow}
      />
      <ActivationModal
        isOpen={isModalOpen}
        modalType={modalType}
        closeModal={closeModal}
        dedupeStatus={dedupeStatus}
        activationData={data}
      />
    </View>
  );
};

export default withRouter(GreylistedSteps);
