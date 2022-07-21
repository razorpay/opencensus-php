import { useContext } from 'react';
import { Formik } from 'formik';
import { formContext } from './FormContext';
import { getFormSchema } from './utils';
import ModalContainer from './ModalContainer';

const FormWrapper = (props) => {
  const { initialValues, isPurposecodeSpecial } = useContext(formContext);

  return (
    <Formik
      initialValues={initialValues}
      enableReinitialize={true}
      validationSchema={getFormSchema(isPurposecodeSpecial)}
    >
      <ModalContainer {...props} />
    </Formik>
  );
};

export default FormWrapper;
