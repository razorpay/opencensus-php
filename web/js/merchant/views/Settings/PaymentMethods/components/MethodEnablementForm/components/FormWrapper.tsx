import React from 'react';
import { Formik } from 'formik';
import { connect } from 'react-redux';

import { FormWrapperProps } from 'merchant/views/Settings/PaymentMethods/components/MethodEnablementForm/types';
import useFormContext from 'merchant/views/Settings/PaymentMethods/components/MethodEnablementForm/hooks/useFormContext';
import { getFormSchema } from 'merchant/views/Settings/PaymentMethods/components/MethodEnablementForm/utils';

import ModalContainer from './ModalContainer';

const FormWrapper = (props: FormWrapperProps) => {
  const { initialValues } = useFormContext();
  const user = props.user;

  return (
    <Formik
      initialValues={initialValues}
      enableReinitialize={true}
      validationSchema={getFormSchema(user.business_type as string)}
      onSubmit={() => {}}
    >
      <ModalContainer {...props} />
    </Formik>
  );
};

const mapStateToProps = (state) => ({
  user: state.session.user,
});

export default connect(mapStateToProps)(FormWrapper);
