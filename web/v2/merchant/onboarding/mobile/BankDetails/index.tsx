import React, { useEffect, useState } from 'react';
import * as Yup from 'yup';
import { Formik } from 'formik';
import View from '@razorpay/blade/src/atoms/View';
import Space from '@razorpay/blade/src/atoms/Space';
import TextInput from '@razorpay/blade/src/atoms/TextInput';
import Checkbox from '@razorpay/blade/src/atoms/Checkbox';
import { FormSection, Field, GetTouchedFields } from '../Form';
import { useActivationFormState, isVisible, isTabComplete } from '../context/store';
import useActivation, { getRequestData } from '../hooks/useActivation';
import { CIN_BusinessTypes } from '../Constants/OnboardingConstants';
import { getLabel, isUnregisteredBusiness, getDetailsForIFSC } from '../services/utils';
import { analyticsTrack } from '../../../../services/tracking/segment';
import { useApp } from 'v2/context/App';

interface BankDetailsProps {
  isFormLocked?: boolean;
}

const BankDetails: React.FC<BankDetailsProps> = ({ isFormLocked }) => {
  const { data, postData } = useActivation();
  const { user } = useApp();
  const bankAndCompanyDetails = data.bank_and_company_details;
  const businessOverviewDetails = data.business_overview;
  const hasGSTIN = useActivationFormState((state) => state.has_gstin);
  const setHasGSTIN = useActivationFormState((state) => state.setHasGSTIN);
  const setBankAndCompanyDetailsCompleted = useActivationFormState(
    (state) => state.setBankAndCompanyDetailsCompleted,
  );
  const [isBlurCalled, setIsBlurCalled] = useState(false);
  const [branchIfscInfo, setBranchIfscInfo] = useState<string>('');

  const handleSubmit = (updatedDetails) => {
    const isComplete = isTabComplete(
      {
        ...data,
        bank_and_company_details: { ...bankAndCompanyDetails, ...updatedDetails },
        hasGSTIN,
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

  useEffect(() => {
    // when checkbox is unchecked then bankAndCompanyDetailsCompleted set to false
    if (!hasGSTIN && data.gstin === '' && !data.gstin.length) {
      setBankAndCompanyDetailsCompleted(hasGSTIN);
    }
  }, [hasGSTIN]);

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
          bank_account_name: Yup.string()
            .required('Bank Account Name is a required field')
            .nullable(),
          bank_account_number: Yup.string()
            .required('Bank Account Number is a required field')
            .nullable(),
          bank_branch_ifsc: Yup.string().required('IFSC is a required field').nullable(),
          company_cin: Yup.lazy(() => {
            if (CIN_BusinessTypes.includes(Number(businessOverviewDetails.business_type.value))) {
              return Yup.string()
                .trim()
                .length(21, 'CIN must be 21 characters')
                .matches(/^([a-z]{3}-\d{4}|[ul]\d{5}[a-z]{2}\d{4}[a-z]{3}\d{6})$/i, {
                  message: 'Invalid Format',
                  excludeEmptyString: true,
                })
                .required('Company CIN is a required field')
                .nullable();
            }
            return Yup.string()
              .trim()
              .matches(/^([a-z]{3}-\d{4}|[ul]\d{5}[a-z]{2}\d{4}[a-z]{3}\d{6})$/i, {
                message: 'Invalid Format',
                excludeEmptyString: true,
              })
              .required('LLPIN is a required field')
              .nullable();
          }),
          gstin: Yup.lazy(() => {
            if (!hasGSTIN) {
              return Yup.string()
                .trim()
                .length(15, 'Please provide valid GSTIN')
                .required('GSTIN is a required field')
                .nullable();
            }
            return Yup.string().trim().nullable();
          }),
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
          <FormSection
            title="Bank Details"
            subtitle="We will be depositing a small amount in this account to verify your bank details"
            disabled={isFormLocked}
          >
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
                    actionName: 'bank account name success',
                    screen: 'home page',
                    properties: {
                      userId: user.id,
                    },
                  });
                }}
                disabled={isFormLocked}
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
                onBlur={() => {
                  analyticsTrack({
                    objectName: 'SignUp',
                    actionName: 'bank account number success',
                    screen: 'home page',
                    properties: {
                      userId: user.id,
                    },
                  });
                }}
                disabled={isFormLocked}
              />
            </Field>
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
                helpText={branchIfscInfo}
                value={formikProps.values.bank_branch_ifsc}
                errorText={
                  formikProps.touched.bank_branch_ifsc && formikProps.errors.bank_branch_ifsc
                }
                onBlur={() => {
                  analyticsTrack({
                    objectName: 'SignUp',
                    actionName: 'bank branch ifsc success',
                    screen: 'home page',
                    properties: {
                      userId: user.id,
                    },
                  });
                }}
                disabled={isFormLocked}
              />
            </Field>
          </FormSection>

          {!isUnregisteredBusiness(businessOverviewDetails.business_type.value) ? (
            <FormSection title="Company Details" last disabled={isFormLocked}>
              <Field visible={isVisible('company_cin', data)}>
                <TextInput
                  width="auto"
                  name="company_cin"
                  label={getLabel('company_cin', data)}
                  value={formikProps.values.company_cin}
                  errorText={formikProps.touched.company_cin && formikProps.errors.company_cin}
                  onBlur={() => {
                    analyticsTrack({
                      objectName: 'SignUp',
                      actionName: 'Company Cin success',
                      screen: 'home page',
                      properties: {
                        userId: user.id,
                      },
                    });
                  }}
                  disabled={isFormLocked}
                />
              </Field>
              {!hasGSTIN ? (
                <Field last>
                  <TextInput
                    width="auto"
                    name="gstin"
                    label="GST Identification Number (GSTIN)"
                    helpText="Should match either of your registered address or operational address"
                    value={formikProps.values.gstin}
                    errorText={formikProps.touched.gstin && formikProps.errors.gstin}
                    onBlur={() => {
                      analyticsTrack({
                        objectName: 'SignUp',
                        actionName: 'Gst Identification Number success',
                        screen: 'home page',
                        properties: {
                          userId: user.id,
                        },
                      });
                    }}
                    disabled={isFormLocked}
                  />
                </Field>
              ) : null}
              {!isUnregisteredBusiness(businessOverviewDetails.business_type.value) ? (
                <Space margin={[1.75, 0, 0, 0]}>
                  <View>
                    <Checkbox
                      name="no_gstin"
                      title="I don't have a GSTIN"
                      defaultChecked={hasGSTIN}
                      onChange={(value) => {
                        setHasGSTIN(value);
                        if (value) {
                          formikProps.setFieldTouched('gstin');
                          formikProps.setFieldValue('gstin', '');
                        }
                        setIsBlurCalled(true);
                        analyticsTrack({
                          objectName: 'SignUp',
                          actionName: "I don't have a GSTIN checkbox success",
                          screen: 'home page',
                          properties: {
                            userId: user.id,
                          },
                        });
                      }}
                    />
                  </View>
                </Space>
              ) : null}
            </FormSection>
          ) : null}
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
