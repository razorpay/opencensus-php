import React, { useEffect, useState } from 'react';
import { Button, InfoIcon, ArrowRightIcon, Link } from '@razorpay/blade/components';

import { analyticsTrack } from 'common/utils/analytics';
import { isMobileAndTablet, getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { MobileHeader } from 'merchant/views/PartnerDashboard/Home/Components/PurePlatformSwitch/ApplicationFlow/Components/Header';
import {
  STEPS,
  StepComponentProps,
} from 'merchant/views/PartnerDashboard/Home/Components/PurePlatformSwitch/Constants';
import {
  ServiceProvidedHeading,
  ServiceProvidedDescription,
  ManagePaymentsBanner,
  ManagePaymentHeading,
  ManagePaymentButtonWrap,
  IntegrateAPIBanner,
  IntegrateAPIHeading,
  IntegrateAPIDesc,
  IntegrateAPICTA,
  ServiceProviderFooter,
} from 'merchant/views/PartnerDashboard/Home/Components/PurePlatformSwitch/Styled';
import { getExperimentsForTracking } from 'merchant/views/PartnerDashboard/Home/Components/utils';
import { PARTNERSHIPS_WEBSITE_LINKS } from 'merchant/views/PartnerDashboard/constants';

const EvaluateUseCase = ({
  setStep,
  setIsOpen,
  trackingExperiments,
  user,
}: StepComponentProps): JSX.Element => {
  const [isManagePayments, setIsManagePayments] = useState<boolean | null>(null);

  useEffect(() => {
    analyticsTrack({
      objectName: 'Migrate To PurePlatform Use Case Screen',
      actionName: 'Loaded',
      screen: 'Use case Screen',
      properties: {
        location: 'partner home',
        ...trackingExperiments,
        ...getCommonAnalyticsProperties(user),
        ...getExperimentsForTracking(user),
      },
    });
  }, []);

  const nextClick = () => {
    analyticsTrack({
      objectName: 'Migrate To PurePlatform Use Case Screen Next',
      actionName: 'Clicked',
      screen: 'Use case Screen',
      properties: {
        location: 'partner home',
        ...trackingExperiments,
        ...getCommonAnalyticsProperties(user),
        ...getExperimentsForTracking(user),
      },
    });

    if (isManagePayments) setStep(STEPS.APPLICATION_FORM);
    else setStep(STEPS.HAVE_ALL_CAPABILITIES);
  };

  const learnMoreClick = () => {
    analyticsTrack({
      objectName: 'Migrate To PurePlatform Use Case Screen Learn More',
      actionName: 'Clicked',
      screen: 'Use case Screen',
      properties: {
        location: 'partner home',
        ...trackingExperiments,
        ...getCommonAnalyticsProperties(user),
        ...getExperimentsForTracking(user),
      },
    });
  };

  const buttonResponse = (val) => {
    setIsManagePayments(val);
    analyticsTrack({
      objectName: 'Migrate To PurePlatform Use Case Screen Response',
      actionName: 'Selected',
      screen: 'Use case Screen',
      properties: {
        location: 'partner home',
        response: val ? 'yes' : 'no',
        ...trackingExperiments,
        ...getCommonAnalyticsProperties(user),
        ...getExperimentsForTracking(user),
      },
    });
  };

  const isMobileView = isMobileAndTablet();

  const yesButtonVariant = isManagePayments ? 'primary' : 'secondary';
  const noButtonVariant = !isManagePayments && isManagePayments !== null ? 'primary' : 'secondary';
  const isDisabled = isManagePayments === null;

  return (
    <div>
      <ServiceProvidedHeading>
        {isMobileView ? (
          <MobileHeader
            title="Let’s evaluate your use case together"
            setIsOpen={setIsOpen}
            step={STEPS.EVALUATE_USE_CASE}
            trackingExperiments={trackingExperiments}
          />
        ) : (
          'Let’s evaluate your use case together'
        )}
      </ServiceProvidedHeading>
      <ServiceProvidedDescription>
        Keep your business in mind while answering
      </ServiceProvidedDescription>

      <ManagePaymentsBanner>
        <ManagePaymentHeading>
          Do you have a product to manage payments for your clients?
        </ManagePaymentHeading>
        <ManagePaymentButtonWrap>
          <Button size="medium" variant={yesButtonVariant} onClick={() => buttonResponse(true)}>
            Yes
          </Button>
          <Button size="medium" variant={noButtonVariant} onClick={() => buttonResponse(false)}>
            No
          </Button>
        </ManagePaymentButtonWrap>
      </ManagePaymentsBanner>

      <IntegrateAPIBanner>
        <IntegrateAPIHeading>
          <InfoIcon color="feedback.icon.neutral.intense" size="medium" />
          <span>Integrating APIs</span>
        </IntegrateAPIHeading>
        <IntegrateAPIDesc>
          You will need to integrate with OAuth to receive commissions after switching
        </IntegrateAPIDesc>
        <IntegrateAPICTA>
          <Link
            href={PARTNERSHIPS_WEBSITE_LINKS.PLATFORM_OAUTH_INTEGRATION}
            onClick={learnMoreClick}
            rel="noopener noreferrer"
            target="_blank"
            icon={ArrowRightIcon}
            iconPosition="right"
            variant="anchor"
          >
            Learn more
          </Link>
        </IntegrateAPICTA>
      </IntegrateAPIBanner>
      <ServiceProviderFooter>
        <div className="btn-wrap">
          {isMobileView ? (
            <Button
              isFullWidth
              onClick={nextClick}
              isDisabled={isDisabled}
              icon={ArrowRightIcon}
              iconPosition="right"
            >
              Next
            </Button>
          ) : (
            <Button isFullWidth onClick={nextClick} isDisabled={isDisabled}>
              Next
            </Button>
          )}
        </div>
      </ServiceProviderFooter>
    </div>
  );
};

export default EvaluateUseCase;
