import React, { useEffect, useState } from 'react';
import * as Yup from 'yup';
import { Formik } from 'formik';
import TextInput from '@razorpay/blade-old/src/atoms/TextInput';
import { FormSection, Field, GetTouchedFields } from '../Form';
import { useActivationFormState, isTabComplete } from '../context/store';
import useActivation, { getRequestData } from '../hooks/useActivation';
import { getDetailsForIFSC, getBankTabHeader } from '../services/utils';
import { analyticsTrack } from 'common/services/tracking/segment';
import { useApp } from 'common/context/App';
import usePartnerActivation from '../hooks/usePartnerActivation';

interface BankDetailsProps {
  isFormLocked?: boolean;
}

const BankDetails: React.FC<BankDetailsProps> = ({ isFormLocked }) => {
  const { data, postData } = useActivation();
  const { user } = useApp();
  const bankAndCompanyDetails = data.bank_and_company_details;
  const setBankAndCompanyDetailsCompleted = useActivationFormState(
    (state) => state.setBankAndCompanyDetailsCompleted,
  );
  const [isBlurCalled, setIsBlurCalled] = useState(false);
  const [branchIfscInfo, setBranchIfscInfo] = useState<string>('');

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

  useEffect(() => {
    fetchDefaultIfscInfo();
  }, []);

  const { title, subtitle } = getBankTabHeader(Number(data.business_type));

  return (
    <Formik
      initialValues={{
        bank_account_name: bankAndCompanyDetails.bank_account_name.value,
        bank_account_number: bankAndCompanyDetails.bank_account_number.value,
        re_enter_bank_account_number: bankAndCompanyDetails.bank_account_number.value,
        bank_branch_ifsc: bankAndCompanyDetails.bank_branch_ifsc.value,
      }}
      validationSchema={() => {
        return Yup.object().shape({
          bank_account_name: Yup.string()
            .matches(/^[a-zA-Z0-9][a-zA-Z0-9-&\\'._()\s–\\/]{3,119}$/, {
              message:
                'Name should contain at least 4 characters. Exclude numbers and special characters',
              excludeEmptyString: true,
            })
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
    >
      {(formikProps) => (
        <form
          onChange={formikProps.handleChange}
          onBlur={(e) => {
            handleBlur(e, formikProps);
          }}
        >
          <FormSection title={title} subtitle={subtitle} disabled={isFormLocked}>
            <Field>
              <TextInput
                width="auto"
                name="bank_account_name"
                label="Beneficiary Name"
                value={formikProps.values.bank_account_name}
                errorText={
                  formikProps.touched.bank_account_name && formikProps.errors.bank_account_name
                }
                onBlur={() => {
                  analyticsTrack({
                    objectName: 'SignUp',
                    actionName: 'bank account name',
                    screen: 'home page',
                    eventAction: 'initiated',
                    user,
                  });
                }}
                disabled={isFormLocked || getFieldStatus('bank_account_name').isDisabled}
                helpText={getFieldStatus('bank_account_name').description}
              />
            </Field>
            <Field>
              <TextInput
                width="auto"
                name="bank_account_number"
                label="Account Number"
                value={formikProps.values.bank_account_number}
                errorText={
                  formikProps.touched.bank_account_number && formikProps.errors.bank_account_number
                }
                onChange={(value) => {
                  setBankAccountNumber(value);
                }}
                onBlur={() => {
                  analyticsTrack({
                    objectName: 'SignUp',
                    actionName: 'bank account number',
                    screen: 'home page',
                    eventAction: 'initiated',
                    user,
                  });
                }}
                disabled={isFormLocked || getFieldStatus('bank_account_number').isDisabled}
                helpText={getFieldStatus('bank_account_number').description}
              />
            </Field>
            {!data.submitted ? (
              <Field>
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
                  disabled={isFormLocked || getFieldStatus('bank_account_number').isDisabled}
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
                errorText={
                  formikProps.touched.bank_branch_ifsc && formikProps.errors.bank_branch_ifsc
                }
                onBlur={() => {
                  analyticsTrack({
                    objectName: 'SignUp',
                    actionName: 'bank branch ifsc',
                    screen: 'home page',
                    eventAction: 'initiated',
                    user,
                  });
                }}
                disabled={isFormLocked || getFieldStatus('bank_branch_ifsc').isDisabled}
                helpText={getFieldStatus('bank_branch_ifsc').description || branchIfscInfo}
              />
            </Field>
          </FormSection>

          <GetTouchedFields
            handleSubmit={handleSubmit}
            isBlurCalled={isBlurCalled}
            setIsBlurCalled={setIsBlurCalled}
          />
        </form>
      )}
    </Formik>
  );
};

export default BankDetails;
