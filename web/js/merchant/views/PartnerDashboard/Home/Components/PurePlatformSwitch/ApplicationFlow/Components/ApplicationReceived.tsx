import React, { useEffect } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import { closeModal } from 'merchant_common/reducers/modals';
import {
  ServiceProvidedHeading,
  ServiceProvidedDescription,
  ApplicationFormContent,
  ApplicationFormWrapper,
  ApplicationReceivedImg,
  ApplicationFooter,
  ApplicationStepWrapper,
  ApplicationDotContent,
} from 'merchant/views/PartnerDashboard/Home/Components/PurePlatformSwitch/Styled';
import { Button, ArrowRightIcon } from '@razorpay/blade/components';
import activatedDot from 'assets/partner-dashboard/activated-step-dot.svg';
import stepDot from 'assets/partner-dashboard/step-dot.svg';
import { MobileHeader } from 'merchant/views/PartnerDashboard/Home/Components/PurePlatformSwitch/ApplicationFlow/Components/Header';
import {
  ApplicationReceivedProps,
  STEPS,
} from 'merchant/views/PartnerDashboard/Home/Components/PurePlatformSwitch/Constants';
import { isMobileAndTablet, getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { analyticsTrack } from 'common/utils/analytics';
import { getExperimentsForTracking } from 'merchant/views/PartnerDashboard/Home/Components/utils';
import { setPartnerSwitchFlag } from 'merchant/reducers/partner';

const ApplicationReceived = ({
  setStep,
  setIsOpen,
  trackingExperiments,
  closeModal,
  user,
  setPartnerSwitchFlag,
}: ApplicationReceivedProps): JSX.Element => {
  useEffect(() => {
    analyticsTrack({
      objectName: 'Migrate To PurePlatform Success Screen',
      actionName: 'Loaded',
      screen: 'Success Screen',
      properties: {
        location: 'partner home',
        ...trackingExperiments,
        ...getCommonAnalyticsProperties(user),
        ...getExperimentsForTracking(user),
      },
    });
  }, []);

  const isMobileView = isMobileAndTablet();

  const goToDashboard = () => {
    analyticsTrack({
      objectName: 'Go to dashboard',
      actionName: 'Clicked',
      screen: 'Success Screen',
      properties: {
        location: 'partner home',
        formName: 'PartnerTypeSwitch',
        ...trackingExperiments,
        ...getCommonAnalyticsProperties(user),
        ...getExperimentsForTracking(user),
      },
    });
    setStep('');
    setPartnerSwitchFlag();
    if (isMobileView) setIsOpen(false);
    else closeModal();
  };

  return (
    <div>
      <ServiceProvidedHeading>
        {isMobileView ? (
          <MobileHeader
            title="Application Received"
            setIsOpen={setIsOpen}
            step={STEPS.APPLICATION_RECEIVED}
            trackingExperiments={trackingExperiments}
          />
        ) : (
          'Application Received'
        )}
      </ServiceProvidedHeading>
      <ServiceProvidedDescription>
        Our sales team will reach out to you for the next steps
      </ServiceProvidedDescription>

      <ApplicationFormContent>
        <ApplicationFormWrapper>
          <ApplicationStepWrapper>
            <div className="pp-dot-wrap">
              <img src={activatedDot} alt="completed step icon" />
            </div>
            <ApplicationDotContent>
              <div>We have received your request</div>
              <div className="pp-dot-subtitle">&nbsp;</div>
              <ul>
                <li />
                <li />
              </ul>
            </ApplicationDotContent>
          </ApplicationStepWrapper>

          <ApplicationStepWrapper>
            <div className="pp-dot-wrap">
              <img src={stepDot} alt="completed step icon" />
            </div>
            <ApplicationDotContent>
              <div>
                Our team will evaluate your response and contact you for more details if needed.
              </div>
              <div className="pp-dot-subtitle">(Can take around 2-3 weeks)</div>
              <ul>
                <li />
                <li />
                <li />
              </ul>
            </ApplicationDotContent>
          </ApplicationStepWrapper>

          <ApplicationStepWrapper>
            <div className="pp-dot-wrap">
              <img src={stepDot} alt="completed step icon" />
            </div>
            <ApplicationDotContent>
              <div>You can now manage your sub-merchants' transactions!</div>
              <div className="pp-dot-subtitle">&nbsp;</div>
            </ApplicationDotContent>
          </ApplicationStepWrapper>
        </ApplicationFormWrapper>
        <ApplicationReceivedImg />
      </ApplicationFormContent>

      <ApplicationFooter>
        <div className="btn-wrap btn-wrap-big">
          {isMobileView ? (
            <Button isFullWidth onClick={goToDashboard} icon={ArrowRightIcon} iconPosition="right">
              Go to dashboard
            </Button>
          ) : (
            <Button isFullWidth onClick={goToDashboard}>
              Go to dashboard
            </Button>
          )}
        </div>
      </ApplicationFooter>
    </div>
  );
};

export default connect(null, (dispatch) =>
  bindActionCreators({ closeModal, setPartnerSwitchFlag }, dispatch),
)(ApplicationReceived);
