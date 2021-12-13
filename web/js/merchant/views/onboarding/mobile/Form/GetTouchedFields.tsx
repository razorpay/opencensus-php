import { useEffect } from 'react';
import { useFormikContext } from 'formik';
import useTrackEvents from 'merchant/hooks/useTrackEvents';
import { capitalize } from 'common/utils/rzp-utils';

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
  setIsBlurCalled: (value: boolean) => void;
  tabName?: string;
}

const GetTouchedFields: React.FC<GetTouchedFieldsPropsT> = ({
  handleSubmit,
  isBlurCalled,
  setIsBlurCalled,
  tabName,
}) => {
  const formikContext = useFormikContext();
  const trackEvents = useTrackEvents();
  useEffect(() => {
    if (isBlurCalled) {
      const touchedFields = getTouchedFields(formikContext);
      if (tabName) {
        touchedFields.forEach((key) => {
          const label = Object.keys(key)[0];
          const fieldLabel = capitalize(label.split('_').join(' '));
          const error = key[label].error;
          if (error) {
            trackEvents({
              objectName: 'Form Field',
              actionName: 'Validation',
              screen: 'home page',
              eventAction: 'failed',
              properties: {
                error,
                fieldLabel,
                tab: tabName,
              },
            });
          }
        });
      }

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
