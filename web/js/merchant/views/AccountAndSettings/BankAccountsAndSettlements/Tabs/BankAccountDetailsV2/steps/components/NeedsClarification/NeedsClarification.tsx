import React, { useEffect, useState, useRef } from 'react';
import { Text, TextArea } from '@razorpay/blade/components';
import { connect } from 'react-redux';
import { bindActionCreators, compose } from 'redux';

import FileUpload from 'merchant/components/File/Upload';
import { allowedVideoExtensions } from 'merchant/components/File/constants';
import { fetchWorkflowStatus as fetchWorkflowStatusReducer } from 'merchant/reducers/workflows';
import { merchantFetch } from 'merchant/utils/ajax';
import { WORKFLOW_TYPES } from 'merchant/views/Account/Profile/components/WorkflowRequests/constants';
import { isWorkflowInClarification } from 'merchant/views/Account/Profile/components/WorkflowRequests/utils';
import { MAX_FILE_SIZE_LIMIT } from 'merchant/views/Account/constants';
import BottomActions from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/components/BottomActions';
import { StyledDivider } from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/components/common/styled';
import LoadingStep from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/steps/components/LoadingStep/LoadingStep';
import { LoadingStateMap } from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/steps/utils/stepConfig';
import {
  BANK_ACCOUNT_UPDATE_STEPS,
  NotificationAction,
  StepsInfoPropsInterface,
  WorkflowConfigInterface,
} from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/typings';
import { trackBankAccountUpdateEvent } from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/utils/track';
import { trackIEEvent } from 'merchant/views/AccountAndSettings/PaymentMethods/Tabs/International/components/InternationalCards/utils/track';
import { showNotification as fnShowNotification } from 'merchant_common/reducers/notifications';

import MessagePrompt from './MessagePrompt';
import NcShimmer from './NcShimmer';
import { StyledNeedsClarification, StyledUploadContainer, UploadContainer } from './styled';

const NeedsClarification = ({
  closeModal,
  showNotification,
  setLayoutInfo,
  workflowType,
  workflowName,
  fetchWorkflowStatus,
  workflows,
  isMultiple = false,
  isFileUploadRequried = true,
}: Pick<StepsInfoPropsInterface, 'closeModal' | 'setLayoutInfo'> &
  WorkflowConfigInterface &
  NotificationAction & {
    isMultiple?: boolean;
    isFileUploadRequried?: boolean;
  }): JSX.Element => {
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
  const isTypeClarificationNotesEventCaptured = useRef(false);

  useEffect(() => {
    if (workflowType === WORKFLOW_TYPES.ENABLE_INTERNATIONAL_CARDS_FOR_PG_PPLI) {
      trackIEEvent({
        objectName: 'Needs Clarification Modal',
        actionName: 'Displayed',
      });
    }
  }, [workflowType]);

  useEffect(() => {
    // TODO: will remove this after verification use case
    fetchWorkflowStatus(workflowType)
      .then((response) => {
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
      })
      .catch(() => {
        showNotification({
          type: 'error',
          message: `Something went wrong, Please try again later`,
        });
        closeModal();
      });
  }, [workflowName, workflowType]);

  const handleNoteChange = (event) => {
    const { value } = event;
    setReplyNote((prevState) => ({
      ...prevState,
      note: value,
      isValidate: value.length >= 50,
    }));

    if (
      workflowType === WORKFLOW_TYPES.ENABLE_INTERNATIONAL_CARDS_FOR_PG_PPLI &&
      !isTypeClarificationNotesEventCaptured.current
    ) {
      trackIEEvent({
        objectName: 'Clarification Notes',
        actionName: 'Entered',
      });
      isTypeClarificationNotesEventCaptured.current = true;
    }
  };

  const onFileLimitFailure = (): void => {
    showNotification({
      type: 'error',
      message: `File exceeds total upload limit of ${MAX_FILE_SIZE_LIMIT / 1024 / 1024}MB!`,
    });
  };

  const onFileRemove = (removedFileId) => {
    setDocuments((prevState) => ({
      ...prevState,
      file: [...prevState.file.filter((_doc, index) => index !== removedFileId)],
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
            file: [...prevState.file, { id, display_name }],
          }));

          if (workflowType === WORKFLOW_TYPES.ENABLE_INTERNATIONAL_CARDS_FOR_PG_PPLI) {
            trackIEEvent({
              objectName: 'Documents',
              actionName: 'Uploaded',
            });
          }
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
    if (workflowType === WORKFLOW_TYPES.ENABLE_INTERNATIONAL_CARDS_FOR_PG_PPLI) {
      trackIEEvent({
        objectName: 'NC Modal Submit Details',
        actionName: 'Clicked',
      });
    } else {
      trackBankAccountUpdateEvent({
        objectName: 'Submit Details',
        actionName: 'Clicked',
      });
    }
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
    if (workflowType === WORKFLOW_TYPES.ENABLE_INTERNATIONAL_CARDS_FOR_PG_PPLI) {
      trackIEEvent({
        objectName: 'Submit Details',
        actionName: 'Cancelled',
        properties: {
          ctaSource: 'needs clarification',
        },
      });
    } else {
      trackBankAccountUpdateEvent({
        objectName: 'Bottom Cancel Action',
        actionName: 'Clicked',
        properties: {
          ctaSource: 'needs clarification',
        },
      });
    }
    closeModal();
  };

  const onClickToUploadClick = () => {
    if (workflowType === WORKFLOW_TYPES.ENABLE_INTERNATIONAL_CARDS_FOR_PG_PPLI) {
      trackIEEvent({
        objectName: 'Click To Upload',
        actionName: 'Clicked',
      });
    }
  };

  const onFileDrop = () => {
    if (workflowType === WORKFLOW_TYPES.ENABLE_INTERNATIONAL_CARDS_FOR_PG_PPLI) {
      trackIEEvent({
        objectName: 'File',
        actionName: 'Dragged and Dropped',
      });
    }
  };

  if (isProcessing) {
    return <LoadingStep type={LoadingStateMap[workflowType]} lottieClass={['mb-20']} />;
  }

  const { loading: isWorkflowLoading, needs_clarification: needsClarificationResponse } =
    workflows[workflowType];

  if (isWorkflowLoading) return <NcShimmer />;

  const isSubmitDisabled =
    documents.isUploading ||
    (isFileUploadRequried && !documents.file.length) ||
    !replyNote.note ||
    !replyNote.isValidate;

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
        maxCharacters={800}
        numberOfLines={5}
        onChange={handleNoteChange}
        necessityIndicator="required"
        validationState={replyNote.isValidate ? 'none' : 'error'}
        errorText="Minimum 50 characters required"
      />
      <UploadContainer>
        {isMultiple && (
          <Text size="small" weight="semibold" color="surface.text.gray.muted">
            You can upload multiple documents below
          </Text>
        )}
        <StyledUploadContainer isMulti={isMultiple}>
          <FileUpload
            multi={isMultiple}
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
            onClickToUploadClick={onClickToUploadClick}
            onFileDrop={onFileDrop}
          />
        </StyledUploadContainer>
      </UploadContainer>
      <BottomActions
        onClose={handleClose}
        onSubmit={handleSubmitReply}
        isSubmitDisabled={isSubmitDisabled}
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
