import React, { useEffect } from 'react';
import { useParams } from 'react-router-dom';
import {
  ArrowRightIcon,
  Box,
  Button,
  Heading,
  TextArea,
  useToast,
} from '@razorpay/blade/components';
import SalesFileUpload from 'apps/pos/src/app/components/SalesFileUpload';
import { handleNachFormViewAnalytics } from 'apps/pos/src/app/utils/paymentsAndServices';

export enum NachFormKeyNames {
  NACH_FORM_DOCUMENT_FIELD = 'nach_form_document_field',
  NACH_FORM_COMMENTS_FIELD = 'nach_form_comments_field',
  NACH_DOCUMENT_NAME = 'nach',
  MODULAR_CALLBACK = 'modular_callback',
}

export type NachFormObject = {
  [NachFormKeyNames.NACH_FORM_DOCUMENT_FIELD]: any;
  [NachFormKeyNames.NACH_FORM_COMMENTS_FIELD]: string;
};

export interface NachFormProps {
  nachForm: NachFormObject;
  onNachTextAreaChange: () => void;
  onNachFileUploadChange: () => void;
  onNachSubmitClick: () => void;
  onNachSkipClick: () => void;
  isFormDisabled: boolean;
  isModularLoading?: boolean;
  isLoading?: boolean;
}

const NACHForm: React.FC<NachFormProps> = ({
  nachForm,
  onNachTextAreaChange,
  onNachFileUploadChange,
  onNachSubmitClick,
  onNachSkipClick,
  isFormDisabled,
  isModularLoading,
  isLoading,
}) => {
  const { id } = useParams();
  const toast = useToast();

  useEffect(() => {
    if (!isModularLoading) {
      handleNachFormViewAnalytics();
    }
  }, []);

  if (isModularLoading) return null;

  return (
    <Box padding="spacing.5">
      <Heading marginBottom="spacing.5" size="large">
        Upload NACH Form
      </Heading>
      <SalesFileUpload
        merchantId={id}
        name={NachFormKeyNames.NACH_DOCUMENT_NAME}
        label="To debit the rental charges from Merchant’s account automatically"
        accept=".pdf,.jpeg,.jpg,.png"
        uploadType="single"
        onChange={onNachFileUploadChange}
        maxSize={5 * 1024 * 1023}
        maxLimit={1}
        isLoading={isLoading}
        defaultValue={nachForm[NachFormKeyNames.NACH_FORM_DOCUMENT_FIELD]}
        onError={() =>
          toast.show({
            content: `Unable to upload file. Please try again`,
            color: 'negative',
            autoDismiss: true,
          })
        }
        isDisabled={isFormDisabled || isLoading}
        value={nachForm[NachFormKeyNames.NACH_FORM_DOCUMENT_FIELD]}
      />
      <Box>
        <Heading marginBottom="spacing.5" size="large">
          Additional Sales Comment
        </Heading>
        <TextArea
          isDisabled={isFormDisabled}
          onChange={onNachTextAreaChange}
          placeholder="Add comments here for sales team"
          label=""
          validationState={
            nachForm[NachFormKeyNames.NACH_FORM_COMMENTS_FIELD]?.trim().length < 4 &&
            !(nachForm[NachFormKeyNames.NACH_FORM_COMMENTS_FIELD]?.length === 0)
              ? 'error'
              : 'none'
          }
          size="large"
          errorText="Please enter more than 3 characters"
          value={nachForm[NachFormKeyNames.NACH_FORM_COMMENTS_FIELD]}
        />
      </Box>
      <Box
        backgroundColor="surface.background.gray.intense"
        padding="spacing.5"
        display="flex"
        flexDirection="column"
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
          onClick={onNachSubmitClick}
          iconPosition="right"
          variant="primary"
          isFullWidth
          isLoading={isLoading}
          isDisabled={
            isFormDisabled ||
            !(
              !(
                nachForm[NachFormKeyNames.NACH_FORM_COMMENTS_FIELD]?.trim().length < 4 &&
                nachForm[NachFormKeyNames.NACH_FORM_COMMENTS_FIELD]?.trim().length !== 0
              ) && nachForm[NachFormKeyNames.NACH_FORM_DOCUMENT_FIELD]?.length
            )
          }
        >
          Save & Continue
        </Button>
        <Button
          onClick={onNachSkipClick}
          icon={ArrowRightIcon}
          iconPosition="right"
          variant="secondary"
          isFullWidth
          marginTop="spacing.5"
          isDisabled={true}
        >
          Skip & add later
        </Button>
      </Box>
    </Box>
  );
};

export default NACHForm;
