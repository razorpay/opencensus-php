import React, { useState, useEffect, useRef } from 'react';
import { useQuery } from '@tanstack/react-query';
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
import { isUnregisteredBusiness } from 'merchant/views/onboarding/mobile/services/utils';
import Loader from 'common/components/Loader';
import { trackBankAccountDetailsChange } from 'merchant/views/Account/Profile/components/BankAccountDetailsChangeSteps/utils';
import { connect } from 'react-redux';
import { bindActionCreators, compose } from 'redux';

import * as ModalActions from 'merchant_common/reducers/modals';

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

/* Method to validate the benificiary name */
const validateBenificiaryName = (value) => {
  return validateBankDetails(value, 'name') ? undefined : "Enter a Valid Account Holder's Name";
};

const registeredBusinessHelpText = 'Beneficiary name should be the same as a business name';
const unregisteredBusinessHelpText =
  'Beneficiary name should be the same as your name in KYC documents';

const getBankName = (bank) => {
  if (!bank?.BANK || !bank.BRANCH) return '';
  return `${bank.BANK}, ${bank.BRANCH}`;
};

const BankAccountUpdateForm = (props) => {
  const { handleSubmit, settlementConfig, ifsc_code, setStep, setVerificationError, user } = props;
  const isOnTemporaryHold = settlementConfig.data?.config?.features?.hold?.status;
  const temporaryHoldReason = settlementConfig.data?.config?.features?.hold?.reason;
  const [showBranch, setShowBranch] = useState(false);
  const isMounted = useRef(false);

  const {
    refetch,
    data: bankData,
    isFetching,
  } = useQuery({
    queryKey: ['ifsc_code', ifsc_code],
    queryFn: async () => {
      const bankDataPromise = await fetch(`${window.BANK_DETAILS_URL}/${ifsc_code}`);
      const bankData = await bankDataPromise.json();
      return bankData;
    },
    enabled: false,
    retry: false,
    refetchOnWindowFocus: false,
    onSuccess: () => {
      setShowBranch(true);
    },
    onError: () => {
      setShowBranch(false);
    },
  });

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

  const handleSubmitCallback = ({ state, error }) => {
    setStep(state);
    if (error) {
      setVerificationError(error);
    }
  };

  const handleSubmission = (body) => {
    const { onSave } = props;
    const newBody = { ...body };
    return onSave(newBody, handleSubmitCallback);
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
  const beneficiaryNameHelpText = isUnregisteredBusiness(user.business_type)
    ? unregisteredBusinessHelpText
    : registeredBusinessHelpText;

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
            <label className="col-md-12 control-label label-required">Beneficiary Name</label>
            <div className="col-md-12">
              <Field
                name="beneficiary_name"
                component={InputField}
                className="material-input"
                validate={[required(), validateBenificiaryName]}
                validateOnChange
              />
              <small className="help-block">
                <i className="i i-info-circle" />
                <span>{beneficiaryNameHelpText}</span>
              </small>
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
  return bindActionCreators({ ...ModalActions }, dispatch);
};

export default compose(
  connect(mapStateToProps, mapDispatchToProps),
  reduxForm({
    form,
  }),
)(BankAccountUpdateForm);
