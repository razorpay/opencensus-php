import React, { useState, useEffect } from 'react';
import { PowerSelect } from 'react-power-select';
import Input from 'common/new-ui/Input';
import MultiFileUpload from './MultiFileUpload';
import { useFormikContext } from 'formik';
import { merchantFetch } from 'merchant/utils/ajax';
import { connect } from 'react-redux';
import { showNotification } from 'merchant_common/reducers/notifications';
import { defaultFileTypes, getAvailableFileTypes } from './utils';
import SupportingDocumentsV2 from 'merchant/views/Settings/Configuration/Questionnaire/SupportingDocumentsV2';

// eslint-disable-next-line no-shadow
const SupportingDocuments = ({
  disabled,
  saveFormData,
  showNotification,
  isRevampFlow,
  isAnyIntlProductEnabled,
}) => {
  const formikProps = useFormikContext();
  const [availableFileTypes, preUploadedDocuments] = getAvailableFileTypes(formikProps);
  const [documents, setDocuments] = useState(preUploadedDocuments);
  const [fileTypes, setFileTypes] = useState(availableFileTypes);

  const getError = (name) =>
    (formikProps.touched[name] ? formikProps.errors[name] : '') ||
    (!!formikProps.status ? formikProps.status[name] : '');

  useEffect(() => {
    const [_availableFileTypes] = getAvailableFileTypes(formikProps);
    setFileTypes(_availableFileTypes);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [formikProps.values.accepts_intl_txns]);

  const handleAddDocument = (event) => {
    if (!event.option) return;
    const { name, label } = event.option;

    setDocuments([
      ...documents,
      {
        name,
        label,
      },
    ]);

    const updatedFileTypes = fileTypes.filter((file) => file.name !== name);
    setFileTypes(updatedFileTypes);
  };

  const removeFileType = (docType) => {
    setDocuments((docs) => docs.filter((d) => d.name !== docType));

    // Adding file type back
    setFileTypes((currentFiles) =>
      currentFiles.concat(defaultFileTypes.filter((f) => f.name === docType)),
    );
  };

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
          const isOtherDocument =
            defaultFileTypes.filter((_fileTypes) => _fileTypes.name === docType).length === 0;

          const docData = {
            id: res.data.id,
            display_name: res.data.display_name,
          };
          if (
            !isOtherDocument ||
            docType === 'bank_statement_inward_remittance' ||
            docType === 'current_payment_partner_settlement_record'
          ) {
            if (!formikProps.values.documents[docType]) {
              formikProps.values.documents[docType] = [];
            }
            formikProps.values.documents[docType].push(docData);
          } else {
            // If other document types not present
            if (!formikProps.values.documents.others) {
              formikProps.values.documents.others = {
                [docType]: [],
              };
            }
            // If other document type present but not the current document type
            if (!formikProps.values.documents.others[docType]) {
              formikProps.values.documents.others[docType] = [];
            }
            formikProps.values.documents.others[docType].push(docData);
          }
          saveFormData(formikProps);
        }
      })
      .catch((err) => {
        if (window.APP_ENV !== 'production') console.error(err);
        showNotification({
          type: 'error',
          message: err.errors,
        });
      });
  };

  const handleFileRemoval = (docId, docType) => {
    const isOtherDocument =
      defaultFileTypes.filter((_fileTypes) => _fileTypes.name === docType).length === 0;

    let documentObject;
    if (
      !isOtherDocument ||
      docType === 'bank_statement_inward_remittance' ||
      docType === 'current_payment_partner_settlement_record'
    ) {
      documentObject = formikProps.values.documents;
    } else {
      documentObject = formikProps.values.documents.others;
    }

    if (documentObject[docType]) {
      const updatedDocs = documentObject[docType].filter((d) => d.id !== docId);
      documentObject[docType] = updatedDocs.length ? updatedDocs : null;

      saveFormData(formikProps, true);
    }
  };

  const handleChange = () => {
    saveFormData(formikProps);
  };

  const formikDocuments = formikProps.values.documents;

  if (isRevampFlow) {
    return (
      <SupportingDocumentsV2
        disabled={disabled}
        saveFormData={saveFormData}
        isAnyIntlProductEnabled={isAnyIntlProductEnabled}
      />
    );
  }

  return (
    <div class="supporting-documents">
      <div class="main-title pb-20">SUPPORTING DOCUMENTS</div>
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
      <div class="spacer" />
      <Input
        name="import_export_code"
        label="Import Export Code"
        placeholder="Enter I/E code here"
        info="Example: U67190TN20"
        disabled={disabled}
        onBlur={handleChange}
        value={formikProps.values.import_export_code}
        mature={formikProps.touched.import_export_code}
        propagatedError={getError('import_export_code')}
      />

      {formikProps.values.accepts_intl_txns === 'true' && (
        <>
          <MultiFileUpload
            name="bank_statement_inward_remittance"
            label="Bank Statement for Inward Remittance"
            disabled={disabled}
            required={formikProps.values.accepts_intl_txns === 'true'}
            onFileChange={(file, progressTracker) =>
              handleFileUpload('bank_statement_inward_remittance', file, progressTracker)
            }
            onFileRemove={handleFileRemoval}
            defaultFiles={formikDocuments.bank_statement_inward_remittance}
          />
          <MultiFileUpload
            name="current_payment_partner_settlement_record"
            label="Settlement record from current payment partner"
            required
            onFileChange={(file, progressTracker) =>
              handleFileUpload('current_payment_partner_settlement_record', file, progressTracker)
            }
            disabled={disabled}
            onFileRemove={handleFileRemoval}
            defaultFiles={formikDocuments.current_payment_partner_settlement_record}
          />
        </>
      )}

      {documents.map((doc) => {
        if (
          formikProps.values.accepts_intl_txns === 'true' &&
          (doc.name === 'bank_statement_inward_remittance' ||
            doc.name === 'current_payment_partner_settlement_record')
        )
          return null;
        return (
          <MultiFileUpload
            key={doc.name}
            name={doc.name}
            label={doc.label}
            removeFileType={removeFileType}
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

      {!disabled && (
        <div class="Input">
          <div class="Input-label">Add Document</div>
          <div class="Input-content">
            <PowerSelect
              options={fileTypes}
              optionLabelPath="label"
              searchEnabled={false}
              placeholder="--Select-- (Optional)"
              optionComponent={({ option }) => <div class="option">{option.label}</div>}
              afterOptionsComponent={({ select }) => {
                return (
                  <AddOtherDoc
                    label="+Others, Specify"
                    placeholder="Please enter document name"
                    showActionBtn={true}
                    onActionClick={(name) => {
                      handleAddDocument({
                        option: { label: name, name: name.replaceAll(' ', '_') },
                      });
                      select.actions.close();
                    }}
                  />
                );
              }}
              onChange={handleAddDocument}
            />
            <div class="Input-desc">
              It is advisable to upload as many documents available from the above list to present a
              strong case for approval
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

const AddOtherDoc = ({
  label = '+Others, Specify',
  placeholder = 'Document name',
  onActionClick,
}) => {
  const [takeInput, setTakeInput] = useState(false);
  const [input, setInput] = useState('');
  return (
    <div class="more-item">
      {takeInput ? (
        <>
          <input
            type="text"
            placeholder={placeholder}
            value={input}
            maxLength="50"
            onChange={(e) => setInput(e.target.value)}
            autoFocus
          />
          <button
            class="btn btn-link add"
            onClick={() => {
              if (input) onActionClick(input);
            }}
          >
            +Add
          </button>
        </>
      ) : (
        <button class="btn btn-link" onClick={() => setTakeInput(true)}>
          {label}
        </button>
      )}
    </div>
  );
};

export default connect(null, { showNotification })(SupportingDocuments);
