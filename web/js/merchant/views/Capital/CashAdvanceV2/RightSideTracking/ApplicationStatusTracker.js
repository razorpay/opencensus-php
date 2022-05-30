import React, { useState } from 'react';
import { CSSTransition } from 'react-transition-group';
import {
  APPLICATION_NAVIGATION_CONFIG,
  APPLICATION_STATES,
  APPLICATION_STATE_SEQUENCE_STAGES,
  STEP_NAMES,
  FINAL_STEP_CONFIG,
} from '../constants';
import { trackApplicationStatus } from '../TrackEvents';
import './ApplicationStatus.styl';

const ApplicationStatusOverview = (props) => {
  const { stepData, particularStep, getSubStepStatus, parentClass, handleCtaClick } = props;

  const {
    isCurrentStep,
    isApplicationUnderReview,
    isWithdrawalStep,
    isLastStep,
    isViewAllEnabled,
  } = stepData;
  const { parentName, subText, actionButtonText, subSteps = [] } = particularStep;

  const handleOnCtaClick = () => {
    handleCtaClick(actionButtonText);
  };

  const showSubTracker = isViewAllEnabled && isCurrentStep && !isApplicationUnderReview;
  const reviewStep = isApplicationUnderReview && isCurrentStep && !isViewAllEnabled;
  const parentLineClass = `${reviewStep && 'review'} ${isLastStep && 'last-parent'}`;
  if (!isViewAllEnabled && !isCurrentStep) {
    return null;
  } else {
    return (
      <CSSTransition in={true} appear={true} timeout={800} classNames="slide-up">
        <div className={`multi-wrapper ${STEP_NAMES[parentClass]}`}>
          <div className="title">
            <span className={STEP_NAMES[parentClass]}>
              <img
                src={`${window.cdnBaseUrl}/static/assets/cash-advance/${STEP_NAMES[parentClass]}.svg`}
                alt="step-image"
              />
            </span>
            {parentName}
          </div>
          <div className="subtext">{subText}</div>
          {!isViewAllEnabled && !isApplicationUnderReview && (
            <button onClick={handleOnCtaClick}>{actionButtonText}</button>
          )}
          {showSubTracker && (
            <SubStepTracker
              subSteps={subSteps}
              getSubStepStatus={getSubStepStatus}
              actionButtonText={actionButtonText}
              handleCtaClick={handleOnCtaClick}
            />
          )}
          <ApplicationUnderReview
            isCurrentStep={isCurrentStep}
            isApplicationUnderReview={isApplicationUnderReview}
          />
          {!isWithdrawalStep && <span className={`parent-line ${parentLineClass}`} />}
        </div>
      </CSSTransition>
    );
  }
};

const SubStepTracker = (props) => {
  const { subSteps, getSubStepStatus, handleCtaClick, actionButtonText } = props;
  const subStepLength = subSteps?.length || 0;

  return (
    <>
      <div className="sub-step-wrapper">
        {subSteps?.map((particularSubStep, index) => {
          const statusKey = getSubStepStatus(particularSubStep.step);
          const isLastStep = subStepLength === index + 1;
          return (
            <div
              className={`sub-step-key ${STEP_NAMES[statusKey]}`}
              key={particularSubStep.stepName}
            >
              <span>
                <img
                  src={`${window.cdnBaseUrl}/static/assets/cash-advance/${STEP_NAMES[statusKey]}.svg`}
                  alt="step-image"
                />
              </span>
              {particularSubStep.stepName}
              <span className={`sub-parent-line ${isLastStep ? 'last-step' : ''}`} />
            </div>
          );
        })}
      </div>
      <button onClick={handleCtaClick}>{actionButtonText}</button>
    </>
  );
};

const ApplicationUnderReview = (props) => {
  const { isCurrentStep, isApplicationUnderReview } = props;
  if (isCurrentStep && isApplicationUnderReview) {
    return (
      <div className="sub-step-wrapper review">
        <div className="sub-step-key">
          <div className="title">
            <span>
              <img
                src={`${window.cdnBaseUrl}/static/assets/cash-advance/pendingStep.svg`}
                alt="pending-step"
              />
            </span>
            Application Review
          </div>
          <div className="subtext">
            Kindly wait while we review your application. The process should take between 4 to 6
            hours.
          </div>
          <span className="sub-parent-line review" />
        </div>
      </div>
    );
  }
  return null;
};

const ApplicationStatusTracker = (props) => {
  const {
    currentNavigationStatus,
    getParentStepStatus,
    getSubStepStatus,
    handleCtaClick,
    isApplicationUnderReview,
  } = props;
  const [isViewAllEnabled, setViewAllEnabled] = useState(false);

  const handleViewClick = () => {
    trackApplicationStatus(isViewAllEnabled ? 'View Less' : 'View More');
    setViewAllEnabled(!isViewAllEnabled);
  };

  const isWithdrawalStep = currentNavigationStatus === APPLICATION_STATES.STATE_COMPLETED;
  const navigationObject = isWithdrawalStep ? FINAL_STEP_CONFIG : APPLICATION_NAVIGATION_CONFIG;
  const parentClassObj = getParentStepStatus(currentNavigationStatus);

  return (
    <>
      {navigationObject.map((particularStep, index) => {
        const { stageName } = particularStep;
        const isLastStep = index + 1 === Object.keys(navigationObject).length;
        const isCurrentStep =
          APPLICATION_STATE_SEQUENCE_STAGES[stageName]?.includes(currentNavigationStatus) ||
          isWithdrawalStep;
        const stepData = {
          isCurrentStep,
          isApplicationUnderReview,
          isWithdrawalStep,
          isLastStep,
          isViewAllEnabled,
        };
        return (
          <ApplicationStatusOverview
            stepData={stepData}
            particularStep={particularStep}
            getSubStepStatus={getSubStepStatus}
            parentClass={parentClassObj[stageName]}
            currentNavigationStatus={currentNavigationStatus}
            key={index}
            handleCtaClick={handleCtaClick}
          />
        );
      })}
      {!isWithdrawalStep && (
        <div onClick={handleViewClick} className="view-content">
          {isViewAllEnabled ? 'View Less' : 'View All'}
          <i className={`i i-chevron-up ${isViewAllEnabled ? '' : 'rotate'}`} />
        </div>
      )}
    </>
  );
};
export default ApplicationStatusTracker;
