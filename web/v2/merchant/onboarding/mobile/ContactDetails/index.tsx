import React from 'react';
import { Formik } from 'formik';
import * as Yup from 'yup';
import TextInput from '@razorpay/blade/src/atoms/TextInput';
import { FormSection, Field } from '../Form';
import { useActivationFormState, isTabComplete } from '../context/store';
import useActivation from '../hooks/useActivation';

const contactDetailsSchema = Yup.object().shape({
  contact_name: Yup.string()
    .trim()
    .matches(/^[a-zA-Z\s]+$/, {
      message: 'Name may only contain alphabets and spaces.',
      excludeEmptyString: true,
    })
    .min(4, 'Contact Name should have at least 4 characters.')
    .required('Contact Name is a required field.'),
  contact_email: Yup.string()
    .email('Please enter a valid email id.')
    .required('Contact Email is a required field.'),
  contact_mobile: Yup.string()
    .trim()
    .length(10, 'Please enter a valid 10-digit mobile number.')
    .required('Contact Mobile is a required field'),
});

const ContactDetails: React.FC = () => {
  const { data, postData } = useActivation();
  const contactDetails = data.contact_details;
  const setContactDetailsCompleted = useActivationFormState(
    (state) => state.setContactDetailsCompleted,
  );
  const handleBlur = (e, formikProps) => {
    formikProps.handleBlur(e);
    const updatedContactDetails = {
      contact_name: {
        value: formikProps.values.contact_name,
        error: formikProps.errors.contact_name,
      },
      contact_email: {
        value: formikProps.values.contact_email,
        error: formikProps.errors.contact_email,
      },
      contact_mobile: {
        value: formikProps.values.contact_mobile,
        error: formikProps.errors.contact_mobile,
      },
    };
    const isComplete = isTabComplete(
      { ...data, contact_details: updatedContactDetails },
      'contact_details',
    );
    setContactDetailsCompleted(isComplete);
    if (isComplete) {
      postData(updatedContactDetails);
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
                errorText={formikProps.errors.contact_name}
              />
            </Field>
            <Field>
              <TextInput
                width="auto"
                name="contact_email"
                label="Contact Email"
                helpText="We will reach out at this email id in case of any account related issue"
                value={formikProps.values.contact_email}
                errorText={formikProps.errors.contact_email}
              />
            </Field>
            <Field last>
              <TextInput
                type="number"
                width="auto"
                name="contact_mobile"
                label="Contact Number"
                value={formikProps.values.contact_mobile}
                errorText={formikProps.errors.contact_mobile}
              />
            </Field>
          </FormSection>
        </form>
      )}
    </Formik>
  );
};

export default ContactDetails;
