import React, { useState } from 'react';
import { Formik, Form } from 'formik';
import * as Yup from 'yup';
import TextInput from '@razorpay/blade-old/src/atoms/TextInput';
import { FormSection, Field, GetTouchedFields } from '../Form';
import { useActivationFormState, isTabComplete } from '../context/store';
import useActivation, { getRequestData } from '../hooks/useActivation';
import usePartnerActivation from '../hooks/usePartnerActivation';
import { useApp } from 'common/context/App';
import EmailVerify from '../EmailVerify';

const contactDetailsSchema = Yup.object().shape({
  contact_name: Yup.string()
    .trim()
    .matches(/^[a-zA-Z\s]+$/, {
      message: 'Name may only contain alphabets and spaces.',
      excludeEmptyString: true,
    })
    .min(4, 'Contact Name should have at least 4 characters.')
    .required('Contact Name is a required field.')
    .nullable(),
  contact_email: Yup.string()
    .email('Please enter a valid email id.')
    .required('Contact Email is a required field.')
    .nullable(),
  contact_mobile: Yup.string()
    .trim()
    .length(10, 'Please enter a valid 10-digit mobile number.')
    .required('Contact Mobile is a required field')
    .nullable(),
});

interface IContactDetailsProps {
  isFormLocked?: boolean;
}

const ContactDetails: React.FC<IContactDetailsProps> = ({ isFormLocked }) => {
  const { data, postData } = useActivation();
  const { user, experiments } = useApp();
  const { getFieldStatus } = usePartnerActivation();
  const contactDetails = data.contact_details;
  const [isBlurCalled, setIsBlurCalled] = useState(false);
  const setContactDetailsCompleted = useActivationFormState(
    (state) => state.setContactDetailsCompleted,
  );
  const isEmailVerificationRequired: boolean =
    (experiments.isEmailMandatoryOnL1 || experiments.isEmailNonMandatoryOnL1) &&
    !user.user?.signup_via_email;

  const handleBlur = (e, formikProps) => {
    formikProps.handleBlur(e);
    setIsBlurCalled(true);
  };

  const handleSubmit = (updatedDetails) => {
    if (isEmailVerificationRequired && (!!updatedDetails?.contact_email || !!updatedDetails?.otp)) {
      delete updatedDetails.contact_email;
      delete updatedDetails.otp;
    }
    const isComplete = isTabComplete(
      {
        ...data,
        contact_details: { ...contactDetails, ...updatedDetails },
        isEmailNonMandatoryOnL1: experiments.isEmailNonMandatoryOnL1,
      },
      'contact_details',
    );
    setContactDetailsCompleted(isComplete);
    const reqData = getRequestData(contactDetails, updatedDetails);
    if (Object.keys(reqData).length) {
      postData(reqData);
    }
  };

  return (
    <Formik
      initialValues={{
        contact_name: contactDetails.contact_name.value,
        contact_email: contactDetails.contact_email.value,
        contact_mobile: contactDetails.contact_mobile.value,
      }}
      validationSchema={contactDetailsSchema}
      validateOnMount={true}
      onSubmit={() => {
        console.log('onSubmit');
      }}
    >
      {(formikProps) => (
        <Form onChange={formikProps.handleChange} onSubmit={(e) => e.preventDefault()}>
          <FormSection title="Contact Details" last>
            <Field>
              <TextInput
                width="auto"
                name="contact_name"
                label="Contact Name"
                value={formikProps.values.contact_name}
                errorText={formikProps.touched.contact_name && formikProps.errors.contact_name}
                disabled={isFormLocked || getFieldStatus('contact_name').isDisabled}
                helpText={getFieldStatus('contact_name').description}
                onChange={(value) => {
                  formikProps.setFieldTouched('contact_name');
                  formikProps.setFieldValue('contact_name', value);
                }}
                onBlur={(e) => handleBlur(e, formikProps)}
              />
            </Field>
            <Field>
              <TextInput
                type="number"
                width="auto"
                name="contact_mobile"
                label="Contact Number"
                value={formikProps.values.contact_mobile}
                errorText={formikProps.touched.contact_mobile && formikProps.errors.contact_mobile}
                disabled={
                  isFormLocked ||
                  getFieldStatus('contact_mobile').isDisabled ||
                  (user?.user?.contact_mobile_verified && isEmailVerificationRequired)
                }
                helpText={getFieldStatus('contact_mobile').description}
                onChange={(value) => {
                  formikProps.setFieldTouched('contact_mobile');
                  formikProps.setFieldValue('contact_mobile', value);
                }}
                onBlur={(e) => handleBlur(e, formikProps)}
              />
            </Field>
            {isEmailVerificationRequired ? (
              <EmailVerify
                contactName={formikProps.values.contact_name}
                isFormLocked={isFormLocked}
              />
            ) : (
              <Field last>
                <TextInput
                  width="auto"
                  name="contact_email"
                  label="Contact Email"
                  helpText={
                    getFieldStatus('contact_name').description ||
                    'All important communications and account updates will be sent to this email ID'
                  }
                  value={formikProps.values.contact_email}
                  errorText={formikProps.touched.contact_email && formikProps.errors.contact_email}
                  disabled={
                    isFormLocked ||
                    getFieldStatus('contact_email').isDisabled ||
                    (!!user.user?.signup_via_email &&
                      (experiments.isEmailMandatoryOnL1 || experiments.isEmailNonMandatoryOnL1))
                  }
                  onChange={(value) => {
                    formikProps.setFieldTouched('contact_email');
                    formikProps.setFieldValue('contact_email', value);
                  }}
                  iconRight={
                    !!user.user?.signup_via_email &&
                    (experiments.isEmailMandatoryOnL1 || experiments.isEmailNonMandatoryOnL1)
                      ? 'check'
                      : ''
                  }
                  onBlur={(e) => handleBlur(e, formikProps)}
                />
              </Field>
            )}
          </FormSection>
          <GetTouchedFields
            handleSubmit={handleSubmit}
            isBlurCalled={isBlurCalled}
            setIsBlurCalled={setIsBlurCalled}
            tabName="Contact Details"
          />
        </Form>
      )}
    </Formik>
  );
};

export default ContactDetails;
