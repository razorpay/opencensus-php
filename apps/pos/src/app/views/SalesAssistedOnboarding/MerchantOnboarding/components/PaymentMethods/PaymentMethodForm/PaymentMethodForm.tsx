import React, { useEffect, useState } from 'react';
import {
  Box,
  Heading,
  Checkbox,
  TextInput,
  Link,
  EditIcon,
  Button,
  ArrowRightIcon,
  useToast,
  Text,
} from '@razorpay/blade/components';
import { useParams } from 'react-router-dom';
import SalesFileUpload from 'apps/pos/src/app/components/SalesFileUpload';
import { FileItem } from 'apps/pos/src/app/types/fileUpload';
import {
  AggregatorModelForm,
  DirectModelForm,
  PaymentMethodFormType,
  PaymentMethodsFieldKeyNames,
} from 'apps/pos/src/app/types/PaymentsAndService';
import {
  AggregatorModelFormKeys,
  DirectModelFormKeys,
} from 'apps/pos/src/app/constants/PaymentsAndService';
import { trackEvent } from 'apps/pos/src/services/analytics';
import {
  ANALYTICS_ACTIONS,
  ANALYTICS_EVENTS,
  L1_FUNNEL_STAGE,
  L2_FUNNEL_STAGE,
} from 'apps/pos/src/services/analytics/types';
import {
  handleMdrEditAnalytics,
  handleVasEditAnalytics,
} from 'apps/pos/src/app/utils/paymentsAndServices';

export type PaymentMethodForm = {
  type: PaymentMethodFormType;
  form: DirectModelForm | AggregatorModelForm;
};

export interface PaymentMethodFormProps {
  methodForm: PaymentMethodForm;
  onFileUploadChange: (files: FileItem[]) => void;
  onFieldCheckboxChange: (key: string) => void;
  onFieldInputChange: (key: string, value: any) => void;
  onFormSubmitClick: () => void;
  isFormDisabled: boolean;
  isModularLoading?: boolean;
  removeExistingPricingDocs: () => void;
}

