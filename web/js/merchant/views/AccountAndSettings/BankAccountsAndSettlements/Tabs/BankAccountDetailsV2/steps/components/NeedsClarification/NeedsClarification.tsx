import { TextArea } from '@razorpay/blade/components';
import { allowedVideoExtensions } from 'merchant/components/File/constants';
import FileUpload from 'merchant/components/File/Upload';
import { fetchWorkflowStatus as fetchWorkflowStatusReducer } from 'merchant/reducers/workflows';
import { merchantFetch } from 'merchant/utils/ajax';
import { MAX_FILE_SIZE_LIMIT } from 'merchant/views/Account/constants';
import { WORKFLOW_TYPES } from 'merchant/views/Account/Profile/components/WorkflowRequests/constants';
import { isWorkflowInClarification } from 'merchant/views/Account/Profile/components/WorkflowRequests/utils';
import BottomActions from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/components/BottomActions';
import { StyledDivider } from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/components/common/styled';
import LoadingStep from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/steps/components/LoadingStep/LoadingStep';
import {
  BANK_ACCOUNT_UPDATE_STEPS,
  LOADING_STATE,
  NotificationAction,
  StepsInfoPropsInterface,
  WorkflowConfigInterface,
} from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/typings';
import { showNotification as fnShowNotification } from 'merchant_common/reducers/notifications';
import React, { useEffect, useState } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators, compose } from 'redux';
import MessagePrompt from './MessagePrompt';
import NcShimmer from './NcShimmer';
import { StyledNeedsClarification, StyledUploadContainer } from './styled';
import { trackBankAccountUpdateEvent } from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/utils/track';

const NeedsClarification = ({
  closeModal,
  showNotification,
  setLayoutInfo,
  workflowType = WORKFLOW_TYPES.BANK_DETAIL_UPDATE,
  workflowName = 'Change your bank account',
  fetchWorkflowStatus,
  workflows,
}: Pick<StepsInfoPropsInterface, 'closeModal' | 'setLayoutInfo'> &
  WorkflowConfigInterface &
  NotificationAction): JSX.Element => {
  const [replyNote, setReplyNote] = useState<{ note: string; isValidate: boolean }>({
    note: '',
    isValidate: true,
  });
  const [documents, setDocuments] = useState<{
    isUploading: boolean;
    file: Array<{ id: string; display_name: string }>;
  }>({
    isUploading: false,
    file: [],
  });
  const [isProcessing, setIsProcessing] = useState(false);

  useEffect(() => {
    // TODO: will remove this after verification use case
    fetchWorkflowStatus(workflowType).then((response) => {
      const { data: workflow } = response;
      if (!isWorkflowInClarification(workflow, ['open', 'approved'])) {
        showNotification({
          type: 'error',
          message: `No clarification required for ${workflowName} workflow`,
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
  }, [workflowName, workflowType]);

  const handleNoteChange = (event) => {
    const { value } = event;
    setReplyNote((prevState) => ({
      ...prevState,
      note: value,
      isValidate: value.length >= 50,
    }));
  };

  const onFileLimitFailure = (): void => {
    showNotification({
      type: 'error',
      message: `File exceeds total upload limit of ${MAX_FILE_SIZE_LIMIT / 1024 / 1024}MB!`,
    });
  };

  const onFileRemove = () => {
    setDocuments((prevState) => ({
      ...prevState,
      file: [],
    }));
  };

  const handleDocumentUpload = (file) => {
    const formData = new FormData();
    formData.append('purpose', 'merchant_workflow_clarification');
    formData.append('file', file);
    setDocuments((prevState) => ({
      ...prevState,
      isUploading: true,
    }));
    merchantFetch({
      url: 'documents',
      method: 'post',
      data: formData,
    })
      .then((response) => {
        // istanbul ignore else
        if (response?.success && response?.data) {
          const { id, display_name } = response.data;
          setDocuments((prevState) => ({
            ...prevState,
            file: [{ id, display_name }],
          }));
        }
      })
      .catch((err) => {
        showNotification({
          type: 'error',
          message: err.errors,
        });
      })
      .finally(() => {
        setDocuments((prevState) => ({
          ...prevState,
          isUploading: false,
        }));
      });
  };

  const handleSubmitReply = () => {
    trackBankAccountUpdateEvent({
      objectName: 'Submit Details',
      actionName: 'Clicked',
    });
    setIsProcessing(true);
    setLayoutInfo(BANK_ACCOUNT_UPDATE_STEPS.LOADING_VIEW);
    const body = {
      merchant_workflow_clarification: replyNote.note,
      clarification_documents_ids: documents.file.map((doc) => doc.id),
    };
    merchantFetch({
      url: `merchant/submit_clarification/${workflowType}`,
      method: 'post',
      mode: 'live',
      data: body,
    })
      .then((response) => {
        // istanbul ignore else
        if (response?.success) {
          fetchWorkflowStatus(workflowType);
          closeModal();
        }
      })
      .catch((err) => {
        showNotification({
          type: 'error',
          message: err.errors,
        });
      })
      .finally(() => {
        setIsProcessing(false);
        setLayoutInfo('');
      });
  };

  const handleClose = () => {
    trackBankAccountUpdateEvent({
      objectName: 'Bottom Cancel Action',
      actionName: 'Clicked',
      properties: {
        ctaSource: 'needs clarification',
      },
    });
    closeModal();
  };

  if (isProcessing) {
    return <LoadingStep type={LOADING_STATE.UPLOAD_NC_BANK_DETAIL} lottieClass={['mb-20']} />;
  }

  const { loading: isWorkflowLoading, needs_clarification: needsClarificationResponse } = workflows[
    workflowType
  ];

  if (isWorkflowLoading) return <NcShimmer />;

  return (
    <StyledNeedsClarification>
      {needsClarificationResponse && (
        <>
          <MessagePrompt title="From Razorpay support" messageBody={needsClarificationResponse} />
          <StyledDivider />
        </>
      )}
      <TextArea
        label="Note"
        placeholder="Add a note"
        value={replyNote.note}
        name="note"
        maxCharacters={232}
        numberOfLines={5}
        onChange={handleNoteChange}
        necessityIndicator="required"
        validationState={replyNote.isValidate ? 'none' : 'error'}
        errorText="Minimum 50 characters required"
      />
      <StyledUploadContainer>
        <FileUpload
          name="needs-clarification-bank-proof"
          maxSize={MAX_FILE_SIZE_LIMIT}
          hideLoader
          uploadSubtitle="(Under 50 MB only)"
          showOnlyFileSize
          hideMaxSize
          showFileSize={false}
          accept={['jpg', 'png', 'pdf', ...allowedVideoExtensions]}
          showAcceptInfo={false}
          onBiggerFileSize={onFileLimitFailure}
          onFileChange={handleDocumentUpload}
          onCloseClick={onFileRemove}
        />
      </StyledUploadContainer>
      <BottomActions
        onClose={handleClose}
        onSubmit={handleSubmitReply}
        isSubmitDisabled={
          documents.isUploading ||
          !documents.file.length ||
          !replyNote.note ||
          !replyNote.isValidate
        }
        submitCTALabel="Submit details"
      />
    </StyledNeedsClarification>
  );
};

const mapStateToProps = (state) => ({
  workflows: state.workflows,
});

const mapDispatchToProps = (dispatch) => {
  return bindActionCreators(
    {
      showNotification: fnShowNotification,
      fetchWorkflowStatus: fetchWorkflowStatusReducer,
    },
    dispatch,
  );
};

export default compose(connect(mapStateToProps, mapDispatchToProps))(NeedsClarification);
