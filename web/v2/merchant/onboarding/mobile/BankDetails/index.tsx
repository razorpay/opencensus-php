import React from 'react';
import * as Yup from 'yup';
import { Formik } from 'formik';
import View from '@razorpay/blade/src/atoms/View';
import Space from '@razorpay/blade/src/atoms/Space';
import TextInput from '@razorpay/blade/src/atoms/TextInput';
import Checkbox from '@razorpay/blade/src/atoms/Checkbox';
import { FormSection, Field } from '../Form';
import { useActivationFormState, isVisible, isTabComplete } from '../context/store';
import useActivation from '../hooks/useActivation';
import { CIN_BusinessTypes } from '../Constants/OnboardingConstants';

const BankDetails: React.FC = () => {
  const { data, postData } = useActivation();
  const bankAndCompanyDetails = data.bank_and_company_details;
  const businessOverviewDetails = data.business_overview;
  const hasNoGSTIN = useActivationFormState((state) => state.no_gstin);
  const setNoGSTIN = useActivationFormState((state) => state.setNoGSTIN);
  const setBankAndCompanyDetailsCompleted = useActivationFormState(
    (state) => state.setBankAndCompanyDetailsCompleted,
  );

  const handleBlur = (e, formikProps) => {
    const updatedBankAndCompanyDetails = {
      bank_account_name: {
        value: formikProps.values.bank_account_name,
        error: formikProps.errors.bank_account_name,
      },
      bank_account_number: {
        value: formikProps.values.bank_account_number,
        error: formikProps.errors.bank_account_number,
      },
      bank_branch_ifsc: {
        value: formikProps.values.bank_branch_ifsc,
        error: formikProps.errors.bank_branch_ifsc,
      },
      gstin: {
        value: formikProps.values.gstin,
        error: formikProps.errors.gstin,
      },
      company_cin: {
        value: formikProps.values.company_cin,
        error: formikProps.errors.company_cin,
      },
    };
    const isComplete = isTabComplete(
      { ...data, bank_and_company_details: updatedBankAndCompanyDetails, hasNoGSTIN },
      'bank_and_company_details',
    );
    setBankAndCompanyDetailsCompleted(isComplete);
    if (isComplete) {
      postData(updatedBankAndCompanyDetails);
    }
    formikProps.handleBlur(e);
  };

  return (
    <Formik
      initialValues={{
        bank_account_name: bankAndCompanyDetails.bank_account_name.value,
        bank_account_number: bankAndCompanyDetails.bank_account_number.value,
        bank_branch_ifsc: bankAndCompanyDetails.bank_branch_ifsc.value,
        gstin: bankAndCompanyDetails.gstin.value,
        company_cin: bankAndCompanyDetails.company_cin.value,
      }}
      validationSchema={() => {
        return Yup.object().shape({
          bank_account_name: Yup.string().required('Bank Account Name is a required field'),
          bank_account_number: Yup.string().required('Bank Account Number is a required field'),
          bank_branch_ifsc: Yup.string().required('IFSC is a required field'),
          company_cin: Yup.lazy(() => {
            if (CIN_BusinessTypes.includes(Number(businessOverviewDetails.business_type.value))) {
              return Yup.string()
                .trim()
                .length(21, 'CIN must be 21 characters')
                .matches(/^([a-z]{3}-\d{4}|[ul]\d{5}[a-z]{2}\d{4}[a-z]{3}\d{6})$/i, {
                  message: 'Invalid Format',
                  excludeEmptyString: true,
                })
                .required('Company CIN is required field');
            }
            return Yup.string()
              .trim()
              .matches(/^([a-z]{3}-\d{4}|[ul]\d{5}[a-z]{2}\d{4}[a-z]{3}\d{6})$/i, {
                message: 'Invalid Format',
                excludeEmptyString: true,
              })
              .required('Company CIN is required field');
          }),
          gstin: Yup.string()
            .trim()
            .length(15, 'Please provide valid GSTIN')
            .required('GSTIN is a required field'),
        });
      }}
      onSubmit={() => console.log('onSubmit')}
    >
      {(formikProps) => (
        <form
          onChange={formikProps.handleChange}
          onBlur={(e) => {
            handleBlur(e, formikProps);
          }}
        >
          <FormSection
            title="Bank Details"
            subtitle="We will be depositing a small amount in this account to verify your bank details"
          >
            <Field>
              <TextInput
                width="auto"
                name="bank_account_name"
                label="Beneficiary Name"
                value={formikProps.values.bank_account_name}
                errorText={formikProps.errors.bank_account_name}
              />
            </Field>
            <Field>
              <TextInput
                width="auto"
                name="bank_account_number"
                label="Account Number"
                value={formikProps.values.bank_account_number}
                errorText={formikProps.errors.bank_account_number}
              />
            </Field>
            <Field last>
              <TextInput
                width="auto"
                name="bank_branch_ifsc"
                label="IFSC Code"
                value={formikProps.values.bank_branch_ifsc}
                errorText={formikProps.errors.bank_branch_ifsc}
              />
            </Field>
          </FormSection>

          <FormSection title="Company Details" last>
            <Field visible={isVisible('company_cin', data)}>
              <TextInput
                width="auto"
                name="company_cin"
                label="Company Identification Number (CIN)"
                value={formikProps.values.company_cin}
                errorText={formikProps.errors.company_cin}
              />
            </Field>
            <Field visible={isVisible('gstin', data)} last>
              <TextInput
                width="auto"
                name="gstin"
                label="GST Identification Number (GSTIN)"
                helpText="Should match either of your registered address or operational address"
                value={formikProps.values.gstin}
                errorText={formikProps.errors.gstin}
              />
            </Field>
            <Space margin={[1.75, 0, 0, 0]}>
              <View>
                <Checkbox
                  name="no_gstin"
                  title="I don't have a GSTIN"
                  checked={hasNoGSTIN}
                  onChange={(value) => setNoGSTIN(value)}
                />
              </View>
            </Space>
          </FormSection>
        </form>
      )}
    </Formik>
  );
};

export default BankDetails;
