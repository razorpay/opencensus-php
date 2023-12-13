import { useMemo, useState } from 'react';
import { useFormikContext } from 'formik';

import { merchantFetch } from 'merchant/utils/ajax';
import { getAdditionalDocumentsBasedOnBusinessType } from 'merchant/views/Settings/Configuration/Questionnaire/utils';
import {
  FormikValues,
  AdditionalDocumentsProps,
  UseAdditionalDocumentsReturn,
  Doc,
} from 'merchant/views/Settings/Configuration/Questionnaire/types';

export const useAdditionalDocuments = ({
  user,
  saveFormData,
  showNotification,
}: AdditionalDocumentsProps): UseAdditionalDocumentsReturn => {
  const [selectedDocument, setSelectedDocument] = useState<Doc[]>([]);
  const [selectedOption, setSelectedOption] = useState<string[]>([]);
  const formikProps = useFormikContext<FormikValues>();

  const docs = useMemo(
    () =>
      getAdditionalDocumentsBasedOnBusinessType({
        businessType: user.business_type,
        acceptsIntlTxns: formikProps.values.accepts_intl_txns === 'true',
      }),
    [user.business_type, formikProps.values.accepts_intl_txns],
  );

  const formikDocuments = formikProps.values.documents;

  const filterOtherSelectOptions = (index, options) => {
    const optionForIndex = selectedOption[index];
    const otherSelectedOptions = selectedOption.filter((_, idx) => idx !== index);

    return selectedOption.length
      ? options.filter((item) => {
          if (optionForIndex && (item.name === optionForIndex || !item.name)) {
            return true;
          }

          if (otherSelectedOptions.length) {
            return otherSelectedOptions.some((selected) => selected !== item.name);
          }

          return true;
        })
      : options;
  };

  const handleSelect = (value, index) => {
    setSelectedOption((prevValue) => {
      const newValue = [...prevValue];
      newValue[index] = value;
      return newValue;
    });

    if (!value && selectedDocument[index]) {
      setSelectedDocument((prevValue) => {
        const newValue = [...prevValue];
        newValue.splice(index, 1);
        return newValue;
      });

      return;
    }

    const option = docs[index]?.options.find((item) => {
      return item.name === value;
    });
    setSelectedDocument((prevValue) => {
      const newValue = [...prevValue];
      newValue[index] = option;
      return newValue;
    });
  };

  const handleUploadFile = (docType, file, progressTracker) => {
    const formData = new FormData();
    formData.append('purpose', 'international_enablement');
    formData.append('file', file);

    return merchantFetch({
      url: 'documents',
      method: 'post',
      data: formData,
      onUploadProgress: progressTracker,
    })
      .then((res) => {
        if (!res.data) {
          return undefined;
        }

        const docData = {
          id: res.data.id,
          display_name: res.data.display_name,
        };

        formikDocuments[docType] = [...(formikDocuments[docType] ?? []), docData];
        formikProps.setFieldValue('documents', formikDocuments);
        saveFormData(formikProps);

        return docData;
      })
      .catch((err) => {
        showNotification({
          type: 'error',
          message: err.errors,
        });
      });
  };

  const handleFileRemove = (docId, docType) => {
    const updatedDocs = formikDocuments[docType]?.filter((d) => d.id !== docId);
    const documentKey = `documents.${docType}`;
    const documentValue = updatedDocs?.length ? updatedDocs : null;

    if (formikProps.values.documents) {
      formikProps.values.documents[docType] = documentValue;
    }

    formikProps.setFieldValue(documentKey, documentValue);
    saveFormData(formikProps, true);
  };

  return {
    docs,
    selectedDocument,
    formikDocuments,
    selectedOption,
    handleSelect,
    handleUploadFile,
    handleFileRemove,
    filterOtherSelectOptions,
  };
};
