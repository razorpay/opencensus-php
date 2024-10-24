import React, { useEffect } from 'react';
import { Alert, Box, Button, Divider, Heading, Text } from '@razorpay/blade/components';
import { useForm } from 'react-hook-form';
import FormField from 'apps/pos/src/app/components/FormField';
import { MODULAR_PRICING_FIELDS } from 'apps/pos/src/app/types/PaymentsAndService';
import { ModularOnboardingField } from 'apps/pos/src/app/types/modular';
import { getSelectedStoreName } from 'apps/pos/src/app/utils/paymentsAndServices';
import { isStringValue } from 'apps/pos/src/app/utils/modularTypeResolvers';
import {
  getFieldErrorText,
  getFieldRules,
} from 'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/components/PaymentMethods/BrandEMIForm/helpers';

interface DropdownOption {
  label: string;
  value: string;
}
export interface BrandEMIFormProps {
  storeTypes: DropdownOption[] | null;
  brandNames: DropdownOption[] | null;
  submitBrandHandler: (payload) => void;
  isUpdateModularLoading: boolean;
  brandEmiFields: ModularOnboardingField[];
  onBrandEmiFieldInputChange: (key: string, value: any) => void;
  handleBrandNameChange: (brandName: string) => void;
  resetBrandRelatedFields: () => void;
  brandRelatedFields: ModularOnboardingField[];
  merchantGstNumber: string;
  gstErrorMsg: string;
  hasAddedBrandEMIData: boolean;
}

const defaultValues: Record<string, string> = {
  [MODULAR_PRICING_FIELDS.BRAND_NAME_FIELD]: '',
  [MODULAR_PRICING_FIELDS.STORE_TYPE_FIELD]: '',
  [MODULAR_PRICING_FIELDS.DEALER_CODE_FIELD]: '',
  [MODULAR_PRICING_FIELDS.STATE_CODE_FIELD]: '',
  [MODULAR_PRICING_FIELDS.DISTRIBUTOR_CODE_FIELD]: '',
  [MODULAR_PRICING_FIELDS.MERCHANT_GST_FIELD]: '',
};

