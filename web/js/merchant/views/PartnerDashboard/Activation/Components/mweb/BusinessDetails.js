import React, { useState, useEffect } from 'react';
import { Formik } from 'formik';
import Text from '@razorpay/blade-old/src/atoms/Text';
import * as Yup from 'yup';
import TextInput from '@razorpay/blade-old/src/atoms/TextInput';
import View from '@razorpay/blade-old/src/atoms/View';
import Space from '@razorpay/blade-old/src/atoms/Space';
import Checkbox from '@razorpay/blade-old/src/atoms/Checkbox';
import { FormSection, Field, GetTouchedFields } from 'merchant/views/onboarding/mobile/Form';
import BusinessType from 'merchant/views/onboarding/mobile/Fields/BusinessType';
import {
  isUnregisteredBusiness,
  displayCompanyPAN,
  PROPRIETORSHIP,
} from '../../utils/ActivationUtils';
import { getDetailsForIFSC } from 'common/utils/rzp-utils';
import { useActivationFormState, isTabComplete } from '../../Hooks/store';
import useActivation, { getRequestData } from '../../Hooks/useActivation';

const businessDetailsSchema = ({ hasGSTIN }) =>
  Yup.object().shape({
    business_type: Yup.string().nullable().required('Business Type is a required field').nullable(),
    company_pan: Yup.string()
      .trim()
      .length(10, 'PAN card must be 10 characters')
      .matches(/^[a-zA-z]{5}\d{4}[a-zA-Z]{1}$/, {
        message: 'Invalid PAN Card',
        excludeEmptyString: true,
      })
      .test('companypan', 'Invalid PAN format.', (value) => {
        if (!value || value.length <= 3) {
          return true;
        }
        return ['C', 'H', 'F', 'A', 'T', 'B', 'J', 'G', 'L'].indexOf(value[3].toUpperCase()) !== -1;
      })
      .required('Company PAN is a required field')
      .nullable(),
    business_name: Yup.string().required('Business Name is a required field').nullable(),
    promoter_pan: Yup.string()
      .trim()
      .length(10, 'PAN must be 10 characters')
      .matches(/^[a-zA-z]{5}\d{4}[a-zA-Z]{1}$/, {
        message: 'Invalid PAN Card.',
        excludeEmptyString: true,
      })
      .test('promoter_pan', 'Invalid PAN Card', (value) => {
        if (!value || value.length <= 3) {
          return true;
        }
        return value[3].toLowerCase() === 'p';
      })
      .required('Promoter PAN is a required field')
      .nullable(),
    promoter_pan_name: Yup.string().required('Promoter PAN Name is a required field').nullable(),
    gstin: Yup.string().when('hasGstin', {
      is: hasGSTIN,
      then: Yup.string()
        .trim()
        .length(15, 'Please provide valid GSTIN')
        .required('GSTIN is a required field')
        .nullable(),
      otherwise: Yup.string().trim().nullable(),
    }),
    bank_account_name: Yup.string().required('Bank Account Name is a required field').nullable(),
    bank_account_number: Yup.string()
      .required('Bank Account Number is a required field')
      .nullable(),
    re_enter_bank_account_number: Yup.string()
      .oneOf([Yup.ref('bank_account_number')], "Account number don't match!")
      .required('Bank Account Number is a required field')
      .nullable(),
    bank_branch_ifsc: Yup.string().required('IFSC is a required field').nullable(),
  });

