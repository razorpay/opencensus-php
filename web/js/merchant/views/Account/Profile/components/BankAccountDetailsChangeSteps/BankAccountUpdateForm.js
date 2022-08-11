import React, { useState, useEffect, useRef } from 'react';
import { useQuery } from 'react-query';
import { Field, reduxForm, formValueSelector } from 'redux-form';
import AsyncButton from 'react-async-button';

import InputField from 'common/ui/Forms/InputField';
import InputGroupField from 'common/ui/Forms/InputField/InputGroupField';
import Fieldset from 'common/ui/Forms/Fieldset';

import { required } from 'common/utils/validators';

import { isWebkit, validateBankDetails } from 'common/utils/rzp-utils';
import Alert from 'common/new-ui/Alert';
import {
  PROPRIETORSHIP,
  INDIVIDUAL,
  NOT_REGISTERED,
} from 'merchant/views/onboarding/mobile/Constants/OnboardingConstants';
import Loader from 'common/components/Loader';
import { trackBankAccountDetailsChange } from 'merchant/views/Account/Profile/components/BankAccountDetailsChangeSteps/utils';
import { connect } from 'react-redux';
import { bindActionCreators, compose } from 'redux';
import FileUpload from 'merchant/components/File/Upload';
import { MAX_FILE_SIZE_LIMIT } from 'merchant/views/Account/constants';

import * as ModalActions from 'merchant_common/reducers/modals';
import * as NotificationsActions from 'merchant_common/reducers/notifications';

const reVerifyAccountNumber = (value, allValues) => {
  return value !== allValues.account_number ? 'Account number does not match' : undefined;
};

/* Method to validate weather the entered IFSC code is a valid one or not */
const verifyIFSCCode = (value) => {
  return validateBankDetails(value, 'ifsc') ? undefined : 'Incorrect IFSC code. Try again';
};

/* Method to Validate the account Number Entry by Pattern */
const verifyAccountNumber = (value) => {
  return validateBankDetails(value, 'accNo') ? undefined : 'Enter a Valid Account Number';
};

const getMaskedPanNumber = (panNumber) => {
  if (panNumber) {
    return `${panNumber.substring(0, 2)}xxxxx^${panNumber.substring(panNumber.length - 2)}`;
  }
  return 'xxxxxxxxx^';
};

const getBankName = (bank) => {
  if (!bank?.BANK || !bank.BRANCH) return '';
  return `${bank.BANK}, ${bank.BRANCH}`;
};

