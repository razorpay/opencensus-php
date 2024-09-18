import { useMemo, useState } from 'react';
import { useFormikContext } from 'formik';

import { merchantFetch } from 'merchant/utils/ajax';
import {
  trackDocumentUploadSuccess,
  trackDocumentUploadErr,
} from 'merchant/views/Settings/PaymentMethods/components/MethodEnablementForm/analytics';
import {
  FormikValues,
  AdditionalDocumentsProps,
  UseAdditionalDocumentsReturn,
  Doc,
} from 'merchant/views/Settings/PaymentMethods/components/MethodEnablementForm/types';
import { getAdditionalDocumentsBasedOnBusinessType } from 'merchant/views/Settings/PaymentMethods/components/MethodEnablementForm/utils';

export const useAdditionalDocuments = ({
  user,
  saveFormData,
  showNotification,
}: AdditionalDocumentsProps): UseAdditionalDocumentsReturn => {
  const [selectedDocument, setSelectedDocument] = useState<Doc[]>([]);
  const [selectedOption, setSelectedOption] = useState<string[]>([]);
  const formikProps = useFormikContext<FormikValues>();

  const docs = useMemo(
    () => getAdditionalDocumentsBasedOnBusinessType(user.business_type as string),
    [user.business_type],
  );

  const formikDocuments = formikProps?.values?.documents ?? {};

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

    const option = docs[index]?.options?.find((item) => {
      return item.name === value;
    });
    setSelectedDocument((prevValue) => {
      const newValue = [...prevValue];
      newValue[index] = option as Doc;
      return newValue;
    });
  };

  const handleUploadFile = (docType, file, progressTracker) => {
    const formData = new FormData();
    formData.append('purpose', 'international_products_pa_cb_enablement');
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
        trackDocumentUploadSuccess({
          businessType: user.business_type,
          documentType: docType,
        });

        return docData;
      })
      .catch((err) => {
        trackDocumentUploadErr({
          businessType: user.business_type,
          documentType: docType,
          error: err,
        });
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
    saveFormData(formikProps);
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
