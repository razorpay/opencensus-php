import { useContext } from 'react';
import { useFormikContext } from 'formik';
import { formContext } from './FormContext';

//Helper functions and constants
import { saveDocument } from './services';
import { MERCHANT_INFO, SPECIAL_PURPOSE_CODES, detailsFormFields } from './constants';
import { disableFutureDates } from './utils';

//Components
import Input from 'common/new-ui/Input';

const Details = ({ showNotification }) => {
  const {
    setIsPurposecodeSpecial,
    isPurposecodeSpecial,
    documents,
    updateDocuments,
    isUneditable,
    purposeCode,
  } = useContext(formContext);
  const { values, touched, errors, handleChange, handleBlur, setFieldValue, validateForm } =
    useFormikContext();

  /**
   * 1. calls post document api to save the file
   * 2. updates document state with key - docId and value as response from api
   * 3. saves the docId in formik for the tag passed
   * @param {*} file - file we get from upload component
   * @param {*} progressTracker - to track upload progress
   * @param {*} tag - key to refer to the values in formik
   * @returns {object} - returns error if failed otherwise null
   */
  const handleFileUpload = async (file, progressTracker, tag) => {
    try {
      const response = await saveDocument(file, progressTracker, tag);
      if (response?.id) {
        setFieldValue(`${MERCHANT_INFO}.${tag}`, response.id);
        updateDocuments(response.id, response);
      }
    } catch (error) {
      showNotification({
        type: 'error',
        message: error?.errors,
      });
      return error;
    }
    return null;
  };

  /**
   * 1. Updates the documents state
   * 2. Updates state in formik
   * @param {*} tag - key to refer to the values in formik
   */
  const handleFileRemove = (tag) => {
    setFieldValue(`${MERCHANT_INFO}.${tag}`, '');
    updateDocuments(values?.[MERCHANT_INFO]?.[tag], null);
  };

  /**
   * this checks if the selected purpose code requires an iec code input
   * if yes then it sets the state for it
   * @param {*} tag - key to refer to the values in formik
   */
  const handlePurposecodeChange = (tag, { option: { label } }) => {
    const purposeCode = label?.split('-')?.[0];
    const isSpecial = SPECIAL_PURPOSE_CODES.includes(purposeCode);
    setIsPurposecodeSpecial(isSpecial);
    setFieldValue(`${MERCHANT_INFO}.${tag}`, purposeCode);
    //iec code is conditionally added, so not getting validated automatically
    setTimeout(() => validateForm());
  };

  const handleDateChange = (tag, date) => {
    setFieldValue(`${MERCHANT_INFO}.${tag}`, date?.format('YYYY-MM-DD'));
  };

  const getFileName = (tag) => {
    const documentId = values?.[MERCHANT_INFO]?.[tag];
    const document = documents?.[documentId];
    return document?.display_name;
  };

  //custom component for powerselect dropdown
  const formatOptionLabel = ({ option: { label, description } }) => (
    <div className="option-parent">
      <div className="label-style">{label}</div>
      <div className="description-style">{description}</div>
    </div>
  );

  const getFieldProps = ({ key, label, placeholder, options, type, accept }) => {
    let props = {
      label,
      placeholder,
      name: `${MERCHANT_INFO}.${key}`,
      value: values?.[MERCHANT_INFO]?.[key],
      mature: touched?.[MERCHANT_INFO]?.[key],
      propagatedError: touched?.[MERCHANT_INFO]?.[key] && errors?.[MERCHANT_INFO]?.[key],
      onChange: handleChange,
      onBlur: handleBlur,
      disabled: isUneditable?.[key],
    };

    switch (type) {
      case 'File':
        props = {
          ...props,
          _accept: accept,
          defaultValue: getFileName(key),
          fileName: getFileName(key),
          onChange: (file, progressTracker) => handleFileUpload(file, progressTracker, key),
          onCloseClick: () => handleFileRemove(key),
        };
        break;
      case 'ReactPowerSelect':
        props = {
          ...props,
          options: purposeCode,
          optionComponent: formatOptionLabel,
          onChange: (data) => handlePurposecodeChange(key, data),
          selectedOptionLabelPath: 'label',
          selected: { label: values?.[MERCHANT_INFO]?.[key] || '--Select--' },
        };
        break;
      case 'ToCalendar':
        props = {
          ...props,
          disabledDate: disableFutureDates,
          onChange: (data) => handleDateChange(key, data),
          onBlur: () => null,
          size: 'half',
          addonAfter: <i className="i i-date-range" />,
          placement: 'topLeft',
        };
        break;
      default:
        props = {
          ...props,
          options,
        };
    }
    return props;
  };

  return (
    <div className="details-container">
      {detailsFormFields.map((field) => {
        const { type, key } = field;
        const InputField = type && Input[type] ? Input[type] : Input;
        if (key === 'iec_code' && !isPurposecodeSpecial) return null;
        return <InputField key={key} {...getFieldProps(field)} />;
      })}
    </div>
  );
};

export default Details;
