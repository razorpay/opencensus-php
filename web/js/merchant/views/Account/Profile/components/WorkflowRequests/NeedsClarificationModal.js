import React, { useState, useEffect } from 'react';
import Input from 'common/new-ui/Input';
import Spinner from 'common/ui/Spinner';
import ModalHeader from 'common/ui/ModalHeader';
import FileUpload from 'merchant/components/File/Upload';
import { connect } from 'react-redux';
import { compose } from 'redux';
import { closeModal as fnCloseModal } from 'merchant_common/reducers/modals';
import { showNotification as fnShowNotification } from 'merchant_common/reducers/notifications';
import { merchantFetch } from 'merchant/utils/ajax';
import { workflowNames } from 'merchant/views/Account/Profile/components/WorkflowRequests/constants';
import { fetchWorkflowStatus as fetchWorkflowStatusReducer } from 'merchant/reducers/workflows';

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
  onResponseSubmit,
  workflows,
  closeModal,
  showNotification,
  fetchWorkflowStatus,
}) => {
  const [response, setResponse] = useState();
  const [isResponseValid, setIsResponseValid] = useState(false);
  const [documents, setDocuments] = useState([]);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const MAX_SIZE_LIMIT = 5242880; // 5 MB
  const MAX_FILES = 10;

  const onChange = (e) => {
    const input = e.target.value;
    const tokens = input.split(' ');

    if (tokens.length >= 50) {
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
          const doc = {
            id: res.data.id,
            display_name: res.data.display_name,
          };
          setDocuments((state) => [...state, doc]);
        }
      })
      .catch((err) => {
        showNotification({
          type: 'error',
          message: err.errors,
        });
      });
  };

  const onBiggerFileSize = () => {
    showNotification({
      type: 'error',
      message: `File exceeds total upload limit of ${MAX_SIZE_LIMIT / 1024 / 1024}MB!`,
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
    setIsSubmitting(true);
    const body = {
      merchant_workflow_clarification: response,
      clarification_documents_ids: documents.map((doc) => doc.id),
    };
    return merchantFetch({
      url: `merchant/${workflowType}/clarification`,
      method: 'post',
      mode: 'live',
      data: body,
    })
      .then((res) => {
        if (res.success) {
          fetchWorkflowStatus(workflowType);
          if (onResponseSubmit) {
            onResponseSubmit();
          }
          closeModal();
        }
      })
      .catch((err) => {
        setIsSubmitting(false);
        showNotification({
          type: 'error',
          message: err.errors,
        });
      });
  };

  useEffect(() => {
    fetchWorkflowStatus(workflowType);
  }, []);

  return (
    <div>
      <ModalHeader title={workflowNames[workflowType]} onCloseClick={closeModal} />
      <div class="modal-body needs-clarification-form">
        {workflows[workflowType].loading && (
          <center>
            <Spinner />
          </center>
        )}
        {workflows[workflowType].needs_clarification && (
          <>
            <p>
              <strong>Needs Clarification on: </strong>
            </p>
            <p class="text-muted">{workflows[workflowType].needs_clarification}</p>
          </>
        )}

        <Input.Textarea
          name="response-text"
          class="Input--vTop Input--space"
          label="Add your reply below"
          placeholder="Enter here"
          onChange={onChange}
          required
        />
        {isResponseValid === false && <p class="notify-error">Minimum 50 words required</p>}
        <FileUpload
          name="response-files"
          size="small"
          maxSize={MAX_SIZE_LIMIT}
          showFileSize={false}
          showAcceptInfo={false}
          onCloseClick={removeFile}
          onFileChange={handleFileUpload}
          onBiggerFileSize={onBiggerFileSize}
          multi
          showCloseBtn
        />
        {/* Add AsyncBtn here */}
        <button
          type="submit"
          class="btn btn-primary btn-block"
          onClick={onSubmit}
          disabled={workflows[workflowType].loading || isSubmitting || !isResponseValid}
        >
          Submit Clarification
        </button>
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
