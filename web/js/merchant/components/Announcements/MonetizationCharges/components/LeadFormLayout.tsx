import React, { useCallback, useEffect } from 'react';
import { ArrowRightIcon, Box, Button, useToast } from '@razorpay/blade/components';
import useForm from '../hooks/useForm';
import validate from '../utils/validator';
import { FORM_FIELDS } from '../constants/fields';
import { submitSFLead } from '../utils/salesForce';
import { useBladeBreakpoints } from 'merchant/views/POS/hooks';
import FormFields from './FormFields';

interface LeadFormLayoutProps {
  formDestination?: string;
  formCampaignId: string;
  onClose?: () => void;
  closeOnSubmit?: boolean;
  onSubmitError?: () => void;
  onSubmitSuccess?: () => void;
}

const LeadFormLayout = ({
  formDestination = 'salesforce',
  formCampaignId,
  onClose = () => {},
  closeOnSubmit = true,
  onSubmitError,
  onSubmitSuccess,
}: LeadFormLayoutProps) => {
  const getFormInputPayload = (values) => {
    if (formDestination === 'salesforce') {
      const payload = {};
      FORM_FIELDS.forEach((field) => {
        const inputValue = values[field.name];
        const transformedValue =
          field.transformValues && inputValue ? field.transformValues(inputValue) : inputValue;

        payload[field.destinationKey] = transformedValue;
      });
      return payload;
    }
    throw new Error('Invalid form destination');
  };

  const submitForm = async (values) => {
    const formInputPayload = getFormInputPayload(values);

    if (formDestination === 'salesforce') {
      await submitSFLead({
        Name: values?.name,
        Email: values?.email,
        Company: 'NA', // Company is mandatory in SF
        ...formInputPayload,
        Campaign_Name__c: formCampaignId,
        LeadSource: formCampaignId,
        Traffic_Campaign__c: 'NA',
        Traffic_Source__c: 'NA',
        Traffic_Medium__c: 'NA',
        Referred_By__c: document.referrer || 'NA',
      });
    }
  };

  const { isDesktop } = useBladeBreakpoints();
  const toast = useToast();

  const {
    values,
    isSubmitting,
    errors,
    handleChange,
    handleSubmit,
    submitError,
    isSubmitted,
    clearForm,
  } = useForm({
    callback: submitForm,
    validate,
    onSubmitError: () => {},
    onValidationError: () => {},
  });

  const handleModalClose = useCallback(() => {
    clearForm();
    onClose();
  }, [clearForm, onClose]);

  useEffect(() => {
    if (isSubmitted && submitError) {
      toast.show({
        content: 'Form submission failed',
        color: 'negative',
        autoDismiss: true,
      });
      onSubmitError?.();
    }
    if (isSubmitted && !submitError) {
      toast.show({
        content: 'Form submitted successfully',
        color: 'positive',
        autoDismiss: true,
      });
      onSubmitSuccess?.();
    }
    if (isSubmitted && closeOnSubmit) {
      handleModalClose();
    }
  }, [
    closeOnSubmit,
    FORM_FIELDS,
    handleModalClose,
    onClose,
    onSubmitError,
    onSubmitSuccess,
    submitError,
    isSubmitted,
    values,
  ]);

  const requiredFields = FORM_FIELDS.filter((field) => field.isRequired === true);
  const isAllRequiredFieldsFilled = requiredFields.every((field) => {
    const fieldVal = values[field.name];
    return fieldVal && fieldVal.trim() !== '';
  });
  const isSubmitButtonDisabled = isSubmitting || !isAllRequiredFieldsFilled;

  return (
    <>
      <FormFields errors={errors} handleChange={handleChange} values={values} />
      <Box display="flex" justifyContent="flex-end">
        <Button
          isFullWidth={!isDesktop}
          color="primary"
          iconPosition="right"
          icon={ArrowRightIcon}
          onClick={handleSubmit}
          isDisabled={isSubmitButtonDisabled}
        >
          Submit
        </Button>
      </Box>
    </>
  );
};

export default LeadFormLayout;
