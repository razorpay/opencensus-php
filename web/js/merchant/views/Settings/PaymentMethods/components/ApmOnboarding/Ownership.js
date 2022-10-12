import { useContext } from 'react';
import { useFormikContext } from 'formik';
import { formContext } from './FormContext';

//Helper function and constants
import { saveDocument } from './services';
import { disableFutureDates } from './utils';
import { OWNER_DETAILS, ownershipFormFields } from './constants';

//Components
import Input from 'common/new-ui/Input';
import OwnerList from './OwnerList';
import OwnerPrompt from './OwnerPrompt';

const Ownership = ({ saveData, showNotification }) => {
  const { activeOwner, ownerCount, updateDocuments, documents } = useContext(formContext);
  const { values, touched, errors, handleChange, handleBlur, setFieldValue } = useFormikContext();

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
        setFieldValue(`${OWNER_DETAILS}[${activeOwner}].${tag}`, response.id);
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

  const handleFileRemove = (tag) => {
    setFieldValue(`${OWNER_DETAILS}[${activeOwner}].${tag}`, '');
    updateDocuments(values?.[OWNER_DETAILS]?.[activeOwner]?.[tag], null);
  };

  const handleDateChange = (tag, data) => {
    setFieldValue(`${OWNER_DETAILS}[${activeOwner}].${tag}`, data.format('YYYY-MM-DD'));
  };

  const getName = (tag) => {
    return `${OWNER_DETAILS}[${activeOwner}].${tag}`;
  };

  const getValues = (tag) => {
    return values?.[OWNER_DETAILS]?.[activeOwner]?.[tag];
  };

  const getTouched = (tag) => {
    return touched?.[OWNER_DETAILS]?.[activeOwner]?.[tag];
  };

  const getError = (tag) => {
    return errors?.[OWNER_DETAILS]?.[activeOwner]?.[tag];
  };

  const getFileName = (tag) => {
    const documentId = values?.[OWNER_DETAILS]?.[activeOwner]?.[tag];
    const document = documents?.[documentId];
    return document?.display_name;
  };

  const getFieldProps = ({ key, label, placeholder, type, options, accept }) => {
    let props = {
      label,
      placeholder,
      name: getName(key),
      value: getValues(key),
      mature: getTouched(key),
      propagatedError: getTouched(key) && getError(key),
      onChange: handleChange,
      onBlur: handleBlur,
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
          showStagedFileStatus: true,
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
    <div className="ownership-container">
      <OwnerPrompt saveData={saveData} />
      <OwnerList saveData={saveData} />
      {ownerCount > 0 &&
        ownershipFormFields.map((field) => {
          const { type, key } = field;
          const InputField = type && Input[type] ? Input[type] : Input;
          return <InputField key={key} {...getFieldProps(field)} />;
        })}
    </div>
  );
};

export default Ownership;
