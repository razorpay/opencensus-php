import Input from 'common/new-ui/Input';
import { useFormikContext } from 'formik';
import { merchantFetch } from 'merchant/utils/ajax';
import { showNotification as showNotificationFn } from 'merchant_common/reducers/notifications';
import React, { useMemo } from 'react';
import { connect } from 'react-redux';
import MultiFileUpload from './MultiFileUpload';
import {
  getAdditionalDocumentsBasedOnSubCategory,
  defaultFileTypesIERevamp,
  getIsOtherDocumentInRevampFlow,
} from './utils';
import { LabelWithTooltip } from 'merchant/views/Settings/Configuration/Questionnaire/Tooltip';
import { stringToObj } from 'common/utils/rzp-utils';

const SupportingDocumentsV2 = ({ disabled, saveFormData, showNotification, user }) => {
  const formikProps = useFormikContext();
  const getError = (name) =>
    (formikProps.touched[name] ? formikProps.errors[name] : '') ||
    (!!formikProps.status ? formikProps.status[name] : '');

  const fileDocsToShow = useMemo(() => {
    const docsToShow = [...defaultFileTypesIERevamp];
    const { business_category, business_subcategory } = user;
    if (business_category && business_subcategory) {
      const mandatoryFile = getAdditionalDocumentsBasedOnSubCategory(user);
      if (mandatoryFile) {
        // Add before FIRS
        docsToShow.splice(2, 0, {
          ...mandatoryFile,
          tooltipContent: 'This is a mandatory document required for your business type',
        });
      }
    }
    if (formikProps.values.accepts_intl_txns === 'true') {
      docsToShow.unshift({
        label: 'Settlement record from current payment partner',
        name: 'current_payment_partner_settlement_record',
        isRequired: true,
        tooltipContent:
          'This is a record of processed settlements from your current payment partner',
      });
    }
    return docsToShow;
  }, [formikProps.values.accepts_intl_txns, user.business_category, user.business_subcategory]);

  const handleFileUpload = (docType, file, progressTracker) => {
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
        if (res.data) {
          const isOtherDocument = getIsOtherDocumentInRevampFlow(docType);
          const documentsObject = isOtherDocument
            ? formikProps.values.documents.others || {}
            : formikProps.values.documents;

          const docData = {
            id: res.data.id,
            display_name: res.data.display_name,
          };

          // If other document type present but not the current document type

          documentsObject[docType] = [...(documentsObject[docType] || []), docData];
          const documentKey = `documents${isOtherDocument ? '.others' : ''}`;
          formikProps.setFieldValue(documentKey, documentsObject);
          saveFormData(formikProps);
        }
      })
      .catch((err) => {
        console.error(err);
        showNotification({
          type: 'error',
          message: err.errors,
        });
      });
  };

  const handleFileRemoval = (docId, docType) => {
    const isOtherDocument = getIsOtherDocumentInRevampFlow(docType);

    const documentObject = isOtherDocument
      ? formikProps.values.documents.others
      : formikProps.values.documents;

    if (documentObject[docType]) {
      const updatedDocs = documentObject[docType].filter((d) => d.id !== docId);
      const documentKey = `documents${isOtherDocument ? '.others' : ''}.${docType}`;
      const documentValue = updatedDocs.length ? updatedDocs : null;
      // this is needed for formik validation schema to run
      formikProps.setFieldValue(documentKey, documentValue);
      // saveFormData doesn't have latest documents value unless it's updated directly
      formikProps.values = stringToObj(documentKey, documentValue, formikProps.values);
      saveFormData(formikProps, true);
    }
  };

  const handleChange = () => {
    saveFormData(formikProps);
  };

  const formikDocuments = formikProps.values.documents;

  return (
    <div className="supporting-documents ie-revamp">
      <div className="main-title pb-20">SUPPORTING DOCUMENTS</div>
      <Input.Radio
        required
        name="accepts_intl_txns"
        label="Currently Accept International Transactions"
        onBlur={handleChange}
        options={[
          { value: 'true', label: 'Yes' },
          { value: 'false', label: 'No' },
        ]}
        className="Input--vTop"
        disabled={disabled}
        defaultValue={formikProps.values.accepts_intl_txns}
        propagatedError={getError('accepts_intl_txns')}
      />
      <div className="spacer" />
      <Input
        name="import_export_code"
        label={() => (
          <LabelWithTooltip
            label="Import Export Code"
            tooltip="Required to validate import/export business in India"
            required={false}
          />
        )}
        placeholder="Enter I/E code here (Optional)"
        info="Example: U67190TN20"
        disabled={disabled}
        onBlur={handleChange}
        value={formikProps.values.import_export_code}
        mature={formikProps.touched.import_export_code}
        propagatedError={getError('import_export_code')}
      />

      {fileDocsToShow.map((doc) => (
        <MultiFileUpload
          key={doc.name}
          name={doc.name}
          label={() => (
            <LabelWithTooltip
              label={doc.label}
              tooltip={doc.tooltipContent}
              required={doc.isRequired}
            />
          )}
          onFileChange={(file, progressTracker) =>
            handleFileUpload(doc.name, file, progressTracker)
          }
          // required is set to true to hide the remove file button at 0th index
          required
          disabled={disabled}
          onFileRemove={handleFileRemoval}
          defaultFiles={
            formikDocuments[doc.name] ||
            (formikDocuments.others && formikDocuments.others[doc.name])
          }
        />
      ))}
    </div>
  );
};

const mapStateToProps = (state) => ({
  user: state.session.user,
});

export default connect(mapStateToProps, { showNotification: showNotificationFn })(
  SupportingDocumentsV2,
);
