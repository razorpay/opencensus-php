import React, { useState, useEffect } from 'react';
import {
  Modal as BladeModal,
  ModalBody as BladeModalBody,
  ModalHeader as BladeModalHeader,
  ModalFooter as BladeModalFooter,
  Box,
  Button,
  Alert,
  Text,
  TextArea,
  FileUpload,
  BladeFileList,
} from '@razorpay/blade/components';
import { connect } from 'react-redux';
import { compose } from 'redux';

import { DASHBOARD_ZINDEX_MAP } from '@libs/shared-utils';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { fetchWorkflowStatus as fetchWorkflowStatusReducer } from 'merchant/reducers/workflows';
import { merchantFetch } from 'merchant/utils/ajax';
import { isWorkflowInClarification } from 'merchant/views/Account/Profile/components/WorkflowRequests/utils';
import { MAX_FILE_SIZE_LIMIT } from 'merchant/views/Account/constants';
import { closeModal as fnCloseModal } from 'merchant_common/reducers/modals';
import { showNotification as fnShowNotification } from 'merchant_common/reducers/notifications';

const NeedsClarificationModal = ({
  workflowType,
  workflowName,
  onResponseSubmit,
  refetch = true,
  workflows,
  showNotification,
  fetchWorkflowStatus,
  closeModal,
  org,
}) => {
  const [response, setResponse] = useState('');
  const [isResponseValid, setIsResponseValid] = useState(false);
  const [responseValidationState, setResponseValidationState] = useState<'none' | 'error'>('none');
  const [isModalOpen, setIsModalOpen] = useState(true);
  const [documents, setDocuments] = useState<
    {
      id: number;
      display_name: string;
    }[]
  >([]);
  const [selectedFiles, setSelectedFiles] = useState<BladeFileList>([]);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [isUploadingDocument, setIsUploadingDocument] = useState(false);
  const MAX_FILES = 10;

  const handleOnDismiss = () => {
    closeModal();
    setIsModalOpen(false);
  };

  const handleFileUpload = (file, progressTracker = () => {}) => {
    if (documents.length >= MAX_FILES) {
      showNotification({
        type: 'error',
        message: `Maximum ${MAX_FILES} are allowed`,
      });
    }
    const formData = new FormData();
    formData.append('purpose', 'merchant_workflow_clarification');
    formData.append('file', file);
    setIsUploadingDocument(true);
    return merchantFetch({
      url: 'documents',
      method: 'post',
      data: formData,
      onUploadProgress: progressTracker,
    })
      .then((res) => {
        if (res.success && res.data) {
          analyticsTrack({
            objectName: 'needs clarification fileupload',
            actionName: 'result',
            screen: 'my account',
            properties: {
              isBladeRevamp: true,
              flowName: workflowType,
              result: 'Success',
              ...getCommonAnalyticsProperties(window.rzp_user),
            },
          });
          const doc = {
            id: res.data.id,
            display_name: res.data.display_name,
          };
          setDocuments((state) => [...state, doc]);
        }
      })
      .catch((err) => {
        analyticsTrack({
          objectName: 'needs clarification fileupload',
          actionName: 'result',
          screen: 'my account',
          properties: {
            isBladeRevamp: true,
            flowName: workflowType,
            result: 'Failure',
            failureMessage: err.errors && err.errors.length ? `${err.errors[0]}` : null,
            ...getCommonAnalyticsProperties(window.rzp_user),
          },
        });
        showNotification({
          type: 'error',
          message: err.errors,
        });
      })
      .finally(() => setIsUploadingDocument(false));
  };

  const handleSubmit = () => {
    analyticsTrack({
      objectName: 'needs clarification submit',
      actionName: 'clicked',
      screen: 'my account',
      properties: {
        isBladeRevamp: true,
        flowName: workflowType,
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });
    setIsSubmitting(true);
    const body = {
      merchant_workflow_clarification: response,
      clarification_documents_ids: documents.map((doc) => doc.id),
    };
    return merchantFetch({
      url: `merchant/submit_clarification/${workflowType}`,
      method: 'post',
      mode: 'live',
      data: body,
    })
      .then((res) => {
        if (res.success) {
          analyticsTrack({
            objectName: 'needs clarification submit',
            actionName: 'result',
            screen: 'my account',
            properties: {
              isBladeRevamp: true,
              flowName: workflowType,
              result: 'Success',
              ...getCommonAnalyticsProperties(window.rzp_user),
            },
          });
          fetchWorkflowStatus(workflowType);
          onResponseSubmit?.();
          handleOnDismiss();
        }
      })
      .catch((err) => {
        analyticsTrack({
          objectName: 'needs clarification submit',
          actionName: 'result',
          screen: 'my account',
          properties: {
            isBladeRevamp: true,
            flowName: workflowType,
            result: 'Failure',
            failureMessage: err.errors && err.errors.length ? `${err.errors[0]}` : null,
            ...getCommonAnalyticsProperties(window.rzp_user),
          },
        });
        showNotification({
          type: 'error',
          message: err.errors,
        });
      })
      .finally(() => setIsSubmitting(false));
  };

  const handleInputChange = ({ value = '' }) => {
    const isValid = value.length >= 50;
    setResponse(value);
    setIsResponseValid(isValid);
    setResponseValidationState(isValid ? 'none' : 'error');
  };

  const handleFileChange = async ({ fileList }) => {
    if (fileList.length > 0) {
      setSelectedFiles(fileList);
      // API call needs to be sequential one after the other and not parallel otherwise it will fail, that's why we are using for loop here
      for (const file of fileList) {
        // eslint-disable-next-line no-await-in-loop
        await handleFileUpload(file);
      }
    }
  };

  const handleFileRemove = ({ file }) => {
    const fileToRemoveIndex = selectedFiles.findIndex((f) => f.id === file.id);
    setDocuments((_files) => {
      const updatedFiles = [..._files];
      updatedFiles.splice(fileToRemoveIndex, 1);
      return updatedFiles;
    });
  };

  useEffect(() => {
    analyticsTrack({
      objectName: 'needs clarification popup',
      actionName: 'rendered',
      screen: 'my account',
      properties: {
        isBladeRevamp: true,
        flowName: workflowType,
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });
    if (!refetch) return;
    fetchWorkflowStatus(workflowType).then((res) => {
      const workflow = res.data;
      if (!isWorkflowInClarification(workflow, ['open', 'approved'])) {
        showNotification({
          type: 'error',
          message: `No clarification required for ${workflowName} worfklow`,
        });
        handleOnDismiss();
      } else if (workflow?.tags?.includes('customer-responded')) {
        showNotification({
          type: 'error',
          message: `You've already responded to ${workflowName} workflow`,
        });
        handleOnDismiss();
      }
    });
  }, []);

  const isSubmitDisabled =
    workflows[workflowType].loading || isSubmitting || !isResponseValid || isUploadingDocument;
  const needsClarificationMessage = workflows[workflowType].needs_clarification;

  return (
    <BladeModal
      isOpen={isModalOpen}
      onDismiss={handleOnDismiss}
      size="small"
      zIndex={DASHBOARD_ZINDEX_MAP.modalOverlay}
    >
      <BladeModalHeader title="Need Clarification" />
      <BladeModalBody>
        <Box display="flex" flexDirection="column" gap="spacing.7">
          <Alert
            title={`From ${org.business_name} support:`}
            description={
              <Text color="surface.text.gray.subtle" wordBreak="break-word">
                {needsClarificationMessage}
              </Text>
            }
            color="negative"
            isDismissible={false}
            isFullWidth
          />
          <TextArea
            label="Add your reply below"
            placeholder="Enter Description"
            value={response}
            onChange={handleInputChange}
            validationState={responseValidationState}
            errorText="Minimum 50 characters required"
            necessityIndicator="required"
            isRequired={true}
          />
          <FileUpload
            label=""
            uploadType="multiple"
            helpText="Max size 50 MB."
            maxSize={MAX_FILE_SIZE_LIMIT}
            maxCount={MAX_FILES}
            onChange={handleFileChange}
            onDrop={handleFileChange}
            onRemove={handleFileRemove}
          />
        </Box>
      </BladeModalBody>
      <BladeModalFooter>
        <Box display="flex" gap="spacing.3" justifyContent="flex-end" width="100%">
          <Button variant="secondary" onClick={handleOnDismiss}>
            Cancel
          </Button>
          <Button
            onClick={handleSubmit}
            isDisabled={isSubmitDisabled}
            isLoading={isSubmitting || isUploadingDocument}
          >
            Continue
          </Button>
        </Box>
      </BladeModalFooter>
    </BladeModal>
  );
};

export default compose(
  connect((state) => ({ workflows: state.workflows, org: state.session.org }), {
    showNotification: fnShowNotification,
    closeModal: fnCloseModal,
    fetchWorkflowStatus: fetchWorkflowStatusReducer,
  }),
)(NeedsClarificationModal);
