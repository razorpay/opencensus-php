import React, { useEffect, useMemo } from 'react';
import {
  ArrowRightIcon,
  Box,
  Button,
  CheckCircleIcon,
  Heading,
  Spinner,
  Text,
  useToast,
} from '@razorpay/blade/components';
import { useForm } from 'react-hook-form';
import { useNavigate } from 'react-router-dom';
import {
  getAdditionalDetailFields,
  getFieldErrorText,
  getFieldRules,
  getInitialMerchantAdditionalDetails,
  getNecessityIndicator,
  trimWhitespace,
} from 'apps/pos/src/app/utils/merchantAdditionalDetails';
import useOnboardingContext from 'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/providers/useOnboardingContext';
import { useScreen } from 'apps/pos/src/app/utils/hooks/useScreen';
import FormField from 'apps/pos/src/app/components/FormField';
import { BASE_ROUTE, ONBOARDING_ROUTE } from 'apps/pos/src/app/routes';
import { isStringValue } from 'apps/pos/src/app/utils/modularTypeResolvers';
import { MODULAR_ADDITIONAL_DETAILS_FIELDS } from 'apps/pos/src/app/types/MerchantAdditionalDetails';
import { trackEvent, analyticsTypes } from 'apps/pos/src/services/analytics';
import { isKycQualified } from 'apps/pos/src/app/utils/merchantActivation';

const ContinueButton = ({ isDisabled, isFullWidth, isLoading }) => {
  return (
    <Button
      isDisabled={isDisabled}
      isFullWidth={isFullWidth}
      isLoading={isLoading}
      type="submit"
      iconPosition="right"
      icon={ArrowRightIcon}
    >
      Continue to next step
    </Button>
  );
};