const BrandEMIForm = ({
  storeTypes,
  brandNames,
  submitBrandHandler,
  isUpdateModularLoading,
  brandEmiFields,
  onBrandEmiFieldInputChange,
  handleBrandNameChange,
  brandRelatedFields,
  merchantGstNumber,
  gstErrorMsg,
  hasAddedBrandEMIData,
  resetBrandRelatedFields,
}: BrandEMIFormProps) => {
  const {
    control,
    handleSubmit,
    formState: { errors },
    getValues,
    setValue,
  } = useForm({
    mode: 'onChange',
    defaultValues,
  });

  const brandNameField = brandEmiFields.find(
    (field) => field.name === MODULAR_PRICING_FIELDS.BRAND_NAME_FIELD,
  );
  const storeTypeField = brandEmiFields.find(
    (field) => field.name === MODULAR_PRICING_FIELDS.STORE_TYPE_FIELD,
  );

  const resetFormValues = () => {
    for (const key in defaultValues) {
      if (defaultValues.hasOwnProperty(key)) {
        if (
          key !== MODULAR_PRICING_FIELDS.BRAND_NAME_FIELD &&
          key !== MODULAR_PRICING_FIELDS.STORE_TYPE_FIELD
        ) {
          setValue(key, '');
        }
      }
    }
  };
  const onDropdownChange = (args) => {
    resetBrandRelatedFields();
    resetFormValues();
    handleBrandNameChange(args.values[0]);
  };

  const onSubmit = () => {
    const formValues = { ...getValues() };
    for (const key in formValues) {
      if (formValues.hasOwnProperty(key)) {
        const isRelatedField = brandRelatedFields.find((field) => field.name === key);
        if (!isRelatedField) {
          delete formValues[key];
        }
      }
    }
    submitBrandHandler({
      [MODULAR_PRICING_FIELDS.STORE_TYPE_FIELD]:
        getValues()[MODULAR_PRICING_FIELDS.STORE_TYPE_FIELD],
      [MODULAR_PRICING_FIELDS.BRAND_NAME_FIELD]:
        getValues()[MODULAR_PRICING_FIELDS.BRAND_NAME_FIELD],
      ...formValues,
    });
  };

  const isFieldDisabled = ({ fieldName, merchantGstField }) => {
    if (fieldName === MODULAR_PRICING_FIELDS.MERCHANT_GST_FIELD && merchantGstField) return true;
    if (gstErrorMsg) return true;
    return false;
  };

  const getDefaultValue = ({
    fieldName,
    merchantGstField,
  }: {
    fieldName: string;
    merchantGstField: string;
  }): string => {
    if (fieldName !== MODULAR_PRICING_FIELDS.MERCHANT_GST_FIELD) return '';
    return merchantGstField;
  };

  useEffect(() => {
    if (hasAddedBrandEMIData && storeTypeField) {
      setValue(
        MODULAR_PRICING_FIELDS.STORE_TYPE_FIELD,
        isStringValue(storeTypeField) ? storeTypeField.stringValue : '',
      );
    }
  }, [hasAddedBrandEMIData]);

  useEffect(() => {
    setValue(MODULAR_PRICING_FIELDS.MERCHANT_GST_FIELD, merchantGstNumber);
  }, [merchantGstNumber]);

  useEffect(() => {
    if (!getValues()[MODULAR_PRICING_FIELDS.BRAND_NAME_FIELD]) {
      resetBrandRelatedFields();
    }
  }, [JSON.stringify(brandRelatedFields)]);

  return (
    <Box>
      <Heading marginBottom="spacing.7" size="medium" weight="semibold">
        Brand Information Form
      </Heading>
      <form noValidate onSubmit={handleSubmit(onSubmit)}>
        <Box paddingBottom="spacing.7">
          {!hasAddedBrandEMIData ? (
            <FormField
              key={storeTypeField?.name ?? ''}
              control={control}
              name={storeTypeField?.name ?? ''}
              label={storeTypeField?.meta?.title ?? ''}
              necessityIndicator={
                storeTypeField?.meta?.validations?.[0].type === 'isRequired' ? 'required' : 'none'
              }
              type={storeTypeField?.meta?.dataType ?? 'select'}
              rules={{
                required: storeTypeField?.meta?.validations?.[0].type === 'isRequired',
              }}
              errorText="Please select store type"
              isDisabled={false}
              defaultValue={''}
              selectOptions={storeTypes ?? [{ label: 'Multi', value: 'multi' }]}
              onDropdownChangeCallback={(e) => onBrandEmiFieldInputChange(e.name, e.values[0])}
            />
          ) : (
            <Box gap="spacing.6" display="flex" justifyContent="flex-sart" alignItems="center">
              <Text>{storeTypeField?.meta?.title}</Text>
              <Text weight="semibold">{getSelectedStoreName(storeTypeField)}</Text>
            </Box>
          )}
        </Box>
        <Divider height={'spacing.1'} />
        <Box paddingTop="spacing.7" paddingBottom="spacing.7">
          <FormField
            testID="brand-name-select"
            key={brandNameField?.name ?? ''}
            control={control}
            name={brandNameField?.name ?? ''}
            label={brandNameField?.meta?.title ?? ''}
            necessityIndicator={brandNameField?.meta?.validations?.[0].type ? 'required' : 'none'}
            type={brandNameField?.meta?.dataType ?? 'select'}
            rules={{ required: brandNameField?.meta?.validations?.[0].type === 'isRequired' }}
            errorText="Please select brand name"
            isDisabled={false}
            defaultValue={''}
            selectOptions={brandNames ?? []}
            onDropdownChangeCallback={onDropdownChange}
          />
          <Box marginTop="spacing.5">
            {brandRelatedFields.length
              ? brandRelatedFields.map((item) => (
                  <Box key={item.name} marginBottom="spacing.4">
                    <FormField
                      control={control}
                      name={item.name}
                      label={item.meta?.title ?? ''}
                      necessityIndicator="required"
                      type={item.meta?.dataType ?? 'string'}
                      rules={getFieldRules(item.name, brandRelatedFields)}
                      errorText={getFieldErrorText({ item, errors })}
                      isDisabled={isFieldDisabled({
                        fieldName: item.name,
                        merchantGstField: merchantGstNumber,
                      })}
                      defaultValue={getDefaultValue({
                        fieldName: item.name,
                        merchantGstField: merchantGstNumber,
                      })}
                    />
                  </Box>
                ))
              : null}
          </Box>
          {gstErrorMsg ? (
            <Box padding={['spacing.5', 'spacing.0']}>
              <Alert
                title={''}
                description={gstErrorMsg}
                marginBottom={'spacing.4'}
                color="notice"
                isDismissible={false}
              />
            </Box>
          ) : null}
        </Box>
        <Box
          backgroundColor="surface.background.gray.intense"
          padding="spacing.5"
          display="flex"
          position="fixed"
          left="0px"
          right="0px"
          bottom="0px"
          elevation="highRaised"
          alignItems="center"
          width="100%"
          zIndex={1}
        >
          <Button
            type="submit"
            variant="primary"
            isFullWidth
            isDisabled={isUpdateModularLoading || !!gstErrorMsg}
            isLoading={isUpdateModularLoading}
          >
            Save
          </Button>
        </Box>
      </form>
    </Box>
  );
};

export default BrandEMIForm;
