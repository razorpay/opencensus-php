import React from 'react';
import moment from 'moment';
import { Box } from '@razorpay/blade/components';
import useOnboardingContext from 'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/providers/useOnboardingContext';
import ErrorBoundary from '@razorpay/universe-cli/errorService/ErrorBoundary';
import errorService from '@razorpay/universe-cli/errorService';
import PageError from 'apps/pos/src/app/components/PageError';
import { AvailableComponents, MODULES } from 'apps/pos/src/app/types/common';
import AddedBrandInfo from 'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/components/PaymentMethods/BrandEMIForm/AddedBrandInfo';
import { MODULAR_PRICING_FIELDS } from 'apps/pos/src/app/types/PaymentsAndService';
import { isStringValue } from 'apps/pos/src/app/utils/modularTypeResolvers';
import {
  getAllAddedBrands,
  getBrandEmiCcDcEnabledStatus,
  getBrandEmiField,
} from 'apps/pos/src/app/utils/paymentsAndServices';
import { analyticsTypes, trackEvent } from 'apps/pos/src/services/analytics';

interface AddedBrandInfoContainerProps {
  isFormDisabled: boolean;
}
const AddedBrandInfoContainer = ({
  isFormDisabled,
}: AddedBrandInfoContainerProps): JSX.Element | null => {
  const { states, handlers } = useOnboardingContext();
  const { modularConfig, isUpdateModularLoading } = states;
  const { handleProceedToNextComponent, updateModularConfig } = handlers;

  const isBrandEmiCcDcEnabled = getBrandEmiCcDcEnabledStatus(modularConfig);

  const redirectToBrandEMIForm = () => {
    handleProceedToNextComponent({
      __typeName: 'custom_routing',
      routerConditions: {
        [AvailableComponents.BRAND_EMI_FORM]: true,
      },
    });
  };

  const removeBrandHandler = (brandName: string) => {
    trackEvent({
      eventName: analyticsTypes.ANALYTICS_EVENTS.ICON,
      action: analyticsTypes.ANALYTICS_ACTIONS.CLICKED,
      properties: {
        type: 'Delete icon',
        section: analyticsTypes.L1_FUNNEL_STAGE.PAYMENT_METHOD_AND_SERVICE_SELECTION,
        subSection: analyticsTypes.L2_FUNNEL_STAGE.BRAND_EMI_FORM,
        l1FunnelStage: analyticsTypes.L1_FUNNEL_STAGE.PAYMENT_METHOD_AND_SERVICE_SELECTION,
        l2FunnelStage: analyticsTypes.L2_FUNNEL_STAGE.BRAND_EMI_FORM,
      },
    });
    updateModularConfig({
      [MODULAR_PRICING_FIELDS.REMOVE_BRAND_DETAILS_FIELD]: true,
      [MODULAR_PRICING_FIELDS.BRAND_NAME_FIELD]: brandName,
    });
  };

  const addBrandHandler = () => {
    trackEvent({
      eventName: analyticsTypes.ANALYTICS_EVENTS.WEBSITE_CTA,
      action: analyticsTypes.ANALYTICS_ACTIONS.CLICKED,
      properties: {
        label: 'Add New Brand',
        section: analyticsTypes.L1_FUNNEL_STAGE.PAYMENT_METHOD_AND_SERVICE_SELECTION,
        subSection: analyticsTypes.L2_FUNNEL_STAGE.BRAND_EMI_FORM,
        l1FunnelStage: analyticsTypes.L1_FUNNEL_STAGE.PAYMENT_METHOD_AND_SERVICE_SELECTION,
        l2FunnelStage: analyticsTypes.L2_FUNNEL_STAGE.BRAND_EMI_FORM,
      },
    });
    updateModularConfig({
      [MODULAR_PRICING_FIELDS.RESET_BRAND_DETAILS_FIELD]: moment().unix(),
      [MODULAR_PRICING_FIELDS.MODULAR_CALLBACK]: redirectToBrandEMIForm,
    });
  };

  const submitHandler = () => {
    trackEvent({
      eventName: analyticsTypes.ANALYTICS_EVENTS.WEBSITE_CTA,
      action: analyticsTypes.ANALYTICS_ACTIONS.CLICKED,
      properties: {
        label: 'Save All',
        section: analyticsTypes.L1_FUNNEL_STAGE.PAYMENT_METHOD_AND_SERVICE_SELECTION,
        subSection: analyticsTypes.L2_FUNNEL_STAGE.BRAND_EMI_FORM,
        l1FunnelStage: analyticsTypes.L1_FUNNEL_STAGE.PAYMENT_METHOD_AND_SERVICE_SELECTION,
        l2FunnelStage: analyticsTypes.L2_FUNNEL_STAGE.BRAND_EMI_FORM,
      },
    });
    handleProceedToNextComponent({
      __typeName: 'custom_routing',
      routerConditions: {
        [AvailableComponents.PAYMENT_METHODS]: true,
      },
    });
  };

  const getInitialStoreTypeValue = () => {
    const storeTypeField = getBrandEmiField({
      modularConfig,
      fieldName: MODULAR_PRICING_FIELDS.STORE_TYPE_FIELD,
    });
    if (!storeTypeField) return '';
    const options = storeTypeField.meta?.options;
    if (!options) return '';
    const storeTypeValue = isStringValue(storeTypeField) ? storeTypeField.stringValue : '';
    const storeType = options.find((option) => option.value === storeTypeValue)?.label ?? '';
    return storeType;
  };

  if (!modularConfig) return null;
  return (
    <Box padding={['spacing.7', 'spacing.6']}>
      <ErrorBoundary
        rank={errorService.ErrorRank.P0}
        tags={{ module: MODULES.PAYMENT_METHODS }}
        fallbackComponent={
          <Box marginTop="spacing.8">
            <PageError
              title="Something went wrong!"
              description="We are facing some issues. Please try again later."
            />
          </Box>
        }
      >
        <AddedBrandInfo
          storeType={getInitialStoreTypeValue()}
          removeBrandHandler={removeBrandHandler}
          addBrandHandler={addBrandHandler}
          submitHandler={submitHandler}
          brands={getAllAddedBrands({ modularConfig }) ?? []}
          isUpdateModularLoading={isUpdateModularLoading}
          isFormDisabled={isFormDisabled}
          isBrandEmiCcDcEnabled={isBrandEmiCcDcEnabled}
        />
      </ErrorBoundary>
    </Box>
  );
};

export default AddedBrandInfoContainer;
