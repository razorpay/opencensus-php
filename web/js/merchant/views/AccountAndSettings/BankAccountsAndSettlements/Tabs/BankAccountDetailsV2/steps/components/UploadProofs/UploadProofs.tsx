import { Text } from '@razorpay/blade/components';
import { fetchWorkflowStatus as fetchWorkflowStatusReducer } from 'merchant/reducers/workflows';
import { merchantFetch } from 'merchant/utils/ajax';
import { WORKFLOW_TYPES } from 'merchant/views/Account/Profile/components/WorkflowRequests/constants';
import { showWorkflowStatus } from 'merchant/views/Account/Profile/components/WorkflowRequests/utils';
import { MAX_FILE_SIZE_LIMIT } from 'merchant/views/Account/constants';
import BottomActions from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/components/BottomActions';
import { StyledDivider } from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/components/common/styled';
import { getBankAccountBannerContent } from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/steps/components/Form/utils';
import LoadingStep from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/steps/components/LoadingStep/LoadingStep';
import {
  BANK_ACCOUNT_UPDATE_STEPS,
  FILE_CHANGE_ACTION,
  FileChangeArgs,
  LOADING_STATE,
  UploadProofsPropsInterface,
} from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/typings';
import { trackBankAccountUpdateEvent } from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/utils/track';
import * as ModalActions from 'merchant_common/reducers/modals';
import * as NotificationsActions from 'merchant_common/reducers/notifications';
import React, { useEffect, useState } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators, compose } from 'redux';
import { TabData } from './data';
import {
  StyledAlert,
  StyledStepContainer,
  StyledSubHeader,
  StyledUploadContainer,
  TabContent,
  TabsHeader,
  TabsHeaderItem,
  UploadSectionTab,
} from './styled';

const initialState = {
  [LOADING_STATE.UPLOAD_CANCELLED_CHEQUE_DETAIL]: [],
  [LOADING_STATE.UPLOAD_VERIFICATION_LETTER_DETAIL]: [],
};

const UploadProofs = ({
  isMobile,
  showNotification,
  closeModal,
  user,
  setLayoutInfo,
  fetchWorkflowStatus,
}: UploadProofsPropsInterface): JSX.Element | null => {
  const [activeTab, setActiveTab] = useState<number>(0);
  const [isProcessing, setIsProcessing] = useState<boolean>(false);
  const [isFileUploaded, setIsFileUploaded] = useState<boolean>(false);
  const [proofs, setProofs] =
    useState<
      Record<
        | LOADING_STATE.UPLOAD_CANCELLED_CHEQUE_DETAIL
        | LOADING_STATE.UPLOAD_VERIFICATION_LETTER_DETAIL,
        any[]
      >
    >(initialState);
  const Component = TabData[activeTab].component;

  const onFileLimitFailure = (): void => {
    showNotification({
      type: 'error',
      message: `File exceeds total upload limit of ${MAX_FILE_SIZE_LIMIT / 1024 / 1024}MB!`,
    });
  };

  const handleFileChange = ({ id, type, file }: FileChangeArgs): void => {
    setProofs(() => ({
      ...initialState,
      [id]: type === FILE_CHANGE_ACTION.ADD ? [file] : [],
    }));
  };

  useEffect(() => {
    if (isFileUploaded) {
      closeModal();
    }
  }, [isFileUploaded]);

  const handleClose = (): void => {
    trackBankAccountUpdateEvent({
      objectName: 'Bottom Cancel Action',
      actionName: 'Clicked',
      properties: {
        ctaSource: 'submit proof',
      },
    });
    closeModal();
  };

  const handleSubmit = (): void => {
    trackBankAccountUpdateEvent({
      objectName: 'Bank Account Number Verification',
      actionName: 'Requested',
    });
    const formData = new FormData();
    const proofType = Object.keys(proofs).find((each) => proofs[each].length);
    if (proofType) {
      setIsProcessing(true);
      setLayoutInfo(BANK_ACCOUNT_UPDATE_STEPS.LOADING_VIEW);
      formData.append('address_proof_url', proofs[proofType][0]);
      merchantFetch({
        url: 'merchants/bank_account/file/upload',
        method: 'post',
        data: formData,
      })
        .then(() => {
          setIsFileUploaded(true);
          showNotification({
            type: 'success',
            message: 'Proofs uploaded successfully',
          });
          fetchWorkflowStatus(WORKFLOW_TYPES.BANK_DETAIL_UPDATE);
          showWorkflowStatus(user.id);
        })
        .catch(({ errors }) => {
          showNotification({
            type: 'error',
            message: errors || 'Something went wrong!',
          });
        })
        .finally(() => {
          setIsProcessing(false);
          setLayoutInfo('');
        });
    } else {
      showNotification({
        type: 'error',
        message: `Please add any one document`,
      });
    }
  };

  if (isProcessing) {
    const type = Object.keys(proofs).find((each) => proofs[each].length);
    return type ? <LoadingStep type={LOADING_STATE[type]} lottieClass={['mb-20']} /> : null;
  }

  return (
    <StyledStepContainer>
      {isMobile ? (
        <>
          <StyledSubHeader>
            <Text color="surface.text.gray.subtle">
              As your bank account couldn’t be automatically verified, choose an additional proof
              from below for our team to verify in 2-3 days
            </Text>
            <StyledAlert
              emphasis="subtle"
              color="notice"
              isDismissible={false}
              description={getBankAccountBannerContent(user)}
            />
          </StyledSubHeader>
          <StyledDivider />
        </>
      ) : (
        <StyledAlert
          emphasis="subtle"
          color="notice"
          isDismissible={false}
          description={getBankAccountBannerContent(user)}
          isFullWidth
        />
      )}
      <StyledUploadContainer>
        {!isMobile && (
          <Text color="surface.text.gray.subtle">
            As your bank account couldn’t be automatically verified, choose an additional proof from
            below for our team to verify in 2-3 days
          </Text>
        )}
        <UploadSectionTab>
          <TabsHeader>
            {TabData.map((each, index) => (
              <TabsHeaderItem
                key={`tab-${index}`}
                isActive={index === activeTab}
                onClick={() => setActiveTab(index)}
              >
                <Text size="large">{each.title}</Text>
              </TabsHeaderItem>
            ))}
          </TabsHeader>
          <TabContent>
            <Component
              key={activeTab}
              activeTab={TabData[activeTab].id}
              extras={TabData[activeTab].extras}
              onFileLimitFailure={onFileLimitFailure}
              handleFileChange={handleFileChange}
              files={proofs[TabData[activeTab].id]}
            />
          </TabContent>
        </UploadSectionTab>
      </StyledUploadContainer>
      <BottomActions
        onClose={handleClose}
        onSubmit={handleSubmit}
        isSubmitDisabled={!Object.values(proofs).some((each) => each.length)}
        submitCTALabel="Submit for verification"
      />
    </StyledStepContainer>
  );
};

const mapStateToProps = (state) => ({
  user: state.session.user,
  isMobile: state.app.isMobileResolution,
});

const mapDispatchToProps = (dispatch) => {
  return bindActionCreators(
    { ...NotificationsActions, ...ModalActions, fetchWorkflowStatus: fetchWorkflowStatusReducer },
    dispatch,
  );
};

export default compose(connect(mapStateToProps, mapDispatchToProps))(UploadProofs);
