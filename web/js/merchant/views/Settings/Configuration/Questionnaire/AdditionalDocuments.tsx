import React from 'react';
import { connect } from 'react-redux';

import Input from 'common/new-ui/Input';
import MultiFileUpload from 'merchant/views/Settings/Configuration/Questionnaire/MultiFileUpload';
import { LabelWithTooltip } from 'merchant/views/Settings/Configuration/Questionnaire/Tooltip';
import { StyledFieldContainer } from 'merchant/views/Settings/Configuration/Questionnaire/styles';
import { useAdditionalDocuments } from 'merchant/views/Settings/Configuration/Questionnaire/useAdditionalDocuments';
import { AdditionalDocumentsProps } from 'merchant/views/Settings/Configuration/Questionnaire/types';
import { showNotification as showNotificationFn } from 'merchant_common/reducers/notifications';

const AdditionalDocuments = ({
  user,
  disabled,
  saveFormData,
  showNotification,
}: AdditionalDocumentsProps) => {
  const {
    docs,
    selectedDocument,
    formikDocuments,
    selectedOption,
    handleSelect,
    handleFileRemove,
    handleUploadFile,
    filterOtherSelectOptions,
  } = useAdditionalDocuments({
    user,
    saveFormData,
    showNotification,
  });

  return (
    <StyledFieldContainer>
      {docs.map((doc, index) => {
        if (doc.type === 'select') {
          return (
            <div key={`${doc.label}-${index}`}>
              <div className="container">
                <p className="document-label required">{doc.label}</p>
                <p className="document-label-info">{doc.info}</p>
              </div>
              <Input.Select
                required
                value={selectedOption[index]}
                options={filterOtherSelectOptions(index, doc.options)}
                onChange={({ target }) => handleSelect(target.value, index)}
              />

              {selectedDocument[index] ? (
                <MultiFileUpload
                  key={selectedDocument[index].name}
                  name={selectedDocument[index].name}
                  required
                  label={() => (
                    <LabelWithTooltip
                      label={selectedDocument[index].subLabel ?? selectedDocument[index].label}
                      tooltip={selectedDocument[index].tooltip}
                      required
                    />
                  )}
                  disabled={disabled}
                  onFileRemove={handleFileRemove}
                  onFileChange={(file, progressTracker) =>
                    handleUploadFile(selectedDocument[index].name, file, progressTracker)
                  }
                  defaultFiles={formikDocuments[selectedDocument[index].name]}
                />
              ) : null}
            </div>
          );
        }

        return (
          <div className="file-uploader" key={`${doc.label}-${index}`}>
            <MultiFileUpload
              key={doc.name}
              name={doc.name}
              required
              label={() => (
                <LabelWithTooltip
                  label={doc.subLabel ?? doc.label}
                  tooltip={doc.tooltip}
                  required={doc.isRequired}
                />
              )}
              disabled={disabled}
              onFileRemove={handleFileRemove}
              onFileChange={(file, progressTracker) =>
                handleUploadFile(doc.name, file, progressTracker)
              }
              defaultFiles={formikDocuments[doc.name]}
            />
          </div>
        );
      })}
    </StyledFieldContainer>
  );
};

const mapStateToProps = (state) => ({
  user: state.session.user,
});

export default connect(mapStateToProps, { showNotification: showNotificationFn })(
  AdditionalDocuments,
);
