import React, { useState } from 'react';
import {
  Button,
  Modal,
  ModalBody,
  ModalHeader,
  ModalFooter,
  Box,
  CheckIcon,
  Text,
  CloseIcon,
} from '@razorpay/blade/components';
import styled from 'styled-components';
import { useQuery } from '@tanstack/react-query';
import MultiFileUpload from './MultiFileUpload';
import { ShowNotificationType } from 'common/typings';
import {
  uploadBankStatement,
  submitBankStatements,
} from 'merchant/views/PartnerDashboard/SubMerchant/api';
import { toBase64 } from './utils';

const PositiveBox = styled.div(({ theme }) => ({
  display: 'flex',
  flexDirection: 'column',
  gap: theme.spacing[5],
  padding: theme.spacing[5],
  backgroundColor: theme.colors.feedback.background.positive.subtle,
  borderLeft: `2px solid ${theme.colors.feedback.border.positive.intense}`,
  margin: '10px 0',
}));

const NegativeBox = styled.div(({ theme }) => ({
  display: 'flex',
  flexDirection: 'column',
  gap: theme.spacing[5],
  padding: theme.spacing[5],
  backgroundColor: theme.colors.feedback.background.negative.subtle,
  borderLeft: `2px solid ${theme.colors.feedback.border.negative.intense}`,
  margin: '10px 0',
}));

type UploadBankStatementProps = {
  closeModal: () => void;
  showNotification: ShowNotificationType;
  uploadData: {
    partnerId: string;
    merchantId: string;
    appId: string;
  };
  isUploadSuccess: () => void;
};

type filesType = {
  store_id: string | undefined;
  name: string | undefined;
  id: string | undefined;
}[];
export const UploadBankStatement = ({
  closeModal,
  showNotification,
  uploadData,
  isUploadSuccess,
}: UploadBankStatementProps): JSX.Element => {
  const [uploadedFiles, setUploadedFiles] = useState<filesType>([]);
  const { appId, partnerId, merchantId } = uploadData;

  // submit all uploaded files
  const { isFetching, refetch: submitStatements } = useQuery({
    queryKey: ['submit-bank-statements'],
    queryFn: () => {
      const submitData = {
        partner_id: partnerId,
        merchant_id: merchantId,
        application_id: appId,
        files: uploadedFiles.map((item) => {
          return {
            id: new Date().getTime().toString(),
            store_id: item.store_id,
            store_type: 'UFH',
            file_type: 'pdf',
            name: item.name,
          };
        }),
      };
      return submitBankStatements(submitData);
    },
    refetchOnWindowFocus: false,
    enabled: false,
    onSuccess: () => {
      isUploadSuccess();
      showNotification?.({
        type: 'success',
        message: [
          'Bank statement uploaded',
          'The bank statements have been successfully uploaded. Our team will take the client further ahead from here.',
        ],
      });
      closeModal();
    },
    onError: (err: { errors: Array<string> }) => {
      showNotification?.({
        type: 'error',
        message: err.errors,
      });
    },
  });

  // upload file
  const handleFileUpload = async (docType: string, file, progressTracker): Promise<any> => {
    const base64 = ((await toBase64(file)) || '').split(',').pop() || '';
    const uploadData = {
      document: base64,
      partner_id: partnerId,
      merchant_id: merchantId,
      document_type: docType,
      display_name: file.name,
      name: `${appId}-${new Date().getTime()}-${file.name}`,
    };

    return uploadBankStatement(uploadData, progressTracker)
      .then((response) => {
        const { data } = response;
        const uploadedFile = {
          store_id: data?.store_id,
          name: file.name,
          id: file.id,
        };
        setUploadedFiles([...uploadedFiles, uploadedFile]);
        showNotification?.({
          type: 'success',
          message: 'File Uploaded Successfully!',
        });
        return data;
      })
      .catch((_err) => {
        showNotification?.({
          type: 'error',
          message: _err.errors,
        });
      });
  };

  const handleFileRemoval = (fileId: string): void => {
    const updatedFiles = uploadedFiles.filter((f) => f.id !== fileId);
    setUploadedFiles(updatedFiles);
  };
  return (
    <Modal isOpen={true} onDismiss={closeModal}>
      <ModalHeader title="Upload bank account statement" />
      <ModalBody>
        <Text>Share the current account statements for the last 6 months for your client</Text>
        <MultiFileUpload
          name="bank_statement"
          label=""
          onFileChange={(file: File, progressTracker: () => void) =>
            handleFileUpload('bank_statement', file, progressTracker)
          }
          onFileRemove={handleFileRemoval}
        />
        <PositiveBox>
          <Text weight="semibold" size="small" color="interactive.text.positive.subtle">
            Do’s
          </Text>
          <Box display="flex" flexDirection="column" gap="spacing.4">
            <Box display="flex" gap="spacing.3" flex="1">
              <Box display="flex" gap="spacing.3">
                <CheckIcon size="medium" color="feedback.icon.positive.intense" />
              </Box>
              <Text>Upload original PDF shared by the bank.</Text>
            </Box>
            <Box display="flex" gap="spacing.3" flex="1">
              <Box display="flex" gap="spacing.3">
                <CheckIcon size="medium" color="feedback.icon.positive.intense" />
              </Box>
              <Text>
                Upload statements of most frequently used account in last 6 months for business
                purpose.
              </Text>
            </Box>
            <Box display="flex" gap="spacing.3" flex="1">
              <Box display="flex" gap="spacing.3">
                <CheckIcon size="medium" color="feedback.icon.positive.intense" />
              </Box>
              <Text>
                Upload either monthly statements or a single statement with all the details.
              </Text>
            </Box>
          </Box>
        </PositiveBox>
        <NegativeBox>
          <Box display="flex" gap="spacing.3" alignItems="center">
            <Text weight="semibold" size="small">
              Dont’s
            </Text>
          </Box>
          <Box display="flex" flexDirection="column" gap="spacing.4" width="264px">
            <Box
              display="flex"
              gap="spacing.3"
              alignItems="center"
              padding={['spacing.0', '35px', 'spacing.0', 'spacing.0']}
              width="264px"
            >
              <CloseIcon size="medium" color="feedback.icon.negative.intense" />
              <Text>Uploaded files should NOT be edited.</Text>
            </Box>
            <Box display="flex" gap="spacing.3" alignItems="center">
              <CloseIcon size="medium" color="feedback.icon.negative.intense" />
              <Text>Should NOT be a scanned document.</Text>
            </Box>
          </Box>
        </NegativeBox>
      </ModalBody>
      <ModalFooter>
        <Box display="flex" gap="spacing.3" justifyContent="flex-end" width="100%">
          <Button
            isDisabled={uploadedFiles.length === 0}
            onClick={() => submitStatements()}
            isLoading={isFetching}
          >
            Submit statements
          </Button>
        </Box>
      </ModalFooter>
    </Modal>
  );
};
