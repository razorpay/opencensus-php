import React, { useEffect } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import { closeModal } from 'merchant_common/reducers/modals';
import {
  ServiceProvidedHeading,
  ApplicationFormContent,
  HaveAllCapabilitiesImg,
  ApplicationFooter,
  HaveAllCapabilitiesTitle,
} from 'merchant/views/PartnerDashboard/Home/Components/PurePlatformSwitch/Styled';
import { Button, ArrowRightIcon } from '@razorpay/blade/components';
import {
  StepComponentProps,
  STEPS,
} from 'merchant/views/PartnerDashboard/Home/Components/PurePlatformSwitch/Constants';
import { MobileHeader } from 'merchant/views/PartnerDashboard/Home/Components/PurePlatformSwitch/ApplicationFlow/Components/Header';
import { isMobileAndTablet, getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { analyticsTrack } from 'common/utils/analytics';
import { getExperimentsForTracking } from 'merchant/views/PartnerDashboard/Home/Components/utils';

const HaveAllCapabilities = ({
  setIsOpen,
  trackingExperiments,
  user,
  closeModal,
}: StepComponentProps): JSX.Element => {
  useEffect(() => {
    analyticsTrack({
      objectName: 'Migrate To PurePlatform FreelancerOrStartUp Screen',
      actionName: 'Loaded',
      screen: 'Freelancer Or StartUp Screen',
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
      screen: 'Freelancer Or StartUp Screen',
      properties: {
        location: 'partner home',
        formName: 'PartnerTypeSwitch',
        ...trackingExperiments,
        ...getCommonAnalyticsProperties(user),
        ...getExperimentsForTracking(user),
      },
    });
    if (isMobileView) setIsOpen(false);
    else closeModal();
  };

  return (
    <div>
      <ServiceProvidedHeading>
        {isMobileView ? (
          <MobileHeader
            title="You have all the capabilities to manage your clients"
            setIsOpen={setIsOpen}
            step={STEPS.HAVE_ALL_CAPABILITIES}
            trackingExperiments={trackingExperiments}
          />
        ) : (
          'You have all the capabilities to manage your clients'
        )}
      </ServiceProvidedHeading>

      <ApplicationFormContent>
        <HaveAllCapabilitiesTitle>
          If you still feel you need more capabilities to manage your clients better, reach out to
          us at partners@razorpay.com
        </HaveAllCapabilitiesTitle>
        <HaveAllCapabilitiesImg />
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

export default connect(null, (dispatch) => bindActionCreators({ closeModal }, dispatch))(
  HaveAllCapabilities,
);
