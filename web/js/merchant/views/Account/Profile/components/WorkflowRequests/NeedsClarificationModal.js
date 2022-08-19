import React, { useState, useEffect } from 'react';
import Spinner from 'common/ui/Spinner';
import ModalHeader from 'common/ui/ModalHeader';
import { connect } from 'react-redux';
import { compose } from 'redux';
import { closeModal as fnCloseModal } from 'merchant_common/reducers/modals';
import { showNotification as fnShowNotification } from 'merchant_common/reducers/notifications';
import { merchantFetch } from 'merchant/utils/ajax';
import { fetchWorkflowStatus as fetchWorkflowStatusReducer } from 'merchant/reducers/workflows';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties, classList } from 'common/utils/rzp-utils';
import { isWorkflowInClarification } from 'merchant/views/Account/Profile/components/WorkflowRequests/utils';
import { MAX_FILE_SIZE_LIMIT } from 'merchant/views/Account/constants';
import { WORKFLOW_TYPES } from 'merchant/views/Account/Profile/components/WorkflowRequests/constants';
import NeedsClarificationModalContent from './components/NeedsClarificationModalContent';

/**
 * Needs Clarification Modal for merchant to respond to queries
 * from agents for self-serve items
 *
 * @param {*} {
 *   workflowType,
 *   onResponseSubmit,
 * }
 * @return {*} `React.Component`
 */
const NeedsClarificationModal = ({
  workflowType,
  workflowName,
  onResponseSubmit,
  refetch = true,
  workflows,
  closeModal,
  showNotification,
  fetchWorkflowStatus,
}) => {
  const [response, setResponse] = useState();
  const [isResponseValid, setIsResponseValid] = useState(false);
  const [documents, setDocuments] = useState([]);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [isClarificationSubmitted, setIsClarificationSubmitted] = useState(false);
  const MAX_FILES = 10;
  const isBankAccountUpdateWorkflow = workflowType === WORKFLOW_TYPES.BANK_DETAIL_UPDATE;

  const onChange = (e) => {
    const inputLength = e.target.value.length;

    if (inputLength >= 50) {
      setIsResponseValid(true);
      setResponse(e.target.value);
    } else setIsResponseValid(false);
  };

  const handleFileUpload = (file, progressTracker) => {
    if (documents.length >= MAX_FILES) {
      showNotification({
        type: 'error',
        message: `Maximum ${MAX_FILES} are allowed`,
      });
    }
    const formData = new FormData();
    formData.append('purpose', 'merchant_workflow_clarification');
    formData.append('file', file);

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
      });
  };

  const onBiggerFileSize = () => {
    showNotification({
      type: 'error',
      message: `File exceeds total upload limit of ${MAX_FILE_SIZE_LIMIT / 1024 / 1024}MB!`,
    });
  };

  const removeFile = (index) => {
    setDocuments((_files) => {
      const updatedFiles = [..._files];
      updatedFiles.splice(index, 1);
      return updatedFiles;
    });
  };

  const onSubmit = () => {
    analyticsTrack({
      objectName: 'needs clarification submit',
      actionName: 'clicked',
      screen: 'my account',
      properties: {
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
              flowName: workflowType,
              result: 'Success',
              ...getCommonAnalyticsProperties(window.rzp_user),
            },
          });
          fetchWorkflowStatus(workflowType);
          onResponseSubmit?.();
          setIsClarificationSubmitted(true);
          !isBankAccountUpdateWorkflow && closeModal();
        }
      })
      .catch((err) => {
        analyticsTrack({
          objectName: 'needs clarification submit',
          actionName: 'result',
          screen: 'my account',
          properties: {
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

  useEffect(() => {
    analyticsTrack({
      objectName: 'needs clarification popup',
      actionName: 'rendered',
      screen: 'my account',
      properties: {
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
        closeModal();
      } else if (workflow?.tags?.includes('customer-responded')) {
        showNotification({
          type: 'error',
          message: `You've already responded to ${workflowName} workflow`,
        });
        closeModal();
      }
    });
  }, []);

  return (
    <div className={classList(isBankAccountUpdateWorkflow && 'bank-details-change')}>
      <ModalHeader title={workflowName} onCloseClick={closeModal} />
      <div className="modal-body needs-clarification-form">
        <div className={classList(isBankAccountUpdateWorkflow && 'bank-details-change-content')}>
          {workflows[workflowType].loading && (
            <center>
              <Spinner />
            </center>
          )}
          <NeedsClarificationModalContent
            workflowType={workflowType}
            workflowName={workflowName}
            workflows={workflows}
            closeModal={closeModal}
            isSubmitting={isSubmitting}
            isResponseValid={isResponseValid}
            isClarificationSubmitted={isClarificationSubmitted}
            onChange={onChange}
            onSubmit={onSubmit}
            removeFile={removeFile}
            handleFileUpload={handleFileUpload}
            onBiggerFileSize={onBiggerFileSize}
            isBankAccountUpdateWorkflow={isBankAccountUpdateWorkflow}
          />
        </div>
      </div>
    </div>
  );
};

export default compose(
  connect((state) => ({ workflows: state.workflows }), {
    showNotification: fnShowNotification,
    closeModal: fnCloseModal,
    fetchWorkflowStatus: fetchWorkflowStatusReducer,
  }),
)(NeedsClarificationModal);