const PaymentMethodFormComponent: React.FC<PaymentMethodFormProps> = ({
  methodForm,
  onFileUploadChange,
  onFieldCheckboxChange,
  onFieldInputChange,
  onFormSubmitClick,
  isFormDisabled,
  removeExistingPricingDocs,
}) => {
  const { form } = methodForm;
  const toast = useToast();
  const { id } = useParams();
  const [isMDREditEnabled, setIsMDREditEnabled] = useState(false);
  const [isVASEditEnabled, setIsVASEditEnabled] = useState(false);

  const onEditVASClick = (label: string) => {
    handleVasEditAnalytics(label);
    if (isVASEditEnabled) {
      removeExistingPricingDocs();
    }
    setIsVASEditEnabled((prev) => !prev);
  };

  const onEditMDRClick = (label: string) => {
    handleMdrEditAnalytics(label);
    if (isMDREditEnabled) {
      removeExistingPricingDocs();
    }
    setIsMDREditEnabled((prev) => !prev);
  };

  const isAggregatorFieldsPresent =
    Object.keys(form).filter((key) =>
      AggregatorModelFormKeys.includes(key as keyof AggregatorModelForm),
    ).length > 0;

  const getKeyName = (key: string) => {
    if (key === PaymentMethodsFieldKeyNames.VAS_CC_EMI_RATE_FIELD)
      return PaymentMethodsFieldKeyNames.VAS_CC_EMI_RATE_ENABLED_FIELD;
    if (key === PaymentMethodsFieldKeyNames.VAS_DC_EMI_RATE_FIELD)
      return PaymentMethodsFieldKeyNames.VAS_DC_EMI_RATE_ENABLED_FIELD;
    return key;
  };

  const getCheckedStatus = (key: string, form) => {
    if (key === PaymentMethodsFieldKeyNames.VAS_CC_EMI_RATE_FIELD) {
      return form[PaymentMethodsFieldKeyNames.VAS_CC_EMI_RATE_ENABLED_FIELD]?.checked ?? false;
    }
    if (key === PaymentMethodsFieldKeyNames.VAS_DC_EMI_RATE_FIELD) {
      return form[PaymentMethodsFieldKeyNames.VAS_DC_EMI_RATE_ENABLED_FIELD]?.checked ?? false;
    }
    return false;
  };

  const getHeading = (isAggregatorFieldsPresent) => {
    if (isAggregatorFieldsPresent) return 'Choose MDR Rates & Value Added Services';
    return 'Choose Value Added Services';
  };

  useEffect(() => {
    if (form) {
      trackEvent({
        eventName: ANALYTICS_EVENTS.FORM_PAGE,
        action: ANALYTICS_ACTIONS.VIEWED,
        properties: {
          formName: isAggregatorFieldsPresent
            ? 'Aggregator Model form screen'
            : 'Direct Model form screen',
          section: 'Payment Method & Service Selection',
          subSection: isAggregatorFieldsPresent ? 'Aggregator Model' : 'Direct Model',
          l1FunnelStage: L1_FUNNEL_STAGE.PAYMENT_METHOD_AND_SERVICE_SELECTION,
          l2FunnelStage: isAggregatorFieldsPresent
            ? L2_FUNNEL_STAGE.AGGREGATOR_MODEL
            : L2_FUNNEL_STAGE.DIRECT_MODEL,
        },
      });
    }
  }, [isAggregatorFieldsPresent]);

  return (
    <Box maxWidth="768px" padding="spacing.5" height="80vh" overflow="scroll">
      <Box display="flex" flexDirection="row" justifyContent="space-between">
        <Heading marginBottom="spacing.5" size="large">
          {getHeading(isAggregatorFieldsPresent)}
        </Heading>
        {!isAggregatorFieldsPresent ? (
          <Link
            iconPosition="left"
            icon={!isVASEditEnabled ? EditIcon : undefined}
            onClick={() => onEditVASClick(isVASEditEnabled ? 'Save Changes' : 'Edit')}
            alignSelf="center"
            testID="edit-save-btn"
            isDisabled={isFormDisabled}
            variant="button"
          >
            {isVASEditEnabled ? 'Save Changes' : 'Edit'}
          </Link>
        ) : null}
      </Box>
      {Object.keys(form).filter((key) =>
        [PaymentMethodsFieldKeyNames.CUSTOM_RATES_DOCUMENTS_FIELD].includes(
          key as PaymentMethodsFieldKeyNames,
        ),
      ).length ? (
        <Box>
          <SalesFileUpload
            merchantId={id}
            name={PaymentMethodsFieldKeyNames.CUSTOM_PRICING_PROOF}
            label="Upload custom rates proof"
            accept=".pdf,.jpeg,.jpg,.png"
            uploadType="multiple"
            onChange={onFileUploadChange}
            maxSize={5 * 1024 * 1023}
            maxLimit={5}
            isLoading={false}
            defaultValue={form[PaymentMethodsFieldKeyNames.CUSTOM_RATES_DOCUMENTS_FIELD].value}
            onError={() =>
              toast.show({
                content: `Some Error Occured while upload file. Please try again later`,
                color: 'negative',
              })
            }
            isDisabled={isFormDisabled || isMDREditEnabled || isVASEditEnabled}
            value={form[PaymentMethodsFieldKeyNames.CUSTOM_RATES_DOCUMENTS_FIELD].value}
          />
        </Box>
      ) : null}
      {isAggregatorFieldsPresent ? (
        <>
          <Box
            display="flex"
            flexDirection="row"
            justifyContent="space-between"
            marginBottom="spacing.5"
          >
            <Heading size="medium">MDR Rates</Heading>
            <Link
              isDisabled={isFormDisabled}
              iconPosition="left"
              icon={!isMDREditEnabled ? EditIcon : undefined}
              onClick={() => onEditMDRClick(isMDREditEnabled ? 'Save Changes' : 'Edit')}
              alignSelf="center"
              testID="mdr-edit-save"
              variant="button"
            >
              {isMDREditEnabled ? 'Save Changes' : 'Edit'}
            </Link>
          </Box>
          <Box>
            {Object.keys(form)
              .filter((key) => AggregatorModelFormKeys.includes(key as keyof AggregatorModelForm))
              .map((key, i) => {
                const field = form[key];
                return (
                  <Box
                    key={`${key}_${i}`}
                    display="flex"
                    flexDirection="row"
                    justifyContent="space-between"
                    marginBottom="spacing.5"
                  >
                    <Box
                      display={'flex'}
                      flexDirection={'column'}
                      gap={'spacing.1'}
                      justifyContent={'center'}
                      alignItems={'flex-start'}
                    >
                      <Text color="surface.text.gray.subtle"> {field.title} </Text>
                      <Text color="surface.text.gray.muted" variant="caption">
                        {field.description}
                      </Text>
                    </Box>
                    <Box width="spacing.11" minWidth={'80px'} maxWidth={'120px'}>
                      {isMDREditEnabled ? (
                        <TextInput
                          type="number"
                          size="medium"
                          label=""
                          isDisabled={!isMDREditEnabled}
                          value={field.value}
                          onChange={(e) => onFieldInputChange(key, e.value)}
                          suffix={'%'}
                          testID={key}
                        />
                      ) : (
                        <Text testID={key} color="surface.text.gray.subtle" textAlign="right">
                          {field.value || Number(field.defaultValue)} %
                        </Text>
                      )}
                    </Box>
                  </Box>
                );
              })}
          </Box>
          <Box
            display="flex"
            flexDirection="row"
            justifyContent="space-between"
            marginBottom="spacing.5"
          >
            <Heading size="medium">Affordability Category</Heading>
            <Link
              isDisabled={isFormDisabled}
              iconPosition="left"
              icon={!isVASEditEnabled ? EditIcon : undefined}
              onClick={() => onEditVASClick(isVASEditEnabled ? 'Save Changes' : 'Edit')}
              alignSelf="center"
              testID="vas-edit-save"
              variant="button"
            >
              {isVASEditEnabled ? 'Save Changes' : 'Edit'}
            </Link>
          </Box>
        </>
      ) : null}
      {Object.keys(form)
        .filter((key) => DirectModelFormKeys.includes(key as keyof DirectModelForm))
        .map((key, i) => {
          const field = form[key];
          return (
            <Box
              key={`${key}_${i}`}
              display="flex"
              flexDirection="row"
              justifyContent="space-between"
              marginBottom="spacing.5"
            >
              <Checkbox
                isDisabled={isFormDisabled}
                name={key}
                size="medium"
                helpText={field.description}
                isChecked={getCheckedStatus(key, form)}
                onChange={() => onFieldCheckboxChange(getKeyName(key))}
              >
                {field.title}
              </Checkbox>
              <Box width="spacing.11" minWidth={'80px'} maxWidth={'120px'}>
                {isVASEditEnabled ? (
                  <TextInput
                    size="medium"
                    label=""
                    isDisabled={!isVASEditEnabled}
                    value={field.value}
                    onChange={(e) => onFieldInputChange(key, e.value)}
                    suffix="%"
                    testID={key}
                  />
                ) : (
                  <Text testID={key} color="surface.text.gray.subtle" textAlign="right">
                    {field.value || Number(field.defaultValue)} %
                  </Text>
                )}
              </Box>
            </Box>
          );
        })}
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
          onClick={onFormSubmitClick}
          icon={ArrowRightIcon}
          iconPosition="right"
          variant="primary"
          isFullWidth
          isDisabled={isMDREditEnabled || isVASEditEnabled}
        >
          Save & Continue
        </Button>
      </Box>
    </Box>
  );
};

export default PaymentMethodFormComponent;
