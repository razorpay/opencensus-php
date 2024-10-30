import React, { useEffect, useState } from 'react';
import moment from 'moment';
import { Box } from '@razorpay/blade/components';
import useOnboardingContext from 'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/providers/useOnboardingContext';
import ErrorBoundary from '@razorpay/universe-cli/errorService/ErrorBoundary';
import { sentryHub } from 'apps/pos/src/bootstrap/Wrapper/Wrapper';
import errorService from '@razorpay/universe-cli/errorService';
import PageError from 'apps/pos/src/app/components/PageError';
import { AvailableComponents, MODULES } from 'apps/pos/src/app/types/common';
import BrandEMIForm from 'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/components/PaymentMethods/BrandEMIForm/BrandEMIForm';
import {
  getAllAddedBrands,
  getAllBrandEmiFields,
  getBrandEmiField,
} from 'apps/pos/src/app/utils/paymentsAndServices';
import {
  MODULAR_PRICING_FIELDS,
  PricingStepComponents,
} from 'apps/pos/src/app/types/PaymentsAndService';
import { getComponentFromStep } from 'apps/pos/src/app/utils/modularConfig';
import { ModularOnboardingField, ModularOnboardingOption } from 'apps/pos/src/app/types/modular';

export interface BrandDealerData {
  name: string;
  dealerCode?: string;
  distributorCode?: string;
  stateCode?: string;
  verificationDetailsId: string;
  verificationStatus?: string;
}
export interface BrandEmiFormData {
  [MODULAR_PRICING_FIELDS.STORE_TYPE_FIELD]: string;
  [MODULAR_PRICING_FIELDS.BRAND_DETAILS_FIELD]: BrandDealerData[];
}

export interface BrandEMIFormContainerProps {
  onBrandEmiFieldInputChange: (key: string, value: any) => void;
  hasAddedBrandEMIData: boolean;
  isFormDisabled: boolean;
}
const BrandEMIFormContainer = ({
  onBrandEmiFieldInputChange,
  hasAddedBrandEMIData,
  isFormDisabled,
}: BrandEMIFormContainerProps): JSX.Element | null => {
  const { states, handlers } = useOnboardingContext();
  const { modularConfig, isUpdateModularLoading } = states;
  const { updateModularConfig, handleProceedToNextComponent } = handlers;
  const [brandFields, setBrandFields] = useState<ModularOnboardingField[]>([]);

  const resetBrandFields = () => setBrandFields([]);

  const brandNames =
    getBrandEmiField({
      modularConfig,
      fieldName: MODULAR_PRICING_FIELDS.BRAND_NAME_FIELD,
    })?.meta?.options ?? [];

  const storeTypes =
    getBrandEmiField({
      modularConfig,
      fieldName: MODULAR_PRICING_FIELDS.STORE_TYPE_FIELD,
    })?.meta?.options ?? [];

  const onSubmit = (brandEmiFormData: Record<string, string>) => {
    const payload = {
      ...brandEmiFormData,
      modular_callback: () =>
        handleProceedToNextComponent({
          __typeName: 'custom_routing',
          routerConditions: {
            [AvailableComponents.ADDED_BRAND_INFO]: true,
          },
        }),
    };
    updateModularConfig(payload);
  };

  const handleBrandNameChange = (brandName: string) => {
    updateModularConfig({
      [MODULAR_PRICING_FIELDS.FETCH_FIELDS_FOR_BRAND]: brandName,
      [MODULAR_PRICING_FIELDS.RESET_BRAND_DETAILS_FIELD]: moment().unix(),
    });
  };

  const getBrandRelatedFields = () => {
    const component = getComponentFromStep({
      modularConfig,
      step: MODULAR_PRICING_FIELDS.PRICING_STEP,
      component: PricingStepComponents.BRAND_EMI_COMPONENT,
    });
    if (!component) return [];
    const brandFields = component.meta.brandDataFields ?? [];
    const data = component.fields.filter((field) => brandFields.includes(field.name));
    return data;
  };

  const getMerchantGstDetails = (): { gstNumber: string; gstError: string } => {
    const component = getComponentFromStep({
      modularConfig,
      step: MODULAR_PRICING_FIELDS.PRICING_STEP,
      component: PricingStepComponents.BRAND_EMI_COMPONENT,
    });
    if (!component) return { gstNumber: '', gstError: '' };
    return {
      gstNumber: component.meta.merchantGstField ?? '',
      gstError: component.meta.errorCode ?? '',
    };
  };

  const getFilteredBrandNames = (
    allBrandNames: ModularOnboardingOption[],
  ): ModularOnboardingOption[] => {
    if (!allBrandNames?.length) return [];
    const addedBrands = getAllAddedBrands({ modularConfig }) ?? [];
    if (!addedBrands?.length) return allBrandNames;
    //filter out the already added brands
    return allBrandNames.filter((brand) => {
      const isAdded = addedBrands.find((item) => item.name === brand.value);
      return isAdded ? false : true;
    });
  };

  useEffect(() => {
    if (modularConfig) {
      const data = getBrandRelatedFields();
      setBrandFields(data);
    }
  }, [modularConfig]);

  if (!modularConfig) return null;

  return (
    <Box padding={['spacing.7', 'spacing.6']}>
      <ErrorBoundary
        sentryHub={sentryHub?.sentryHub}
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
        <BrandEMIForm
          hasAddedBrandEMIData={hasAddedBrandEMIData}
          brandRelatedFields={brandFields}
          brandEmiFields={getAllBrandEmiFields({ modularConfig })}
          storeTypes={storeTypes}
          brandNames={getFilteredBrandNames(brandNames)}
          isUpdateModularLoading={isUpdateModularLoading}
          submitBrandHandler={onSubmit}
          onBrandEmiFieldInputChange={onBrandEmiFieldInputChange}
          handleBrandNameChange={handleBrandNameChange}
          merchantGstNumber={getMerchantGstDetails().gstNumber}
          gstErrorMsg={getMerchantGstDetails().gstError}
          resetBrandRelatedFields={resetBrandFields}
          isFormDisabled={isFormDisabled}
        />
      </ErrorBoundary>
    </Box>
  );
};

export default BrandEMIFormContainer;
