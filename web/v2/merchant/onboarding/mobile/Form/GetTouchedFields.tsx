import { useEffect } from 'react';
import { useFormikContext } from 'formik';

const getTouchedFields = (formikProps) => {
  const { touched, values, errors } = formikProps;
  const touchedValues = Object.keys(touched).map((key) => ({
    [key]: { value: values[key], error: errors[key] },
  }));
  return touchedValues;
};

interface GetTouchedFieldsPropsT {
  handleSubmit: (updatedDetails) => void;
  isBlurCalled: boolean;
  setIsBlurCalled: (value) => void;
}

const GetTouchedFields: React.FC<GetTouchedFieldsPropsT> = ({
  handleSubmit,
  isBlurCalled,
  setIsBlurCalled,
}) => {
  const formikContext = useFormikContext();
  useEffect(() => {
    if (isBlurCalled) {
      const touchedFields = getTouchedFields(formikContext);
      const updatedDetails = touchedFields.reduce((prev, cur) => {
        return { ...prev, ...cur };
      }, {});
      handleSubmit(updatedDetails);
      setIsBlurCalled(false);
    }
  }, [isBlurCalled, setIsBlurCalled, formikContext, handleSubmit]);
  return null;
};

export default GetTouchedFields;
