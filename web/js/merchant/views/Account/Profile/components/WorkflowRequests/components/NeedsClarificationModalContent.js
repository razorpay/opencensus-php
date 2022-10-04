import React from 'react';
import Input from 'common/new-ui/Input';
import FileUpload from 'merchant/components/File/Upload';
import { MAX_FILE_SIZE_LIMIT } from 'merchant/views/Account/constants';
import AsyncButton from 'react-async-button';
import NeedsClarificationContent from './NeedsClarificationContent';
import {
  BankAccountUpdateState,
  BankAccountUpdateStatus,
  BANK_ACCOUNT_UPDATE_UNDER_REVIEW,
  BANK_ACCOUNT_UPDATE_SUBMIT_DETAILS,
} from '../../BankAccountDetailsChangeSteps';
import { allowedVideoExtensions } from 'merchant/components/File/constants';

const NeedsClarificationModalContent = ({
  workflowType,
  workflows,
  closeModal,
  isSubmitting,
  isUploadingDocument,
  isResponseValid,
  isClarificationSubmitted,
  onChange,
  onSubmit,
  removeFile,
  handleFileUpload,
  onBiggerFileSize,
  isBankAccountUpdateWorkflow,
}) => {
  const isSubmitDisabled =
    workflows[workflowType].loading || isSubmitting || !isResponseValid || isUploadingDocument;
  const needsClarificationMessage = workflows[workflowType].needs_clarification;

  if (isBankAccountUpdateWorkflow && isSubmitting) {
    return (
      <BankAccountUpdateState
        data={BANK_ACCOUNT_UPDATE_SUBMIT_DETAILS}
        lottieDivClass={['mb-20']}
      />
    );
  }

  if (isBankAccountUpdateWorkflow && isClarificationSubmitted) {
    return (
      <BankAccountUpdateStatus
        data={BANK_ACCOUNT_UPDATE_UNDER_REVIEW}
        buttonText="Okay, got it"
        onButtonClick={closeModal}
      />
    );
  }

  return (
    <>
      {needsClarificationMessage && (
        <NeedsClarificationContent
          isBankAccountUpdateWorkflow={isBankAccountUpdateWorkflow}
          needsClarificationMessage={needsClarificationMessage}
        />
      )}

      <Input.Textarea
        name="response-text"
        className="Input--vTop Input--space"
        label={isBankAccountUpdateWorkflow ? 'Note' : 'Add your reply below'}
        placeholder={isBankAccountUpdateWorkflow ? 'Add a note' : 'Enter here'}
        onChange={onChange}
        required
      />
      {isResponseValid === false ? (
        <p className="notify-error">Minimum 50 characters required</p>
      ) : null}
      <div className="form-group">
        <FileUpload
          name="response-files"
          size="small"
          maxSize={MAX_FILE_SIZE_LIMIT}
          showFileSize={false}
          showAcceptInfo={false}
          onCloseClick={removeFile}
          onFileChange={handleFileUpload}
          onBiggerFileSize={onBiggerFileSize}
          multi
          showCloseBtn
          {...(isBankAccountUpdateWorkflow && {
            multi: false,
            accept: ['jpg', 'png', 'pdf', ...allowedVideoExtensions],
          })}
        />
      </div>

      {/* Add AsyncBtn here */}
      {isBankAccountUpdateWorkflow ? (
        <div className="form-actions">
          <AsyncButton
            type="button"
            className="btn"
            text="Submit"
            pendingText="Submitting..."
            onClick={onSubmit}
            disabled={isSubmitDisabled}
          />
        </div>
      ) : (
        <button
          type="submit"
          className="btn btn-primary btn-block"
          onClick={onSubmit}
          disabled={isSubmitDisabled}
        >
          Submit Clarification
        </button>
      )}
    </>
  );
};

export default NeedsClarificationModalContent;
