import React, { useState } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import AsyncButton from 'react-async-button';
import FileUpload from 'merchant/components/File/Upload';
import { MAX_FILE_SIZE_LIMIT } from 'merchant/views/Account/constants';
import BankAccountUpdateStatus from './BankAccountUpdateStatus';
import { BANK_ACCOUNT_UPDATE_UNDER_REVIEW, BANK_ACCOUNT_UPDATE_FILE_UPLOAD } from './constants';
import { trackBankAccountDetailsChange, getResponseTime } from './utils';
import BankAccountUpdateState from './BankAccountUpdateState';

import * as ProfileActions from 'merchant/reducers/profile';
import * as ModalActions from 'merchant_common/reducers/modals';
import * as NotificationsActions from 'merchant_common/reducers/notifications';

const BankAccountUpdateAsyncFlow = ({
  user,
  closeModal,
  showNotification,
  saveBankAccountChangesAutomate,
  newBankAccountDetails,
}) => {
  const [isFileUploaded, setIsFileUploaded] = useState(false);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [file, setFile] = useState(null);

  const handleFileChange = (file) => {
    setFile(file);
  };

  const removeFile = () => {
    setFile(null);
  };

  const onBiggerFileSize = () => {
    showNotification({
      type: 'error',
      message: `File exceeds total upload limit of ${MAX_FILE_SIZE_LIMIT / 1024 / 1024}MB!`,
    });
  };

  const handleSubmit = () => {
    const formdata = new FormData();
    const { account_number, ifsc_code } = newBankAccountDetails;
    const body = {
      address_proof_url: file,
      //required fields for api
      account_number,
      ifsc_code,
      beneficiary_email: user.email,
      beneficiary_mobile: user.contact_mobile,
      beneficiary_name: user.bank_account_name,
    };

    for (const key in body) {
      if (body[key]) {
        formdata.append(key, body[key]);
      }
    }

    trackBankAccountDetailsChange({
      objectName: 'Bank Account Update Submit',
      actionName: 'Request',
      properties: {
        fileUploadSubmit: true,
      },
    });
    const requestStartedAt = new Date();
    setIsSubmitting(true);
    return saveBankAccountChangesAutomate(user.id, formdata)
      .then(() => {
        trackBankAccountDetailsChange({
          objectName: 'Bank Account Update Submit',
          actionName: 'Result',
          properties: {
            status: 'success',
            responseTime: getResponseTime(requestStartedAt),
            requestType: 'async',
            fileUploadSubmit: true,
          },
        });
        setIsFileUploaded(true);
      })
      .catch(({ errors }) => {
        showNotification({
          type: 'error',
          message: errors || 'Some network error has occurred',
        });
        trackBankAccountDetailsChange({
          objectName: 'Bank Account Update Submit',
          actionName: 'Result',
          properties: {
            status: 'failure',
            responseTime: getResponseTime(requestStartedAt),
            errorMessage: errors?.[0],
            fileUploadSubmit: true,
          },
        });
      })
      .finally(() => setIsSubmitting(false));
  };

  if (isSubmitting) {
    return (
      <BankAccountUpdateState data={BANK_ACCOUNT_UPDATE_FILE_UPLOAD} lottieDivClass={['mb-20']} />
    );
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
        <div className="sub-title">Upload your bank account statement for our team to review.</div>
      </div>
      <hr className="divider" />
      <div className="upload-info">
        Upload your bank statement for the last 3 months, or from the date of opening if its a new
        account.
      </div>
      <div class="form-group">
        <FileUpload
          name="bank-proof"
          size="small"
          maxSize={MAX_FILE_SIZE_LIMIT}
          showFileSize={false}
          showAcceptInfo={true}
          accept={['jpg', 'png', 'pdf']}
          onCloseClick={removeFile}
          onFileChange={handleFileChange}
          onBiggerFileSize={onBiggerFileSize}
          showCloseBtn
        />
      </div>
      <div class="form-actions">
        <AsyncButton
          type="button"
          class="btn"
          text="Submit"
          pendingText="Submitting..."
          disabled={!file}
          onClick={handleSubmit}
        />
      </div>
    </div>
  );
};

const mapStateToProps = (state) => {
  return {
    user: state.session.user,
  };
};

const mapDispatchToProps = (dispatch) => {
  return bindActionCreators(
    { ...ProfileActions, ...ModalActions, ...NotificationsActions },
    dispatch,
  );
};

export default connect(mapStateToProps, mapDispatchToProps)(BankAccountUpdateAsyncFlow);