const MerchantAdditionalDetails = (): JSX.Element | null => {
  const toast = useToast();
  const navigate = useNavigate();
  const { states, handlers, values } = useOnboardingContext({
    onModularConfigUpdate: () => {
      toast.show({
        color: 'positive',
        content: 'Successfully updated additional details',
        leading: CheckCircleIcon,
      });
      navigate(`/${BASE_ROUTE}/${ONBOARDING_ROUTE}/${values.merchantId}`, { replace: true });
    },
  });
  const { modularConfig, isUpdateModularLoading, isModularLoading, merchantDetails } = states;
  const { isMobile } = useScreen();
  const { updateModularConfig } = handlers;
  const isFormDisabled = isKycQualified(merchantDetails?.activation?.posActivationStatus);

  const defaultValues = getInitialMerchantAdditionalDetails({ modularConfig });

  const {
    control,
    handleSubmit,
    formState: { errors, isValid },
    getValues,
    reset,
    watch,
  } = useForm({
    mode: 'onChange',
    defaultValues: defaultValues ?? {},
  });

  const omcValue = watch(MODULAR_ADDITIONAL_DETAILS_FIELDS.OMC_FIELD);

  const additionalDetailsFields = useMemo(() => {
    return getAdditionalDetailFields({ modularConfig, omcValue });
  }, [omcValue]);

  useEffect(() => {
    if (modularConfig && defaultValues) {
      reset(defaultValues);
    }
  }, [modularConfig]);

  useEffect(() => {
    if (!omcValue || omcValue === 'none') {
      reset({ ...getValues(), additional_details_sap_code_field: '' });
    }
  }, [omcValue]);

  useEffect(() => {
    trackEvent({
      eventName: analyticsTypes.ANALYTICS_EVENTS.FORM_PAGE,
      action: analyticsTypes.ANALYTICS_ACTIONS.VIEWED,
      properties: {
        l1FunnelStage: analyticsTypes.L1_FUNNEL_STAGE.ADDITIONAL_DETAILS,
        l2FunnelStage: analyticsTypes.L2_FUNNEL_STAGE.TAXATION_AND_COMPLIANCE,
        section: 'Additional Details',
        formName: 'Additional Details',
        subSection: 'Taxation and Compliance',
      },
    });
  }, []);

  const onSubmit = () => {
    trackEvent({
      eventName: analyticsTypes.ANALYTICS_EVENTS.WEBSITE_CTA,
      action: analyticsTypes.ANALYTICS_ACTIONS.CLICKED,
      properties: {
        l1FunnelStage: analyticsTypes.L1_FUNNEL_STAGE.ADDITIONAL_DETAILS,
        l2FunnelStage: analyticsTypes.L2_FUNNEL_STAGE.TAXATION_AND_COMPLIANCE,
        label: 'Continue to next step',
        section: 'Additional Details',
        subSection: 'Taxation and Compliance',
      },
    });

    const trimmedData = trimWhitespace(getValues());
    updateModularConfig(trimmedData);
  };

  const onBottomSheetDismiss = () => {
    trackEvent({
      eventName: analyticsTypes.ANALYTICS_EVENTS.ICON,
      action: analyticsTypes.ANALYTICS_ACTIONS.CLICKED,
      properties: {
        l1FunnelStage: analyticsTypes.L1_FUNNEL_STAGE.ADDITIONAL_DETAILS,
        l2FunnelStage: analyticsTypes.L2_FUNNEL_STAGE.TAXATION_AND_COMPLIANCE,
        section: 'Additional Details',
        subSection: 'Taxation and Compliance',
        type: 'Close Icon',
      },
    });
  };

  const onTextInputFocus = (field) => {
    trackEvent({
      eventName: analyticsTypes.ANALYTICS_EVENTS.FORM_FIELD_FILL,
      action: analyticsTypes.ANALYTICS_ACTIONS.INITIATED,
      properties: {
        l1FunnelStage: analyticsTypes.L1_FUNNEL_STAGE.ADDITIONAL_DETAILS,
        l2FunnelStage: analyticsTypes.L2_FUNNEL_STAGE.TAXATION_AND_COMPLIANCE,
        formName: 'Miscellaneous',
        fieldType: analyticsTypes.FIELD_TYPES.TEXTBOX,
        fieldName: field?.name,
      },
    });
  };

  const onRadioBtnChange = (field) => {
    trackEvent({
      eventName: analyticsTypes.ANALYTICS_EVENTS.FORM_FIELD,
      action: analyticsTypes.ANALYTICS_ACTIONS.SELECTED,
      properties: {
        l1FunnelStage: analyticsTypes.L1_FUNNEL_STAGE.ADDITIONAL_DETAILS,
        l2FunnelStage: analyticsTypes.L2_FUNNEL_STAGE.TAXATION_AND_COMPLIANCE,
        formName: 'Miscellaneous',
        fieldType: analyticsTypes.FIELD_TYPES.RADIO_BUTTON,
        fieldName: field?.name,
        section: 'Additional Details',
        subSection: 'Taxation and Compliance',
      },
    });
  };

  const onDropdownChange = (args) => {
    trackEvent({
      eventName: analyticsTypes.ANALYTICS_EVENTS.FORM_FIELD,
      action: analyticsTypes.ANALYTICS_ACTIONS.SELECTED,
      properties: {
        l1FunnelStage: analyticsTypes.L1_FUNNEL_STAGE.ADDITIONAL_DETAILS,
        l2FunnelStage: analyticsTypes.L2_FUNNEL_STAGE.TAXATION_AND_COMPLIANCE,
        formName: 'Miscellaneous',
        fieldType: analyticsTypes.FIELD_TYPES.DROPDOWN,
        fieldName: args?.values[0],
        section: 'Additional Details',
        subSection: 'Taxation and Compliance',
      },
    });
  };

  if (isModularLoading)
    return (
      <Box
        as="section"
        height="90vh"
        width="100%"
        display="flex"
        alignItems="center"
        justifyContent="center"
      >
        <Spinner color="primary" accessibilityLabel="additional-details-spinner" size="xlarge" />
      </Box>
    );

  if (!modularConfig) return null;

  return (
    <Box padding={['spacing.6', 'spacing.6']}>
      <Box paddingBottom="spacing.7">
        <Heading
          marginBottom="spacing.2"
          color="surface.text.gray.normal"
          weight="semibold"
          as="h5"
        >
          Miscellaneous Information
        </Heading>
        <Text color="surface.text.gray.muted">
          We require this information for taxation and compliance.
        </Text>
      </Box>
      <Box maxWidth={isMobile ? '768px' : '540px'}>
        <form noValidate onSubmit={handleSubmit(onSubmit)}>
          <Box display="flex" flexDirection="column" gap="spacing.7">
            {additionalDetailsFields?.map((item) => {
              return !item.isHidden && !item.isInternal ? (
                <FormField
                  key={item.name}
                  type={item?.meta?.dataType ?? ''}
                  label={item?.meta?.title ?? ''}
                  necessityIndicator={getNecessityIndicator({ field: item, omcValue })}
                  control={control}
                  name={item.name}
                  errorText={getFieldErrorText({ item, errors, omcValue }) ?? ''}
                  rules={getFieldRules({ field: item, omcValue })}
                  selectOptions={item?.meta?.options ?? []}
                  defaultValue={isStringValue(item) ? item.stringValue : ''}
                  isDisabled={isFormDisabled || item?.isDisabled}
                  onBottomSheetDismissCallback={onBottomSheetDismiss}
                  onTextInputClick={onTextInputFocus}
                  onRadioBtnChangeCallback={onRadioBtnChange}
                  onDropdownChangeCallback={onDropdownChange}
                />
              ) : null;
            })}
          </Box>
          {isMobile ? (
            <Box
              display="flex"
              justifyContent="center"
              position="fixed"
              bottom="0px"
              padding="spacing.4"
              backgroundColor="surface.background.gray.intense"
              left="0px"
              right="0px"
              zIndex="1"
            >
              <ContinueButton
                isDisabled={isFormDisabled || !isValid}
                isFullWidth={isMobile}
                isLoading={isUpdateModularLoading}
              />
            </Box>
          ) : (
            <Box marginTop="spacing.9">
              <ContinueButton
                isDisabled={isFormDisabled || !isValid}
                isFullWidth={isMobile}
                isLoading={isUpdateModularLoading}
              />
            </Box>
          )}
        </form>
      </Box>
    </Box>
  );
};

export default MerchantAdditionalDetails;