const BusinessDetails = ({ isFormLocked, isFormSubmitted, tracking, partnerID }) => {
  const { data, postData } = useActivation();
  const { business_type: businessType } = data;
  const [isBlurCalled, setIsBlurCalled] = useState(false);
  const [branchIfscInfo, setBranchIfscInfo] = useState('');
  const [currentBusinessType, setCurrentBusinessType] = useState(businessType);
  const businessDetails = data.business_details;
  const commonLockedFields = data?.lock_common_fields || [];

  const hasGSTIN = useActivationFormState((state) => state.has_gstin);
  const setHasGSTIN = useActivationFormState((state) => state.setHasGSTIN);

  const setBusinessDetailsCompleted = useActivationFormState(
    (state) => state.setBusinessDetailsCompleted,
  );

  const handleBlur = (e, formikProps) => {
    formikProps.handleBlur(e);
    setIsBlurCalled(true);
  };

  const showUnregisteredText = () => {
    return isUnregisteredBusiness(currentBusinessType) ? (
      <Text size="xxsmall" color="shade.960" align="justify">
        Unregistered business type is for freelancers or small businesses who have not yet
        registered as a company. Don't choose this option if your business is already registered.
        Business type cannot be changed once submitted.
      </Text>
    ) : null;
  };

  const fetchDefaultIfscInfo = () => {
    if (businessDetails.bank_branch_ifsc.value) {
      // eslint-disable-next-line babel/no-unused-expressions
      getDetailsForIFSC(businessDetails.bank_branch_ifsc.value)?.then((info) => {
        const defaultBranchIfscInfo = info ? `${info.Bank}, ${info.Branch}` : '';
        setBranchIfscInfo(defaultBranchIfscInfo);
      });
    }
  };

  useEffect(() => {
    tracking.trackEvent(
      window.rzpQ.onbr().interaction('partnerships.partner_KYC.form_open', {
        partnerID,
        section: 'Business Details',
      }),
    );
    fetchDefaultIfscInfo();
  }, []);

  const handleSubmit = (updatedDetails) => {
    const bankAccountNumber = updatedDetails.bank_account_number?.value;
    const reAccountNumber = updatedDetails.re_enter_bank_account_number?.value;
    const isAccountNumberValid =
      (bankAccountNumber ? bankAccountNumber : data.bank_account_number) === reAccountNumber;

    if (!isAccountNumberValid && !!updatedDetails.bank_account_number) {
      delete updatedDetails.bank_account_number;
    }
    if (!!updatedDetails.re_enter_bank_account_number) {
      delete updatedDetails.re_enter_bank_account_number;
    }

    if (!hasGSTIN) {
      delete updatedDetails.gstin;
    }

    const isComplete = isTabComplete(
      {
        ...data,
        business_details: { ...businessDetails, ...updatedDetails },
        hasGSTIN,
      },
      'business_details',
    );
    setBusinessDetailsCompleted(isComplete);

    const reqData = getRequestData(businessDetails, updatedDetails);
    if (Object.keys(reqData).length) {
      postData(reqData);
    }
  };

  return (
    <Formik
      initialValues={{
        business_type: businessDetails.business_type.value,
        company_pan: businessDetails.company_pan.value,
        business_name: businessDetails.business_name.value,
        promoter_pan: businessDetails.promoter_pan.value,
        promoter_pan_name: businessDetails.promoter_pan_name.value,
        bank_account_name: businessDetails.bank_account_name.value,
        bank_account_number: businessDetails.bank_account_number.value,
        re_enter_bank_account_number: businessDetails.bank_account_number.value,
        bank_branch_ifsc: businessDetails.bank_branch_ifsc.value,
        gstin: businessDetails.gstin.value,
      }}
      validationSchema={businessDetailsSchema({ hasGSTIN })}
      validateOnMount={true}
      onSubmit={() => {
        console.log('onSubmit');
      }}
    >
      {(formikProps) => (
        <form onChange={formikProps.handleChange} onBlur={(e) => handleBlur(e, formikProps)}>
          <FormSection title="About Your Business" last>
            <Field>
              <BusinessType
                value={formikProps.values.business_type}
                errorText={formikProps.touched.business_type && formikProps.errors.business_type}
                onChange={(value) => {
                  setCurrentBusinessType(Number(value));
                  formikProps.setFieldTouched('business_type');
                  formikProps.setFieldValue('business_type', value);
                  setIsBlurCalled(true);
                }}
                disabled={isFormLocked || commonLockedFields.includes('business_type')}
              />
              {showUnregisteredText()}
            </Field>
          </FormSection>
          <FormSection
            title="PAN Details"
            subtitle="These details will be verified with the government database"
          >
            <Field visible={displayCompanyPAN(currentBusinessType)}>
              <TextInput
                width="auto"
                name="company_pan"
                label="Business PAN"
                helpText="PAN of the Company"
                value={
                  formikProps.values.company_pan && formikProps.values.company_pan.toUpperCase()
                }
                errorText={formikProps.touched.company_pan && formikProps.errors.company_pan}
                disabled={isFormLocked || commonLockedFields.includes('company_pan')}
                autoCapitalize="characters"
              />
            </Field>
            <Field visible={!isUnregisteredBusiness(currentBusinessType)}>
              <TextInput
                width="auto"
                name="business_name"
                label="Business Name"
                helpText="As mentioned in the PAN"
                value={formikProps.values.business_name}
                errorText={formikProps.touched.business_name && formikProps.errors.business_name}
                disabled={isFormLocked || commonLockedFields.includes('business_name')}
              />
            </Field>
            <Field visible={!displayCompanyPAN(currentBusinessType)}>
              <TextInput
                width="auto"
                name="promoter_pan"
                label="Business Owner's PAN"
                value={
                  formikProps.values.promoter_pan && formikProps.values.promoter_pan.toUpperCase()
                }
                errorText={formikProps.touched.promoter_pan && formikProps.errors.promoter_pan}
                disabled={isFormLocked || commonLockedFields.includes('promoter_pan')}
                autoCapitalize="characters"
              />
            </Field>
            <Field visible={!displayCompanyPAN(currentBusinessType)}>
              <TextInput
                width="auto"
                name="promoter_pan_name"
                label="Business Owner's Name"
                helpText="As mentioned in the PAN"
                value={formikProps.values.promoter_pan_name}
                errorText={
                  formikProps.touched.promoter_pan_name && formikProps.errors.promoter_pan_name
                }
                disabled={isFormLocked || commonLockedFields.includes('promoter_pan_name')}
              />
            </Field>
          </FormSection>
          <FormSection title="BANK Details" last>
            <Field>
              <TextInput
                width="auto"
                name="bank_account_name"
                label="Beneficiary Name"
                value={formikProps.values.bank_account_name}
                errorText={
                  formikProps.touched.bank_account_name && formikProps.errors.bank_account_name
                }
                disabled={isFormLocked || commonLockedFields.includes('bank_account_name')}
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
                disabled={isFormLocked || commonLockedFields.includes('bank_account_number')}
              />
            </Field>
            {!isFormSubmitted ? (
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
                  disabled={isFormLocked || commonLockedFields.includes('bank_account_number')}
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
                    const info = await getDetailsForIFSC(value);
                    if (info) {
                      setBranchIfscInfo(`${info.Bank}, ${info.Branch}`);
                    }
                  }
                }}
                helpText={branchIfscInfo}
                value={formikProps.values.bank_branch_ifsc}
                errorText={
                  formikProps.touched.bank_branch_ifsc && formikProps.errors.bank_branch_ifsc
                }
                disabled={isFormLocked || commonLockedFields.includes('bank_branch_ifsc')}
              />
            </Field>
          </FormSection>
          {!isUnregisteredBusiness(currentBusinessType) && (
            <FormSection
              title="Company Details"
              last
              disabled={isFormLocked || commonLockedFields.includes('gstin')}
            >
              <Field last>
                <TextInput
                  width="auto"
                  name="gstin"
                  label="GST Identification Number (GSTIN)"
                  helpText="Enter GSTIN & get reviewed faster."
                  value={formikProps.values.gstin}
                  errorText={formikProps.touched.gstin && formikProps.errors.gstin}
                  disabled={isFormLocked || !hasGSTIN}
                />
              </Field>
              <>
                <Space margin={[1.75, 0, 0, 0]}>
                  <View>
                    <Checkbox
                      name="no_gstin"
                      title="I don't have a GSTIN"
                      defaultChecked={!hasGSTIN}
                      onChange={(value) => setHasGSTIN(!value)}
                    />
                  </View>
                </Space>
                {hasGSTIN && Number(currentBusinessType) === PROPRIETORSHIP && (
                  <Text size="xsmall" color="negative.900">
                    Please note that skipping GSTIN might lead to delay in your account review by
                    upto two weeks, usually it takes 3-4 days
                  </Text>
                )}
              </>
            </FormSection>
          )}

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

export default BusinessDetails;
