export const handleEventForInputError = (formikProps, field, eventSource) => {
  if (formikProps.errors[field]) {
    eventSource.trackInputError(formikProps.errors[field], field);
  }
};
