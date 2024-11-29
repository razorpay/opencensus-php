import {
  ArrowRightIcon,
  Box,
  Button,
  Heading,
  TextArea,
  useToast,
} from '@razorpay/blade/components';
import SalesFileUpload from 'apps/pos/src/app/components/SalesFileUpload';
import { FileItem } from 'apps/pos/src/app/types/fileUpload';
import React from 'react';
import { useParams } from 'react-router-dom';

export enum NachFormKeyNames {
  NACH_FORM_DOCUMENT_FIELD = 'nach_form_document_field',
  NACH_FORM_COMMENTS_FIELD = 'nach_form_comments_field',
  NACH_DOCUMENT_NAME = 'nach',
}

export type NachFormObject = {
  [NachFormKeyNames.NACH_FORM_DOCUMENT_FIELD]: any;
  [NachFormKeyNames.NACH_FORM_COMMENTS_FIELD]: string;
};

export interface NachFormProps {
  nachForm: NachFormObject;
  onNachTextAreaChange: (event: any) => void;
  onNachFileUploadChange: (files: FileItem[]) => void;
  onNachSubmitClick: () => void;
  onNachSkipClick: () => void;
  isFormDisabled: boolean;
  isModularLoading: boolean;
  isUpdateModularLoading: boolean;
  isNACHMandatory: boolean;
}

const NACHFormEkyc: React.FC<NachFormProps> = ({
  nachForm,
  onNachTextAreaChange,
  onNachFileUploadChange,
  onNachSubmitClick,
  onNachSkipClick,
  isFormDisabled,
  isModularLoading,
  isUpdateModularLoading,
  isNACHMandatory,
}) => {
  const { id } = useParams();
  const toast = useToast();

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
        isLoading={false}
        defaultValue={nachForm[NachFormKeyNames.NACH_FORM_DOCUMENT_FIELD]}
        onError={() =>
          toast.show({
            content: `Unable to upload file. Please try again`,
            color: 'negative',
          })
        }
        isDisabled={isFormDisabled}
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
          isLoading={isUpdateModularLoading}
          isDisabled={
            isFormDisabled ||
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
          isDisabled={isNACHMandatory}
        >
          Skip & add later
        </Button>
      </Box>
    </Box>
  );
};

export default NACHFormEkyc;
