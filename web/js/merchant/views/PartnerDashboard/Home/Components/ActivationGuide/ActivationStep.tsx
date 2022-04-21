import React, { useEffect } from 'react';
import Button from '@razorpay/blade-old/src/atoms/Button';
import { StepContentT } from 'merchant/views/PartnerDashboard/Home/TypesDeclare/home';
import PopoverComponent, { PopoverBody } from 'common/ui/Popover';
import { classList } from 'common/utils/rzp-utils';
import { showActivationConfetti } from 'merchant/views/PartnerDashboard/Home/Components/utils';

const getStepConnector = (count: number): JSX.Element => {
  return (
    <div className="step-connector">
      {new Array(count).fill(null).map((_, idx) => (
        <span className="connector" key={idx} />
      ))}
    </div>
  );
};
interface ActivationStepT {
  isCurrentStep: boolean;
  isNextStep: boolean;
  isCompletedStep: boolean;
  stepContent: StepContentT;
}

const ActivationStep = ({
  isCurrentStep,
  isNextStep,
  isCompletedStep,
  stepContent,
}: ActivationStepT): JSX.Element => {
  const cdnBase = `${window.cdnBaseUrl}/static/assets/partner-dashboard/fux-cards/activation-guide`;
  const stepIcon = `${cdnBase}/activation-step-current.svg`;
  const completedStepIcon = `${cdnBase}/activation-step-done.svg`;

  const isShowSubText = (isCurrentStep || isNextStep) && !isCompletedStep;
  const isShowCTATooltip = stepContent.ctaText && isCurrentStep;
  const opaqueStepClass = isCurrentStep || isCompletedStep ? '' : 'activation-step--opaque';

  const connectorCount = isCompletedStep ? 3 : 4;

  useEffect(() => {
    setTimeout(() => {
      showActivationConfetti(stepContent.stepName, isCompletedStep);
    }, 1500);
  }, [isCompletedStep, stepContent.stepName]);

  return (
    <div className={classList('activation-step', opaqueStepClass)}>
      <div className="activation-step__icon">
        {isCompletedStep ? (
          <img src={completedStepIcon} alt="completed step icon" />
        ) : (
          <img src={stepIcon} alt="step icon" />
        )}
        {getStepConnector(connectorCount)}
      </div>
      <div className="activation-step__content">
        <div className="activation-step__title">{stepContent.title}</div>
        {isShowSubText ? (
          <div className="activation-step__sub-title">
            {stepContent.subTitle}
            {isShowCTATooltip && <ToolTip content={stepContent.toolTip} />}
          </div>
        ) : null}
      </div>
      {isShowCTATooltip ? (
        <div className="activation-step__cta">
          <Button onClick={stepContent.onClickCTA}>{stepContent.ctaText}</Button>
        </div>
      ) : null}
    </div>
  );
};
export default ActivationStep;

interface ToolTipT {
  content?: string | JSX.Element;
}
const ToolTip = ({ content }: ToolTipT): JSX.Element | null => {
  if (!content) return null;
  return (
    <span>
      <i className="i i-info-circle" />
      <PopoverComponent align="bottom" theme="dark">
        <PopoverBody>{content}</PopoverBody>
      </PopoverComponent>
    </span>
  );
};
