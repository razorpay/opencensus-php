import React, { useState } from 'react';
import { Formik } from 'formik';
import TextInput from '@razorpay/blade/src/atoms/TextInput';
import { Field, GetTouchedFields } from '../Form';
import { useActivationFormState, isTabComplete } from '../context/store';
import useActivation, { getRequestData } from '../hooks/useActivation';

const ContactDetails: React.FC = () => {
  const { data, postData } = useActivation();
  const documents = data.documents;

  const [isBlurCalled, setIsBlurCalled] = useState(false);

  const setDocumentUploadCompleted = useActivationFormState(
    (state) => state.setDocumentUploadCompleted,
  );

  const handleBlur = (e, formikProps) => {
    formikProps.handleBlur(e);
    setIsBlurCalled(true);
  };

  const handleSubmit = (updatedDetails) => {
    const isComplete = isTabComplete(
      { ...data, documents: { ...documents, ...updatedDetails } },
      'documents',
    );
    setDocumentUploadCompleted(isComplete);
    const reqData = getRequestData(documents, updatedDetails);
    if (Object.keys(reqData).length) {
      postData(reqData);
    }
  };

  return (
    <Formik
      initialValues={{
        shop_establishment_number: documents.shop_establishment_number.value,
      }}
      onSubmit={() => {
        console.log('onSubmit');
      }}
    >
      {(formikProps) => (
        <form onChange={formikProps.handleChange} onBlur={(e) => handleBlur(e, formikProps)}>
          <Field>
            <TextInput
              width="auto"
              name="shop_establishment_number"
              label="Shop Establishment Number"
              value={formikProps.values.shop_establishment_number}
              errorText={
                formikProps.touched.shop_establishment_number &&
                formikProps.errors.shop_establishment_number
              }
            />
          </Field>
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

export default ContactDetails;
