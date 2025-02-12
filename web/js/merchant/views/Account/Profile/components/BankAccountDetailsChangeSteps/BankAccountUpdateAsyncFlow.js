import React, { useState } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import AsyncButton from 'react-async-button';
import FileUpload from 'merchant/components/File/Upload';
import { allowedVideoExtensions } from 'merchant/components/File/constants';
import { MAX_FILE_SIZE_LIMIT } from 'merchant/views/Account/constants';
import BankAccountUpdateStatus from './BankAccountUpdateStatus';
import {
  BANK_ACCOUNT_UPDATE_UNDER_REVIEW,
  CANCELLED_CHEQUE_VIDEO_UPLOAD,
  BANK_VERIFICATION_LETTER_UPLOAD,
} from './constants';
import { trackBankAccountDetailsChange, getResponseTime } from './utils';
import BankAccountUpdateState from './BankAccountUpdateState';
import { merchantFetch } from 'merchant/utils/ajax';
import { fetchWorkflowStatus as fetchWorkflowStatusReducer } from 'merchant/reducers/workflows';
import Radio from '@razorpay/blade-old/src/atoms/Radio';
import { WORKFLOW_TYPES } from 'merchant/views/Account/Profile/components/WorkflowRequests/constants';
import { showWorkflowStatus } from 'merchant/views/Account/Profile/components/WorkflowRequests/utils';

import * as ModalActions from 'merchant_common/reducers/modals';
import * as NotificationsActions from 'merchant_common/reducers/notifications';

const CANCELLED_CHEQUE_VIDEO = 'cancelledChequeVideo';
const BANK_VERIFICATION_LETTER = 'bankVerificationLetter';
const isCancelledChequeVideoUpload = (fileUploadOption) =>
  fileUploadOption === CANCELLED_CHEQUE_VIDEO;

const handleWatchSampleVideoClick = () => {
  trackBankAccountDetailsChange({
    objectName: 'Sample Video CTA',
    actionName: 'Clicked',
  });
};

const BankAccountUpdateAsyncFlow = ({
  closeModal,
  showNotification,
  fetchWorkflowStatus,
  user,
}) => {
  const [isFileUploaded, setIsFileUploaded] = useState(false);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [files, setFiles] = useState([]);
  const [fileUploadOption, setFileUploadOption] = useState(CANCELLED_CHEQUE_VIDEO);
  const acceptFiles = isCancelledChequeVideoUpload(fileUploadOption)
    ? allowedVideoExtensions
    : ['jpg', 'png', 'pdf'];
  const showAcceptInfo = fileUploadOption === BANK_VERIFICATION_LETTER;

  const handleFileUploadOptionChange = (fileUploadOption) => {
    setFileUploadOption(fileUploadOption);
    setFiles([]);
  };

  const handleFileChange = (file) => {
    setFiles([file]);
  };

  const removeFile = () => {
    setFiles([]);
  };

  const onBiggerFileSize = () => {
    showNotification({
      type: 'error',
      message: `File exceeds total upload limit of ${MAX_FILE_SIZE_LIMIT / 1024 / 1024}MB!`,
    });
  };

  const handleSubmit = () => {
    const formData = new FormData();
    formData.append('address_proof_url', files[0]);

    const documentType = isCancelledChequeVideoUpload(fileUploadOption)
      ? 'Cancelled Cheque Video'
      : 'Bank Verification Letter';
    const requestStartedAt = new Date();
    setIsSubmitting(true);
    return merchantFetch({
      url: 'merchants/bank_account/file/upload',
      method: 'post',
      data: formData,
    })
      .then(() => {
        trackBankAccountDetailsChange({
          objectName: 'Bank Account Document',
          actionName: 'Uploaded',
          properties: {
            status: 'success',
            responseTime: getResponseTime(requestStartedAt),
            documentType,
          },
        });
        setIsFileUploaded(true);
        fetchWorkflowStatus(WORKFLOW_TYPES.BANK_DETAIL_UPDATE);
        showWorkflowStatus(user.id);
      })
      .catch(({ errors }) => {
        showNotification({
          type: 'error',
          message: errors,
        });
        trackBankAccountDetailsChange({
          objectName: 'Bank Account Document',
          actionName: 'Uploaded',
          properties: {
            status: 'failure',
            responseTime: getResponseTime(requestStartedAt),
            documentType,
            errorMessage: errors?.[0],
          },
        });
      })
      .finally(() => setIsSubmitting(false));
  };

  if (isSubmitting) {
    const data = isCancelledChequeVideoUpload(fileUploadOption)
      ? CANCELLED_CHEQUE_VIDEO_UPLOAD
      : BANK_VERIFICATION_LETTER_UPLOAD;
    return <BankAccountUpdateState data={data} lottieDivClass={['mb-20']} />;
  }

  if (isFileUploaded) {
    return (
      <BankAccountUpdateStatus
        data={BANK_ACCOUNT_UPDATE_UNDER_REVIEW}
        buttonText="Okay, got it"
        onButtonClick={closeModal}
      />
    );
  }

  return (
    <div className="file-upload">
      <div className="description">
        <div className="title">Your given bank account couldn&apos;t be verified.</div>
        <div className="sub-title">Upload a bank proof (any one) for our team to review:</div>
      </div>
      <hr className="divider" />
      <div className="upload-info">
        <Radio defaultValue={fileUploadOption} onChange={handleFileUploadOptionChange}>
          <Radio.Option
            value={CANCELLED_CHEQUE_VIDEO}
            title="Video of cancelled cheque (recommended)"
            helpText="Record a video of you cancelling a cheque of the given bank account. All details must be clearly visible."
            name="file-upload"
          />
          <a
            className="btn watch-sample-video-link"
            target="_blank"
            rel="noopener noreferrer"
            href="https://youtu.be/MRC4sl23olw"
            onClick={handleWatchSampleVideoClick}
          >
            <i className="i i-play-filled-circle m-r" /> Watch sample video
          </a>
          <Radio.Option
            value={BANK_VERIFICATION_LETTER}
            title="Bank Verification letter"
            helpText="Upload a verification letter form your bank stating that the account belongs to you. This should be on bank's official letter head and duly signed by an authorised signatory of the bank."
            name="file-upload"
          />
        </Radio>
      </div>
      <div className="form-group">
        <FileUpload
          name="bank-proof"
          size="small"
          maxSize={MAX_FILE_SIZE_LIMIT}
          showFileSize={false}
          showAcceptInfo={showAcceptInfo}
          accept={acceptFiles}
          onCloseClick={removeFile}
          onFileChange={handleFileChange}
          onBiggerFileSize={onBiggerFileSize}
          showCloseBtn
          resetFileUpload={fileUploadOption}
          files={files}
        />
      </div>
      <div className="form-actions">
        <AsyncButton
          type="button"
          className="btn"
          text="Submit"
          pendingText="Submitting..."
          disabled={!files.length}
          onClick={handleSubmit}
        />
      </div>
    </div>
  );
};

const mapDispatchToProps = (dispatch) => {
  return bindActionCreators(
    { ...ModalActions, ...NotificationsActions, fetchWorkflowStatus: fetchWorkflowStatusReducer },
    dispatch,
  );
};

export default connect(
  (state) => ({ user: state.session.user }),
  mapDispatchToProps,
)(BankAccountUpdateAsyncFlow);