// TODO: temporarily disable new flow until new API for File upload is available
const BankAccountUpdateForm = (props) => {
  const { handleSubmit, settlementConfig, ifsc_code, showNotification } = props;
  const isOnTemporaryHold = settlementConfig.data?.config?.features?.hold?.status;
  const temporaryHoldReason = settlementConfig.data?.config?.features?.hold?.reason;
  const [showBranch, setShowBranch] = useState(false);
  const isMounted = useRef(false);
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

  const { refetch, data: bankData, isFetching } = useQuery(
    ['ifsc_code', ifsc_code],
    async () => {
      const bankDataPromise = await fetch(`${window.BANK_DETAILS_URL}/${ifsc_code}`);
      const bankData = await bankDataPromise.json();
      return bankData;
    },
    {
      enabled: false,
      retry: false,
      refetchOnWindowFocus: false,
      onSuccess: () => {
        setShowBranch(true);
      },
      onError: () => {
        setShowBranch(false);
      },
    },
  );

  useEffect(() => {
    if (!isMounted.current) {
      isMounted.current = true;
      return;
    }
    setShowBranch(false);
    if (validateBankDetails(ifsc_code, 'ifsc')) {
      refetch({ cancelRefetch: true });
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [ifsc_code]);

  const handleSubmission = (body) => {
    const { onSave } = props;
    const newBody = { ...body };
    if (!file) {
      showNotification({
        type: 'error',
        message: 'Please upload a valid Bank Account proof.',
      });
      return null;
    }
    newBody.address_proof_url = file;
    return onSave(newBody);
  };

  const getBankAccountBannerContent = () => {
    const {
      user: { promoter_pan, company_pan, business_type },
    } = props;
    const promoterPan = getMaskedPanNumber(promoter_pan);
    const companyPan = getMaskedPanNumber(company_pan);

    switch (Number(business_type)) {
      case PROPRIETORSHIP:
        return `The bank account must belong to the business PAN holder ${companyPan} or signatory PAN holder ${promoterPan} only.`;
      case INDIVIDUAL:
      case NOT_REGISTERED:
        return `The bank account must belong to the PAN holder ${promoterPan} only.`;
      default:
        return `The bank account must belong to the business PAN holder ${companyPan} only.`;
    }
  };

  let ifscCodeSuffix = '';
  if (isFetching) {
    ifscCodeSuffix = <Loader width={2.25} height={2.25} />;
  } else if (showBranch) {
    ifscCodeSuffix = getBankName(bankData);
  }

  return (
    <div className="bank-account-update-form">
      <header className="bank-details-header">Add your new bank account details</header>
      {isOnTemporaryHold && (
        <Alert.Error iconBefore="i i-triangle-alert">
          <span className="pr-5">Your settlements have been put on hold due to</span>
          <strong className="pr-5">{temporaryHoldReason}</strong>
          <span>Please update alternate details to unblock settlements.</span>
        </Alert.Error>
      )}
      <Alert.Warning iconBefore="i-info-outline">{getBankAccountBannerContent()}</Alert.Warning>
      <form className="form-horizontal" onSubmit={handleSubmit(handleSubmission)}>
        <Fieldset>
          <div className="form-group">
            <label className="col-md-12 control-label label-required">Account number</label>
            <div className="col-md-12">
              <Field
                name="account_number"
                component={InputField}
                className={`material-input${isWebkit ? ' webkit-sec' : ''}`}
                type={isWebkit ? 'text' : 'password'}
                autoComplete="off"
                validate={[required(), verifyAccountNumber]}
                validateOnChange
                onBlur={() => {
                  trackBankAccountDetailsChange({
                    objectName: 'Bank Account Number',
                    actionName: 'Entered',
                    screen: 'My Account',
                  });
                }}
              />
            </div>
          </div>

          <div className="form-group">
            <label className="col-md-12 control-label label-required">Confirm account number</label>
            <div className="col-md-12">
              <Field
                name="account_number_confirmation"
                component={InputField}
                className="material-input"
                validate={[required(), reVerifyAccountNumber]}
                validateOnChange
              />
            </div>
          </div>

          <div className="form-group">
            <label className="col-md-12 control-label label-required">IFSC code</label>
            <div className="col-md-12">
              <Field
                name="ifsc_code"
                component={InputGroupField}
                className="material-input"
                validate={[required(), verifyIFSCCode]}
                suffix={ifscCodeSuffix}
                validateOnChange
                onBlur={() => {
                  trackBankAccountDetailsChange({
                    objectName: 'Bank Account IFSC Code',
                    actionName: 'Entered',
                    screen: 'My Account',
                  });
                }}
              />
            </div>
          </div>
          <div className="form-group">
            <label className="col-md-12 control-label label-required">
              Company&apos;s Bank Account Statement with Address
            </label>
            <div className="col-md-12">
              <span className="help-block">
                Upload following:
                <ul>
                  <li>
                    Bank Account Statement (last three months or since opening of account) OR
                    cancelled cheque issued in the name of the registered business
                  </li>
                </ul>
              </span>
            </div>
            <div className="col-md-12">
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
          </div>
          <div className="form-actions">
            <AsyncButton
              type="button"
              className="btn"
              text="Submit and verify"
              pendingText="Submitting and verifying..."
              onClick={handleSubmit(handleSubmission)}
            />
          </div>
        </Fieldset>
      </form>
    </div>
  );
};

const form = 'changeBankAccountDetails';
const selector = formValueSelector(form);

const mapStateToProps = (state) => {
  return {
    user: state.session.user,
    settlementConfig: state.settlement.config,
    ifsc_code: selector(state, 'ifsc_code'),
  };
};

const mapDispatchToProps = (dispatch) => {
  return bindActionCreators({ ...ModalActions, ...NotificationsActions }, dispatch);
};

export default compose(
  connect(mapStateToProps, mapDispatchToProps),
  reduxForm({
    form,
  }),
)(BankAccountUpdateForm);
