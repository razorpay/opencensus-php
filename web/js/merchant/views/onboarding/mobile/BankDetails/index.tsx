import React, { useEffect, useState } from 'react';
import * as Yup from 'yup';
import { Formik } from 'formik';
import TextInput from '@razorpay/blade-old/src/atoms/TextInput';
import { FormSection, Field, GetTouchedFields } from '../Form';
import { useActivationFormState, isTabComplete } from '../context/store';
import useActivation, { getRequestData } from '../hooks/useActivation';
import useConfigDetails from '../hooks/useConfigDetails';
import {
  getDetailsForIFSC,
  getBankTabHeader,
  isUnregisteredBusiness,
  getBankFieldError,
} from '../services/utils';
import { analyticsTrack } from 'common/services/tracking/segment';
import { useApp } from 'common/context/App';
import usePartnerActivation from '../hooks/usePartnerActivation';
import useTrackEvents from 'merchant/hooks/useTrackEvents';

interface BankDetailsProps {
  isFormLocked?: boolean;
}
const UNREG_BANK_ERROR =
  'Kindly make sure you enter your Personal Bank Account details. Beneficiary Name of this bank account should match your Personal Pan Name';
const REG_BANK_ERROR =
  'Make sure you enter your Company Bank Account details. Beneficiary Name of this bank account should match your Company Pan Name';

