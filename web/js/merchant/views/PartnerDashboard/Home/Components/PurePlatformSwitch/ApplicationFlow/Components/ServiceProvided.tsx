import React, { useEffect, useState } from 'react';
import {
  ServiceProvidedHeading,
  ServiceProvidedDescription,
  ServiceProvidedPills,
  ServiceProvidedPillsOption,
  ServiceProviderFooter,
  ServiceProvidedOtherText,
} from 'merchant/views/PartnerDashboard/Home/Components/PurePlatformSwitch/Styled';
import { ArrowRightIcon, Button, TextInput } from '@razorpay/blade/components';
import {
  RESELLER_PARTNER_SERVICES,
  SERVICES_PROVIDED_OPTIONS,
  STEPS,
  StepComponentProps,
} from 'merchant/views/PartnerDashboard/Home/Components/PurePlatformSwitch/Constants';
import { isMobileAndTablet, getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { MobileHeader } from 'merchant/views/PartnerDashboard/Home/Components/PurePlatformSwitch/ApplicationFlow/Components/Header';
import { analyticsTrack } from 'common/utils/analytics';
import { getExperimentsForTracking } from 'merchant/views/PartnerDashboard/Home/Components/utils';

const ServiceProvided = ({
  setStep,
  setIsOpen,
  trackingExperiments,
  user,
}: StepComponentProps): JSX.Element => {
  const [selectedService, setSelectedService] = useState<Array<string>>([]);

  const isResellerPartnerService = () => {
    let isValidate = false;
    if (selectedService && selectedService.length > 0) {
      selectedService.forEach((service) => {
        if (RESELLER_PARTNER_SERVICES.includes(service)) isValidate = true;
      });
    }
    return isValidate;
  };
  const nextClick = () => {
    analyticsTrack({
      objectName: 'Migrate To PurePlatform Service Provider Qn Screen',
      actionName: 'Selected',
      screen: 'Service Provided',
      properties: {
        location: 'partner home',
        ...trackingExperiments,
        ...getCommonAnalyticsProperties(user),
        ...getExperimentsForTracking(user),
      },
    });
    if (isResellerPartnerService()) setStep(STEPS.HAVE_ALL_CAPABILITIES);
    else setStep(STEPS.EVALUATE_USE_CASE);
  };
  const isMobileView = isMobileAndTablet();

  const removeSelection = (arr, value) => {
    const temp = [...arr];
    const index = temp.indexOf(value);
    if (index !== -1) {
      temp.splice(index, 1);
    }

    return temp;
  };

  const updateSelection = (service) => {
    let updatedServices = [...selectedService];
    if (service === 'Other') {
      // if other is selected we uncheck all the other selected services
      updatedServices = [];
      updatedServices.push(service);
    } else if (updatedServices.includes('Other')) {
      // if other is already selected and you try to select some service then remove other from the array
      updatedServices = removeSelection(updatedServices, 'Other');
      updatedServices.push(service);
    } else if (updatedServices.includes(service)) {
      // deletion case
      updatedServices = removeSelection(updatedServices, service);
    } else updatedServices.push(service);

    analyticsTrack({
      objectName: 'Migrate To PurePlatform Service Provider Qn Screen',
      actionName: 'Selected',
      screen: 'Service Provided',
      properties: {
        location: 'partner home',
        ...trackingExperiments,
        fieldSelected: updatedServices,
        ...getCommonAnalyticsProperties(user),
        ...getExperimentsForTracking(user),
      },
    });
    setSelectedService(updatedServices);
  };

  useEffect(() => {
    analyticsTrack({
      objectName: 'Migrate To PurePlatform Service Provider Qn Screen',
      actionName: 'Loaded',
      screen: 'Service Provided',
      properties: {
        location: 'partner home',
        ...trackingExperiments,
        ...getCommonAnalyticsProperties(user),
        ...getExperimentsForTracking(user),
      },
    });
  }, []);

  const isDisabled = !(selectedService && selectedService.length > 0);

  return (
    <div>
      <ServiceProvidedHeading>
        {isMobileView ? (
          <MobileHeader
            title="Select the services you provide"
            setIsOpen={setIsOpen}
            step={STEPS.SERVICE_PROVIDED}
            trackingExperiments={trackingExperiments}
          />
        ) : (
          'Tell us more about the services you provide'
        )}
      </ServiceProvidedHeading>
      <ServiceProvidedDescription>
        Feel free to pick more than one that applies to your business
      </ServiceProvidedDescription>
      <ServiceProvidedPills>
        {SERVICES_PROVIDED_OPTIONS.map((option, index) => (
          <ServiceProvidedPillsOption
            key={`key-${index}`}
            data-testid={option}
            $isActive={selectedService.includes(option)}
            onClick={() => updateSelection(option)}
          >
            {option}
          </ServiceProvidedPillsOption>
        ))}
      </ServiceProvidedPills>
      {selectedService.includes('Other') && (
        <ServiceProvidedOtherText>
          <TextInput
            autoFocus
            label="Mention other services"
            name="otherServices"
            placeholder="Type here"
          />
        </ServiceProvidedOtherText>
      )}
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

export default ServiceProvided;
