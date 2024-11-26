import React, { useEffect } from 'react';
import {
  Modal,
  ModalHeader,
  ModalBody,
  ModalFooter,
  Box,
  TextInput,
  Button,
  DatePicker,
  DownloadIcon,
  RadioGroup,
  Radio,
} from '@razorpay/blade/components';
import { makeSpace } from '@razorpay/blade/utils';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { useFormik } from 'formik';
import { DownloadReportModalProps } from 'merchant/views/Reconciliations/Dashboard/types';
import moment from 'moment';
import styled from 'styled-components';

import { triggerDownloadReport } from 'merchant/views/Reconciliations/api';
import { downloadFormSchema } from 'merchant/views/Reconciliations/schema';

const DownloadForm = styled.form`
  display: grid;
  gap: ${({ theme }) => makeSpace(theme.spacing[7])};
`;

const DownloadReportModal: React.FC<DownloadReportModalProps> = ({
  downloadReportConfig,
  isOpenDownloadModal,
  setIsOpenDownloadModal,
}) => {
  const queryClient = useQueryClient();

  const reportMutation = useMutation({
    mutationFn: (payload: any) => triggerDownloadReport(payload),
  });

  const formik = useFormik({
    initialValues: {
      fileName: '',
      fileType: '',
      startDate: '',
      endDate: '',
      recipientEmail: '',
    },
    validationSchema: downloadFormSchema,
    onSubmit: (values) => {
      const payload = {
        ...(downloadReportConfig?.id ? { reporting_config_id: downloadReportConfig.id } : {}),
        transaction_date_range: {
          from_date: values.startDate ? `${moment(values.startDate).unix()}` : '',
          to_date: values.endDate ? `${moment(values.endDate).unix()}` : '',
        },
        file_name: values.fileName?.trim(),
        file_format: values.fileType?.toLowerCase(),
        ...(values.recipientEmail ? { emails: [values.recipientEmail] } : {}),
      };

      reportMutation.mutate(payload);
    },
  });

  useEffect(() => {
    const currentPage = 0;
    if (reportMutation.isSuccess) {
      formik.resetForm();
      setIsOpenDownloadModal(false);
      queryClient.invalidateQueries({ queryKey: ['downloadList', currentPage] });
    }
  }, [reportMutation.isSuccess]);

  return (
    <Modal
      isOpen={isOpenDownloadModal}
      onDismiss={() => {
        setIsOpenDownloadModal(false);
        formik.resetForm();
      }}
      size="medium"
    >
      <ModalHeader
        title="Download Report"
        subtitle="Fill the following information."
        leading={
          <Box
            display="flex"
            justifyContent="center"
            alignItems="center"
            borderRadius="medium"
            backgroundColor="surface.background.gray.moderate"
            height="spacing.8"
            width="spacing.8"
          >
            <DownloadIcon size="large" color="surface.icon.gray.subtle" />
          </Box>
        }
      />
      <ModalBody>
        <Box display="grid" gap="spacing.7">
          <DownloadForm id="downloadForm" onSubmit={formik.handleSubmit}>
            <Box
              display="grid"
              gridTemplateColumns="repeat(2, 1fr)"
              gap="spacing.9"
              width="100%"
              alignItems="start"
            >
              <TextInput
                label="File Name"
                placeholder="File Name"
                name="fileName"
                value={formik.values.fileName || ''}
                isRequired={true}
                onChange={({ name, value }) => {
                  if (name) {
                    formik.setFieldValue(name, value);
                  }
                }}
                validationState={
                  formik.touched.fileName && formik.errors.fileName ? 'error' : 'none'
                }
                errorText={formik.errors.fileName}
                necessityIndicator="required"
              />

              <RadioGroup
                label="Select format"
                name="fileType"
                value={formik.values.fileType}
                onChange={({ name, value }) => {
                  if (name) {
                    formik.setFieldValue(name, value);
                  }
                }}
                validationState={
                  formik.touched.fileType && formik.errors.fileType ? 'error' : 'none'
                }
                isRequired={true}
                errorText={formik.errors.fileType}
                necessityIndicator="required"
                size="medium"
              >
                <Box
                  display="flex"
                  gap="spacing.5"
                  height="36px"
                  justifyContent="flex-start"
                  alignItems="center"
                >
                  <Radio value="xlsx">Excel</Radio>
                  <Radio value="csv">CSV</Radio>
                  <Radio value="txt">Text File</Radio>
                </Box>
              </RadioGroup>
            </Box>

            <DatePicker
              // eslint-disable-next-line @typescript-eslint/ban-ts-comment
              // @ts-ignore
              label={{ start: 'Start Date', end: 'End Date' }}
              // eslint-disable-next-line @typescript-eslint/ban-ts-comment
              // @ts-ignore
              selectionType="range"
              value={[formik.values.startDate, formik.values.endDate]}
              onChange={(date) => {
                formik.setFieldValue('startDate', date[0]);
                formik.setFieldValue('endDate', date[1]);
              }}
              validationState={formik.errors.startDate || formik.errors.endDate ? 'error' : 'none'}
              // eslint-disable-next-line @typescript-eslint/ban-ts-comment
              // @ts-ignore
              errorText={{
                start: formik.errors.startDate || '',
                end: formik.errors.endDate || '',
              }}
              necessityIndicator="required"
            />
            <Box
              display="grid"
              gridTemplateColumns="repeat(2, 1fr)"
              gap="spacing.9"
              alignItems="start"
            >
              <TextInput
                type="email"
                label="Add Recipient's Details"
                placeholder="Enter email address of recipients"
                name="recipientEmail"
                value={formik.values.recipientEmail}
                onChange={({ name, value }) => {
                  if (name) {
                    formik.setFieldValue(name, value);
                  }
                }}
                validationState={
                  formik.touched.recipientEmail && formik.errors.recipientEmail ? 'error' : 'none'
                }
                errorText={formik.errors.recipientEmail}
                necessityIndicator="optional"
              />
            </Box>
          </DownloadForm>
        </Box>
      </ModalBody>
      <ModalFooter>
        <Box display="flex" justifyContent="flex-end" gap="spacing.4">
          <Button
            variant="tertiary"
            onClick={(e) => {
              e.preventDefault();
              setIsOpenDownloadModal(false);
              formik.resetForm();
            }}
          >
            Cancel
          </Button>
          <Button
            variant="primary"
            type="submit"
            onClick={() => formik.handleSubmit()}
            isDisabled={reportMutation.isLoading}
            isLoading={reportMutation.isLoading}
          >
            Download
          </Button>
        </Box>
      </ModalFooter>
    </Modal>
  );
};

export default DownloadReportModal;