const BankDetails: React.FC<BankDetailsProps> = ({ isFormLocked }) => {
  const { data, postData } = useActivation();
  const { user, experiments } = useApp();
  const trackEvents = useTrackEvents();
  const { data: configData, refetch } = useConfigDetails('onboarding');

  const bankAndCompanyDetails = data.bank_and_company_details;
  const setBankAndCompanyDetailsCompleted = useActivationFormState(
    (state) => state.setBankAndCompanyDetailsCompleted,
  );
  const [isBlurCalled, setIsBlurCalled] = useState(false);
  const [branchIfscInfo, setBranchIfscInfo] = useState<string>('');

  const [bankDetailsCardTitle, setBankDetailsCardTitle] = useState('');

  const [bankAccountNumber, setBankAccountNumber] = useState();
  const [reAccountNumber, setReAccountNumber] = useState();
  const { getFieldStatus } = usePartnerActivation();

  const handleSubmit = (updatedDetails) => {
    const isAccountNumberValid =
      (bankAccountNumber ? bankAccountNumber : data.bank_account_number) === reAccountNumber;

    if (!isAccountNumberValid && !!updatedDetails.bank_account_number) {
      delete updatedDetails.bank_account_number;
    }
    if (!!updatedDetails.re_enter_bank_account_number) {
      delete updatedDetails.re_enter_bank_account_number;
    }

    const isComplete = isTabComplete(
      {
        ...data,
        bank_and_company_details: { ...bankAndCompanyDetails, ...updatedDetails },
      },
      'bank_and_company_details',
    );
    setBankAndCompanyDetailsCompleted(isComplete);
    const reqData = getRequestData(bankAndCompanyDetails, updatedDetails);
    if (Object.keys(reqData).length) {
      postData(reqData);
    }
  };

  const handleBlur = (e, formikProps) => {
    formikProps.handleBlur(e);
    setIsBlurCalled(true);
  };

  const fetchDefaultIfscInfo = () => {
    if (bankAndCompanyDetails.bank_branch_ifsc.value) {
      getDetailsForIFSC(bankAndCompanyDetails.bank_branch_ifsc.value)?.then((info) => {
        const defaultBranchIfscInfo = info ? `${info.bank}, ${info.branch}` : '';
        setBranchIfscInfo(defaultBranchIfscInfo);
      });
    }
  };

  const canDisabledField =
    experiments.isSyncBankVerificationEnabled &&
    configData?.bank_account_verification_attempt_count == 10;

  useEffect(() => {
    fetchDefaultIfscInfo();
  }, []);

  useEffect(() => {
    refetch();
  }, [bankAndCompanyDetails]);

  const { title, subtitle } = getBankTabHeader(Number(data.business_type));
  const hasBankVerificationFailed =
    data?.bank_details_verification_status &&
    !['initiated', 'verified'].includes(data?.bank_details_verification_status) &&
    experiments.isSyncBankVerificationEnabled;

  useEffect(() => {
    if (hasBankVerificationFailed) {
      analyticsTrack({
        objectName: 'insync',
        actionName: 'karza bank verification',
        screen: 'home page',
        eventAction: 'failed',
        user,
        properties: {
          bvs_attempt_count: configData?.bank_account_verification_attempt_count,
        },
      });
    } else if (
      data?.bank_details_verification_status === 'verified' &&
      experiments.isSyncBankVerificationEnabled
    ) {
      analyticsTrack({
        objectName: 'insync',
        actionName: 'karza bank verification',
        screen: 'home page',
        eventAction: 'success',
        user,
        properties: {
          bvs_attempt_count: configData?.bank_account_verification_attempt_count,
        },
      });
    }
  }, [hasBankVerificationFailed]);

  useEffect(() => {
    trackEvents({
      objectName: 'Page',
      actionName: 'Viewed',
      screen: 'home page',
      properties: {
        pageTitle: 'Bank Details',
      },
    });
  }, []);

  return (
    <Formik
      initialValues={{
        bank_account_name: bankAndCompanyDetails.bank_account_name.value,
        bank_account_number: bankAndCompanyDetails.bank_account_number.value,
        re_enter_bank_account_number: bankAndCompanyDetails.bank_account_number.value,
        bank_branch_ifsc: bankAndCompanyDetails.bank_branch_ifsc.value,
        bank_verificatio_attemp_count: configData?.bank_account_verification_attempt_count,
        hasBankVerificationFailed,
      }}
      validationSchema={() => {
        return Yup.object().shape({
          bank_account_name: Yup.string()
            .required('Bank Account Name is a required field')
            .nullable(),
          bank_account_number: Yup.string()
            .required('Bank Account Number is a required field')
            .nullable(),
          re_enter_bank_account_number: Yup.string()
            .oneOf([Yup.ref('bank_account_number')], "Account number don't match!")
            .required('Bank Account Number is a required field')
            .nullable(),
          bank_branch_ifsc: Yup.string()
            .min(11, 'IFSC code must be 11 characters')
            .required('IFSC is a required field')
            .nullable(),
        });
      }}
      onSubmit={() => {}}
      enableReinitialize
    >
      {(formikProps) => (
        <form
          onChange={formikProps.handleChange}
          onBlur={(e) => {
            handleBlur(e, formikProps);
          }}
        >
          <FormSection
            title={title}
            subtitle={
              formikProps.values.hasBankVerificationFailed
                ? canDisabledField
                  ? 'You have reached maximum limit to changed the bank account details'
                  : isUnregisteredBusiness(data?.business_type)
                  ? UNREG_BANK_ERROR
                  : REG_BANK_ERROR
                : subtitle
            }
            disabled={isFormLocked}
            hasError={formikProps.values.hasBankVerificationFailed}
          >
            <Field visible={!experiments.isUpdatedLiteOnboarding}>
              <TextInput
                width="auto"
                name="bank_account_name"
                label="Beneficiary Name"
                value={formikProps.values.bank_account_name}
                errorText={getBankFieldError(
                  formikProps.touched.bank_account_name,
                  formikProps.errors.bank_account_name,
                  formikProps.values.hasBankVerificationFailed,
                  formikProps.values.bank_verificatio_attemp_count,
                )}
                onBlur={() => {
                  setBankDetailsCardTitle(title);
                  analyticsTrack({
                    objectName: 'SignUp',
                    actionName: 'bank account name',
                    screen: 'home page',
                    eventAction: 'initiated',
                    user,
                  });
                }}
                disabled={
                  isFormLocked || canDisabledField || getFieldStatus('bank_account_name').isDisabled
                }
                helpText={getFieldStatus('bank_account_name').description}
              />
            </Field>
            <Field>
              <TextInput
                width="auto"
                name="bank_account_number"
                label="Account Number"
                value={formikProps.values.bank_account_number}
                errorText={getBankFieldError(
                  formikProps.touched.bank_account_number,
                  formikProps.errors.bank_account_number,
                  formikProps.values.hasBankVerificationFailed,
                  formikProps.values.bank_verificatio_attemp_count,
                )}
                onChange={(value) => {
                  setBankAccountNumber(value);
                }}
                onBlur={() => {
                  setBankDetailsCardTitle(title);
                  analyticsTrack({
                    objectName: 'SignUp',
                    actionName: 'bank account number',
                    screen: 'home page',
                    eventAction: 'initiated',
                    user,
                  });
                }}
                disabled={
                  isFormLocked ||
                  canDisabledField ||
                  getFieldStatus('bank_account_number').isDisabled
                }
                helpText={getFieldStatus('bank_account_number').description}
              />
            </Field>
            {!data.submitted ? (
              <Field visible={!experiments.isUpdatedLiteOnboarding}>
                <TextInput
                  width="auto"
                  name="re_enter_bank_account_number"
                  label="Re-Enter Account Number"
                  value={formikProps.values.re_enter_bank_account_number}
                  errorText={
                    formikProps.touched.re_enter_bank_account_number &&
                    formikProps.errors.re_enter_bank_account_number
                  }
                  onChange={(value) => {
                    setReAccountNumber(value);
                  }}
                  disabled={
                    isFormLocked ||
                    canDisabledField ||
                    getFieldStatus('bank_account_number').isDisabled
                  }
                  onBlur={() => setBankDetailsCardTitle(title)}
                />
              </Field>
            ) : null}
            <Field last>
              <TextInput
                width="auto"
                name="bank_branch_ifsc"
                label="IFSC Code"
                onChange={async (value) => {
                  if (value) {
                    const info: any = await getDetailsForIFSC(value);
                    if (info) {
                      setBranchIfscInfo(`${info.bank}, ${info.branch}`);
                    }
                  }
                }}
                value={formikProps.values.bank_branch_ifsc}
                errorText={getBankFieldError(
                  formikProps.touched.bank_branch_ifsc,
                  formikProps.errors.bank_branch_ifsc,
                  formikProps.values.hasBankVerificationFailed,
                  formikProps.values.bank_verificatio_attemp_count,
                )}
                onBlur={() => {
                  setBankDetailsCardTitle(title);
                  analyticsTrack({
                    objectName: 'SignUp',
                    actionName: 'bank branch ifsc',
                    screen: 'home page',
                    eventAction: 'initiated',
                    user,
                  });
                }}
                disabled={
                  isFormLocked || canDisabledField || getFieldStatus('bank_branch_ifsc').isDisabled
                }
                helpText={getFieldStatus('bank_branch_ifsc').description || branchIfscInfo}
              />
            </Field>
          </FormSection>

          <GetTouchedFields
            handleSubmit={handleSubmit}
            isBlurCalled={isBlurCalled}
            setIsBlurCalled={setIsBlurCalled}
            tabName="Bank Details"
            cardTitle={bankDetailsCardTitle}
          />
        </form>
      )}
    </Formik>
  );
};

export default BankDetails;
