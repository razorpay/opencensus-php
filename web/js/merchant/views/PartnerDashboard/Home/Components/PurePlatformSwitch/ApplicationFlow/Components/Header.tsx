import React, { Dispatch, SetStateAction } from 'react';
import {
  HeaderWrapper,
  HeaderBlock,
  HeaderBlockRight,
  MobileHeaderWrapper,
  MobileHeaderContent,
  MobileHeaderCloseIcon,
} from 'merchant/views/PartnerDashboard/Home/Components/PurePlatformSwitch/Styled';
import { ChevronLeftIcon, CloseIcon, ProgressBar } from '@razorpay/blade/components';
import {
  SERVICE_PROVIDED,
  STEP_COMPONENTS,
  STEPS,
  trackingExperimentsProps,
  progressProps,
} from 'merchant/views/PartnerDashboard/Home/Components/PurePlatformSwitch/Constants';
import starIcon from 'assets/partner-dashboard/pp-app-icon.svg';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { analyticsTrack } from 'common/utils/analytics';
import { getExperimentsForTracking } from 'merchant/views/PartnerDashboard/Home/Components/utils';

interface HeaderProps {
  closeModal: () => void;
  step: string;
  setStep: Dispatch<SetStateAction<string>>;
  trackingExperiments: trackingExperimentsProps;
}

interface MobileHeaderProps {
  title: string;
  setIsOpen: (val: boolean) => void;
  step: string;
  trackingExperiments: trackingExperimentsProps;
}

export const MobileHeader = ({
  setIsOpen,
  title,
  step,
  trackingExperiments,
}: MobileHeaderProps): JSX.Element => {
  const onClose = () => {
    analyticsTrack({
      objectName: 'Form',
      actionName: 'Retreat',
      screen: step,
      properties: {
        location: 'partner home',
        formName: 'PartnerTypeSwitch',
        pageTitle: step,
        ...trackingExperiments,
        ...getCommonAnalyticsProperties(window.rzp_user),
        ...getExperimentsForTracking(window.rzp_user),
      },
    });
    setIsOpen(false);
  };

  return (
    <MobileHeaderWrapper>
      <MobileHeaderContent>
        <img src={starIcon} alt="star-icon" />
        <span className="mobile-header-content">{title}</span>
      </MobileHeaderContent>
      <MobileHeaderCloseIcon onClick={onClose}>
        <CloseIcon size="large" color="feedback.icon.neutral.intense" />
      </MobileHeaderCloseIcon>
    </MobileHeaderWrapper>
  );
};

const Header = ({ closeModal, step, setStep, trackingExperiments }: HeaderProps): JSX.Element => {
  const goBack = () => {
    let currentStep = 'default';
    if (step === STEPS.HAVE_ALL_CAPABILITIES) currentStep = STEPS.SERVICE_PROVIDED;
    else {
      const stepList = Object.keys(STEP_COMPONENTS);
      const currentIndex = stepList.indexOf(step);
      if (currentIndex > 0) currentStep = stepList[currentIndex - 1];
    }
    setStep(currentStep);

    analyticsTrack({
      objectName: 'Form',
      actionName: 'Retreat',
      screen: step,
      properties: {
        location: 'partner home',
        formName: 'PartnerTypeSwitch',
        pageTitle: step,
        previousPageTitle: currentStep,
        ...trackingExperiments,
        ...getCommonAnalyticsProperties(window.rzp_user),
        ...getExperimentsForTracking(window.rzp_user),
      },
    });
  };

  const onClose = () => {
    analyticsTrack({
      objectName: 'Form',
      actionName: 'Retreat',
      screen: step,
      properties: {
        location: 'partner home',
        formName: 'PartnerTypeSwitch',
        pageTitle: step,
        ...trackingExperiments,
        ...getCommonAnalyticsProperties(window.rzp_user),
        ...getExperimentsForTracking(window.rzp_user),
      },
    });
    closeModal();
  };

  const getProgressValue = () => {
    const progress: progressProps = {};

    switch (step) {
      case 'SERVICE_PROVIDED':
        progress.firstStep = 50;
        progress.secondStep = 0;
        progress.thirdStep = 0;
        break;

      case 'EVALUATE_USE_CASE':
        progress.firstStep = 100;
        progress.secondStep = 50;
        progress.thirdStep = 0;
        break;

      case 'APPLICATION_FORM':
        progress.firstStep = 100;
        progress.secondStep = 100;
        progress.thirdStep = 50;
        break;

      case 'APPLICATION_RECEIVED':
        progress.firstStep = 100;
        progress.secondStep = 100;
        progress.thirdStep = 100;
        break;

      case 'HAVE_ALL_CAPABILITIES':
        progress.firstStep = 100;
        progress.secondStep = 100;
        progress.thirdStep = 100;
        break;

      default:
        progress.firstStep = 0;
        progress.secondStep = 0;
        progress.thirdStep = 0;
    }

    return progress;
  };
  const progress = getProgressValue();

  return (
    <HeaderWrapper>
      <HeaderBlock>
        <span
          className={`icon-block icon-block-left ${
            step === SERVICE_PROVIDED && 'service-provided'
          }`}
          data-testid="back-id"
          onClick={goBack}
        >
          <ChevronLeftIcon color="feedback.icon.neutral.intense" size="large" />
        </span>
        <span>Want to manage your clients?</span>
      </HeaderBlock>
      <HeaderBlockRight>
        <div className="progress-wrap">
          <ProgressBar value={progress.firstStep} showPercentage={false} size="medium" />
        </div>
        <div className="progress-wrap">
          <ProgressBar value={progress.secondStep} showPercentage={false} size="medium" />
        </div>
        <div className="progress-wrap">
          <ProgressBar value={progress.thirdStep} showPercentage={false} size="medium" />
        </div>
        <div className="icon-block" onClick={onClose}>
          <CloseIcon color="interactive.icon.gray.normal" size="large" />
        </div>
      </HeaderBlockRight>
    </HeaderWrapper>
  );
};

export default Header;
