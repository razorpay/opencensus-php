import React, { useState } from 'react';
import { Formik } from 'formik';
import * as Yup from 'yup';
import TextInput from '@razorpay/blade-old/src/atoms/TextInput';
import { FormSection, Field, GetTouchedFields } from '../Form';
import { useActivationFormState, isTabComplete } from '../context/store';
import useActivation, { getRequestData } from '../hooks/useActivation';
import usePartnerActivation from '../hooks/usePartnerActivation';

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

interface ContactDetailsProps {
  isFormLocked?: boolean;
}

const ContactDetails: React.FC<ContactDetailsProps> = ({ isFormLocked }) => {
  const { data, postData } = useActivation();
  const { getFieldStatus } = usePartnerActivation();
  const contactDetails = data.contact_details;
  const [isBlurCalled, setIsBlurCalled] = useState(false);
  const setContactDetailsCompleted = useActivationFormState(
    (state) => state.setContactDetailsCompleted,
  );

  const handleBlur = (e, formikProps) => {
    formikProps.handleBlur(e);
    setIsBlurCalled(true);
  };

  const handleSubmit = (updatedDetails) => {
    const isComplete = isTabComplete(
      { ...data, contact_details: { ...contactDetails, ...updatedDetails } },
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
        <form onChange={formikProps.handleChange} onBlur={(e) => handleBlur(e, formikProps)}>
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
              />
            </Field>
            <Field>
              <TextInput
                width="auto"
                name="contact_email"
                label="Contact Email"
                helpText={
                  getFieldStatus('contact_name').description ||
                  'We will reach out at this email id in case of any account related issue'
                }
                value={formikProps.values.contact_email}
                errorText={formikProps.touched.contact_email && formikProps.errors.contact_email}
                disabled={isFormLocked || getFieldStatus('contact_email').isDisabled}
              />
            </Field>
            <Field last>
              <TextInput
                type="number"
                width="auto"
                name="contact_mobile"
                label="Contact Number"
                value={formikProps.values.contact_mobile}
                errorText={formikProps.touched.contact_mobile && formikProps.errors.contact_mobile}
                disabled={isFormLocked || getFieldStatus('contact_mobile').isDisabled}
                helpText={getFieldStatus('contact_mobile').description}
              />
            </Field>
          </FormSection>
          <GetTouchedFields
            handleSubmit={handleSubmit}
            isBlurCalled={isBlurCalled}
            setIsBlurCalled={setIsBlurCalled}
            tabName="Contact Details"
          />
        </form>
      )}
    </Formik>
  );
};

export default ContactDetails;
