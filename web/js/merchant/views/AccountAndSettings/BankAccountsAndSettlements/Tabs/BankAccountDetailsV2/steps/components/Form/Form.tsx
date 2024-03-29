import { InfoIcon, Text, TextInput } from '@razorpay/blade/components';
import { validateBankDetails } from 'common/utils/rzp-utils';
import { fetchBankAccount, saveBankAccountChangesAutomate } from 'merchant/reducers/profile';
import BottomActions from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/components/BottomActions';
import { RETRY_LIMIT } from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/constants/data';
import {
  getBVSErrorCodeFromErrors,
  isErrorCodeSupported,
} from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/steps/components/InputError/utils';
import LoadingStep from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/steps/components/LoadingStep/LoadingStep';
import {
  BANK_ACCOUNT_UPDATE_STEPS,
  InputFormPropsInterface,
  INPUT_VALIDATION_STATES,
  LOADING_STATE,
} from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/typings';
import { trackBankAccountUpdateEvent } from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/utils/track';
import { selfServeTrackSuccess } from 'common/utils/selfServeAnalytics';
import React, { useEffect, useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { connect } from 'react-redux';
import { bindActionCreators, compose } from 'redux';
import { INPUT_FIELDS_PROPS } from './constants';
import { BeneficiaryAlertWrapper, FormInputWrapper, StyledAlert, StyledForm } from './styled';
import {
  getBankAccountBannerContent,
  getBankName,
  getUpdatePayload,
  isFormInputValid,
} from './utils';
import { Modules } from 'common/constant/enums';
import { fetchWorkflowStatus as fetchWorkflowStatusAction } from 'merchant/reducers/workflows';
import { WORKFLOW_TYPES } from 'merchant/views/Account/Profile/components/WorkflowRequests/constants';

const Form = ({
  user,
  state,
  setState,
  closeModal,
  setView,
  setLayoutInfo,
  showNotification,
  saveBankAccountChangesAutomate,
  fetchBankAccount,
  fetchWorkflowStatus,
}: InputFormPropsInterface): JSX.Element => {
  const [isProcessing, setIsProcessing] = useState<boolean>(false);
  const [shouldShowBranch, setShouldShowBranch] = useState(false);
  const {
    refetch: refetchIfscCode,
    data: bankData,
    isFetching: isFetchingBankBranch,
  } = useQuery({
    queryKey: ['ifsc_code', state.inputData.ifsc_code],
    queryFn: async () => {
      const bankDataPromise = await fetch(
        `${window.BANK_DETAILS_URL}/${state.inputData.ifsc_code}`,
      );
      const bankData = await bankDataPromise.json();
      return bankData;
    },
    enabled: false,
    retry: false,
    refetchOnWindowFocus: false,
    onSuccess: () => {
      setShouldShowBranch(true);
    },
    onError: () => {
      setShouldShowBranch(false);
    },
  });

  const validator = {
    account_number: (val) => validateBankDetails(val, 'accNo'),
    account_number_confirmation: (val) => state.inputData.account_number === val,
    ifsc_code: (val) => validateBankDetails(val, 'ifsc'),
    beneficiary_name: (val) => validateBankDetails(val, 'name'),
  };

  function handleChange(event) {
    const { name, value } = event;
    setState((prevState) => ({
      ...prevState,
      inputData: {
        ...prevState.inputData,
        [name]: value,
      },
      inputValidation: {
        ...prevState.inputValidation,
        [name]: validator[name](value)
          ? INPUT_VALIDATION_STATES.NONE
          : INPUT_VALIDATION_STATES.ERROR,
      },
    }));
  }

  const submitForm = () => {
    trackBankAccountUpdateEvent({
      objectName: 'Bank Account Update Submit',
      actionName: 'Request',
    });
    setIsProcessing(true);
    setLayoutInfo(BANK_ACCOUNT_UPDATE_STEPS.LOADING_VIEW);

    const formData = getUpdatePayload(state?.inputData, user);
    return saveBankAccountChangesAutomate(user.id, formData) //user.id is merchant_id not user_id
      .then(({ data }) => {
        if (data.new_bank_account && data.sync_flow === true) {
          selfServeTrackSuccess({
            selfServeAction: 'Bank Account Updated',
            page: 'Profile',
            screen: Modules.AccountAndSettings,
            props: {
              version: 'v2',
            },
          });
          fetchBankAccount();
          fetchWorkflowStatus(WORKFLOW_TYPES.BANK_DETAIL_UPDATE);
          setView(BANK_ACCOUNT_UPDATE_STEPS.PENNY_TESTING_SUCCESS);
        } else {
          const { retries } = state;
          if (retries < RETRY_LIMIT) {
            setView(BANK_ACCOUNT_UPDATE_STEPS.PENNY_TESTING_RETRY);
          } else {
            setView(BANK_ACCOUNT_UPDATE_STEPS.UPLOAD_PROOF);
          }
        }
      })
      .catch(({ errors }) => {
        const errorCode = getBVSErrorCodeFromErrors(errors);
        if (errorCode && isErrorCodeSupported(errorCode)) {
          setView(BANK_ACCOUNT_UPDATE_STEPS.PENNY_TESTING_INPUT_ERROR);
          setState({
            ...state,
            bvs_error_code: errorCode,
          });
        } else {
          showNotification({
            type: 'error',
            message: errors,
          });
          closeModal();
        }
      })
      .finally(() => {
        setIsProcessing(false);
        setLayoutInfo('');
      });
  };

  const handleCancel = (): void => {
    trackBankAccountUpdateEvent({
      objectName: 'Bottom Cancel Action',
      actionName: 'Clicked',
      properties: {
        ctaSource: 'input details',
      },
    });
    closeModal();
  };

  useEffect(() => {
    setShouldShowBranch(false);
    if (validateBankDetails(state.inputData.ifsc_code, 'ifsc')) {
      refetchIfscCode();
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [state.inputData.ifsc_code]);

  useEffect(() => {
    trackBankAccountUpdateEvent({
      objectName: isProcessing ? 'Penny Test Processing' : 'Bank Account Input Details',
      actionName: 'Displayed',
    });
  }, [isProcessing]);

  if (isProcessing) {
    return <LoadingStep type={LOADING_STATE.PENNY_TESTING_INPROGRESS} lottieClass={['mb-20']} />;
  }

  return (
    <StyledForm onSubmit={submitForm}>
      <StyledAlert
        emphasis="subtle"
        description={getBankAccountBannerContent(user)}
        color="notice"
        isDismissible={false}
      />
      <FormInputWrapper>
        <TextInput
          {...INPUT_FIELDS_PROPS.account_number}
          value={state.inputData.account_number}
          validationState={state.inputValidation.account_number}
          onChange={handleChange}
        />
        <TextInput
          {...INPUT_FIELDS_PROPS.account_number_confirmation}
          value={state.inputData.account_number_confirmation}
          validationState={state.inputValidation.account_number_confirmation}
          onChange={handleChange}
        />
        <TextInput
          {...INPUT_FIELDS_PROPS.ifsc_code}
          value={state.inputData.ifsc_code}
          validationState={state.inputValidation.ifsc_code}
          onChange={handleChange}
          isLoading={isFetchingBankBranch}
          helpText={!isFetchingBankBranch && shouldShowBranch ? getBankName(bankData) : undefined}
        />
        <TextInput
          {...INPUT_FIELDS_PROPS.beneficiary_name}
          value={state.inputData.beneficiary_name}
          validationState={state.inputValidation.beneficiary_name}
          onChange={handleChange}
        />
        <BeneficiaryAlertWrapper>
          <InfoIcon color="interactive.icon.gray.muted" size="small" />
          <Text size="small" color="surface.text.gray.muted">
            Beneficiary name should be the same as your name in KYC documents
          </Text>
        </BeneficiaryAlertWrapper>
      </FormInputWrapper>
      <BottomActions
        onClose={handleCancel}
        onSubmit={submitForm}
        isSubmitDisabled={
          !isFormInputValid({
            inputData: state.inputData,
            inputValidation: state.inputValidation,
          })
        }
        submitCTALabel="Submit and verify"
      />
    </StyledForm>
  );
};

const mapStateToProps = (state) => ({
  user: state.session.user,
});

const mapDispatchToProps = (dispatch) => {
  return bindActionCreators(
    {
      saveBankAccountChangesAutomate,
      fetchBankAccount,
      fetchWorkflowStatus: fetchWorkflowStatusAction,
    },
    dispatch,
  );
};

export default compose(connect(mapStateToProps, mapDispatchToProps))(Form);
