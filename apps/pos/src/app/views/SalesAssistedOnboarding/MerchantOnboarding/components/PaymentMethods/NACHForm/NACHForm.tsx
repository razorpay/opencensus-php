import React from 'react';
import {
  ArrowRightIcon,
  Box,
  Button,
  Heading,
  TextArea,
  useToast,
} from '@razorpay/blade/components';
import SalesFileUpload from 'apps/pos/src/app/components/SalesFileUpload';

export enum NachFormKeyNames {
  NACH_FORM_DOCUMENT_FIELD = 'nach_form_document_field',
  NACH_FORM_COMMENTS_FIELD = 'nach_form_comments_field',
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
}

const NACHForm: React.FC<NachFormProps> = ({
  nachForm,
  onNachTextAreaChange,
  onNachFileUploadChange,
  onNachSubmitClick,
  onNachSkipClick,
}) => {
  const toast = useToast();
  return (
    <Box padding="spacing.5">
      <Heading marginBottom="spacing.5" size="large">
        Upload NACH Form
      </Heading>
      <SalesFileUpload
        name={NachFormKeyNames.NACH_FORM_DOCUMENT_FIELD}
        label="To debit the rental charges from Merchant’s account automatically"
        accept=".pdf"
        uploadType="single"
        onChange={onNachFileUploadChange}
        maxSize={5 * 1024 * 1023}
        maxLimit={1}
        isLoading={false}
        defaultValue={nachForm[NachFormKeyNames.NACH_FORM_DOCUMENT_FIELD]}
        onError={() =>
          toast.show({
            content: `Unable to upload file. Please try again`,
            color: 'negative',
          })
        }
        isDisabled={false}
      />
      <Box>
        <Heading marginBottom="spacing.5" size="large">
          Additional Sales Comment
        </Heading>
        <TextArea
          onChange={onNachTextAreaChange}
          placeholder="Add comments here for sales team"
          label=""
          size="large"
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
          isDisabled={
            !(
              nachForm[NachFormKeyNames.NACH_FORM_COMMENTS_FIELD] &&
              nachForm[NachFormKeyNames.NACH_FORM_DOCUMENT_FIELD]?.length
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
        >
          Skip & add later
        </Button>
      </Box>
    </Box>
  );
};

export default NACHForm;
