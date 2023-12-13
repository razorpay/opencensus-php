import React from 'react';
import { useFormikContext } from 'formik';
import { connect } from 'react-redux';

import Input from 'common/new-ui/Input';
import { useSplitzService } from 'common/splitz';
import { stringToObj } from 'common/utils/rzp-utils';
import { merchantFetch } from 'merchant/utils/ajax';
import { LabelWithTooltip } from 'merchant/views/Settings/Configuration/Questionnaire/Tooltip';
import { showNotification as showNotificationFn } from 'merchant_common/reducers/notifications';

import AdditionalDocuments from './AdditionalDocuments';
import MultiFileUpload from './MultiFileUpload';
import { StyledFieldContainer } from './styles';
import { getAdditionalDocumentsBasedOnSubCategory, getIsOtherDocumentInRevampFlow } from './utils';

const SupportingDocumentsV2 = ({ disabled, saveFormData, showNotification, user }) => {
  const [transactionProofDocs, setTransactionProofDocs] = React.useState([]);
  const [additionalDocs, setAdditionalDocs] = React.useState([]);
  const formikProps = useFormikContext();
  const {
    abExperiments: { internationalAdditionalDocs },
  } = useSplitzService();
  const isAdditionalDocExperimentEnabled = internationalAdditionalDocs.variables.result === 'on';

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
          return docData;
        }
        return undefined;
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

      // saveFormData doesn't have latest documents value unless it's updated directly
      formikProps.values = stringToObj(documentKey, documentValue, formikProps.values);
      // this is needed for formik validation schema to run
      formikProps.setFieldValue(documentKey, documentValue);
      saveFormData(formikProps, true);
    }
  };

  const handleDocumentOptionsChange = (value, type) => {
    const document = [];
    switch (value) {
      case 'bank_statement':
        document.push({
          label: 'Bank Statement (Last 60 days)',
          name: 'bank_statement_inward_remittance',
          isRequired: false,
          tooltipContent:
            'Document to ensure that your bank statement is matching with the invoices shared with us',
        });
        break;
      case 'invoices':
        document.push({
          label: 'Invoices',
          name: 'invoices',
          isRequired: false,
          tooltipContent: 'Invoice for purchases made by customers on your website',
        });
        break;
      case 'settlement_record':
        document.push({
          label: 'Settlement record from current payment partner',
          name: 'current_payment_partner_settlement_record',
          isRequired: false,
          tooltipContent: 'Record of processed settlements from your current payment partner',
        });
        break;
      case 'firc':
        document.push({
          label: 'Forward inward remittance statement',
          name: 'firc',
          isRequired: false,
          tooltipContent:
            'Document to validate if you are already receiving international payments from another payment partner',
        });
        break;
      default:
        break;
    }

    if (type === 'transaction_proofs') {
      setTransactionProofDocs(document);
    } else {
      setAdditionalDocs(document);
    }
  };

  const formikDocuments = formikProps.values.documents;

  const getMCCBasedDocumentation = () => {
    const document = [];
    const { business_category, business_subcategory } = user;
    if (business_category && business_subcategory) {
      const mandatoryFile = getAdditionalDocumentsBasedOnSubCategory(user);
      if (mandatoryFile) {
        document.push({
          ...mandatoryFile,
          tooltipContent: 'Mandatory document required for your business type',
        });
        return document;
      } else {
        return document;
      }
    }

    return document;
  };

  const getAdditionalDocumentsOptions = () => {
    const documents = [
      { label: 'Select', name: '' },
      { label: 'Forward inward remittance statement', name: 'firc' },
    ];

    if (formikProps.values.accepts_intl_txns === 'true')
      documents.push({
        label: 'Settlement record from current payment partner',
        name: 'settlement_record',
      });

    return documents;
  };

  const handleChange = (e) => {
    const radioValue = e?.target?.value;
    formikProps.setFieldValue('accepts_intl_txns', radioValue);
    if (radioValue === 'false') {
      setTransactionProofDocs([]);
      setAdditionalDocs([]);
    }
  };

  const handleImportCodeChange = (e) => {
    formikProps.setFieldValue('import_export_code', e?.target?.value);
  };

  const getError = (name) =>
    (formikProps.touched[name] ? formikProps.errors[name] : '') ||
    (!!formikProps.status ? formikProps.status[name] : '');

  return (
    <div className="supporting-documents ie-revamp">
      <div className="main-title pb-20">SUPPORTING DETAILS</div>
      <Input.Radio
        required
        name="accepts_intl_txns"
        label="Currently Accept International Transactions"
        onChange={handleChange}
        options={[
          { value: 'true', label: 'Yes' },
          { value: 'false', label: 'No' },
        ]}
        className="Input--vTop"
        disabled={disabled}
        defaultValue={formikProps.values.accepts_intl_txns || 'false'}
        propagatedError={getError('accepts_intl_txns')}
      />
      <div class="spacer" />
      <Input
        name="import_export_code"
        label="Import Export Code"
        placeholder="Enter I/E code here (Optional)"
        info="Example: U67190TN20"
        disabled={disabled}
        onChange={handleImportCodeChange}
        value={formikProps.values.import_export_code}
        mature={formikProps.touched.import_export_code}
        propagatedError={getError('import_export_code')}
      />

      {getMCCBasedDocumentation().map((doc) => {
        return (
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
            required={false}
            disabled={disabled}
            onFileRemove={handleFileRemoval}
            defaultFiles={
              formikDocuments[doc.name] ||
              (formikDocuments.others && formikDocuments.others[doc.name])
            }
          />
        );
      })}

      {formikProps.values.accepts_intl_txns === 'true' ? (
        <StyledFieldContainer>
          <div className="container">
            <p className="document-label">Proof of Transaction</p>
            <p className="document-label-info">
              Without uploading this your maximum limit per transaction will only be upto 1 lakh
            </p>
          </div>
          <Input.Select
            options={[
              { label: 'Select', name: '' },
              { label: 'Bank Statement', name: 'bank_statement' },
              { label: 'Invoices', name: 'invoices' },
            ]}
            onChange={({ target }) => {
              handleDocumentOptionsChange(target.value, 'transaction_proofs');
            }}
          />
        </StyledFieldContainer>
      ) : null}

      {transactionProofDocs.map((doc) => {
        return (
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
            required={false}
            disabled={disabled}
            onFileRemove={handleFileRemoval}
            defaultFiles={
              formikDocuments[doc.name] ||
              (formikDocuments.others && formikDocuments.others[doc.name])
            }
          />
        );
      })}

      {formikProps.values.accepts_intl_txns === 'true' ? (
        <StyledFieldContainer>
          <div className="container">
            <p className="document-label">Additional document</p>
            <p className="document-label-info">
              Uploading an optional document will help us offer you an even higher transaction limit
            </p>
          </div>
          <Input.Select
            options={getAdditionalDocumentsOptions()}
            onChange={({ target }) => {
              handleDocumentOptionsChange(target.value, 'additional_docs');
            }}
          />
        </StyledFieldContainer>
      ) : null}

      {additionalDocs.map((doc) => {
        return (
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
            disabled={disabled}
            onFileRemove={handleFileRemoval}
            defaultFiles={
              formikDocuments[doc.name] ||
              (formikDocuments.others && formikDocuments.others[doc.name])
            }
          />
        );
      })}

      {isAdditionalDocExperimentEnabled ? (
        <AdditionalDocuments disabled={disabled} saveFormData={saveFormData} />
      ) : null}

      <div class="Input Input--required Input--checkbox">
        <div class="Input-content">
          <div class="Input-elWrapper">
            <label>
              <input required name="submit" class="Input-el" type="checkbox" />
              <div class="Input-checkbox" />
              <div class="Input-inlineLabel">
                I have read and understood the{' '}
                <a href="https://razorpay.com/terms" target="_blank" rel="noopener noreferrer">
                  Terms &amp; Conditions
                </a>
                ,{' '}
                <a href="https://razorpay.com/agreement" target="_blank" rel="noopener noreferrer">
                  Merchant Agreement
                </a>{' '}
                and the{' '}
                <a href="https://razorpay.com/privacy" target="_blank" rel="noopener noreferrer">
                  Privacy Policy
                </a>
                {'. '}
                By submitting the form, I agree to abide by the rules at all times.
              </div>
            </label>
          </div>
        </div>
      </div>
    </div>
  );
};

const mapStateToProps = (state) => ({
  user: state.session.user,
});

export default connect(mapStateToProps, { showNotification: showNotificationFn })(
  SupportingDocumentsV2,
);
