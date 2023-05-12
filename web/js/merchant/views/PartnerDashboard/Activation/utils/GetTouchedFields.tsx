import { useEffect } from 'react';
import { useFormikContext } from 'formik';
import useActivation from 'merchant/views/PartnerDashboard/Activation/Hooks/useActivation';

const getTouchedFields = (formikProps) => {
  const { touched, values, errors } = formikProps;
  const touchedValues = Object.keys(touched).map((key) => ({
    [key]: { value: values[key], error: errors[key] },
  }));
  return touchedValues;
};

interface GetTouchedFieldsProps {
  handleSubmit: (updatedDetails) => void;
  isBlurCalled: boolean;
  setIsBlurCalled: (value: boolean) => void;
}

const GetTouchedFields = ({
  handleSubmit,
  isBlurCalled,
  setIsBlurCalled,
}: GetTouchedFieldsProps): JSX.Element | null => {
  const formikContext = useFormikContext();
  useActivation();
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
