import React from 'react';
import Input from 'common/new-ui/Input';
import Form from 'common/new-ui/Form';
import * as PropTypes from 'prop-types';
import FileUpload from 'merchant/components/File/Upload';
import ToggleWithDescription from '../components/ToggleWithDescription';
import { DOCUMENT_GROUP_NAMES_MAP } from '../Loans/constants';

class DocumentsUpload extends React.Component {
  isNativeUploadAllowed = (document) => {
    const { selectedUploadModes } = this.props;
    return (
      selectedUploadModes[document.id] === 'native_upload' ||
      (selectedUploadModes[document.id] !== 'native_upload' && this.isDocumentUploaded(document))
    );
  };

  isDocumentUploaded = (document) => {
    return !!document.store_id;
  };

  docHasMultipleUploadOptions = (document) => {
    const { canUpload } = this.props;
    return (
      document.documentUploadOptions.length > 1 && canUpload && !this.isDocumentUploaded(document)
    );
  };

  render() {
    let {
      handleFileChange,
      documents,
      onRemoveFile,
      selectedUploadModes,
      uploadModesMeta,
      handleUploadModeChange,
      handleDocumentTypeChange,
      businessType,
    } = this.props;

    const isProprietorshipBusiness = parseInt(businessType) === 1;
    const proprietorshipDescription =
      'Upload a scanned copy of GST' +
      ' Certificate / Shop Establishment Act / Registration Certificate';

    return (
      <Form layout="tabular" onSubmit={() => {}}>
        {documents.map((document) => {
          const isBusinessRegistrationProofDoc =
            document.document_group.name === 'business_registration_proof';
          const allowedDocuments = document.master_documents.map((doc) => ({
            name: doc.id,
            label: doc.name || doc.type,
          }));

          let selectedDoc = allowedDocuments.find((a) => a.name == document.document_masters_id);
          if (!selectedDoc) {
            selectedDoc = allowedDocuments[0];
          }
          return (
            <Input.Group
              key={document.document_group.id}
              label={
                document.document_group.label ||
                DOCUMENT_GROUP_NAMES_MAP[document.document_group.name] ||
                document.document_group.name
              }
              className="InputGroup--inline"
              required
            >
              <div className="Input-content m-b">
                {document.master_documents.length > 1 && (
                  <Input.Select
                    name={document.document_group.id}
                    required
                    disabled={!!document.store_id}
                    onChange={(event) => handleDocumentTypeChange(document, event.target.value)}
                    value={selectedDoc.name}
                    options={allowedDocuments}
                  />
                )}
              </div>
              <div className="Input-content">
                {this.docHasMultipleUploadOptions(document) && (
                  <div className="document-upload-options-wrapper">
                    {document.documentUploadOptions.map((uploadOption) => (
                      <ToggleWithDescription
                        key={uploadOption}
                        title={uploadModesMeta[uploadOption].title}
                        description={uploadModesMeta[uploadOption].description}
                        meta={uploadModesMeta[uploadOption].meta}
                        hint={uploadModesMeta[uploadOption].hint}
                        selected={uploadOption === selectedUploadModes[document.id]}
                        onClick={() => handleUploadModeChange(document, uploadOption)}
                        disabled={uploadModesMeta[uploadOption].disabled}
                        style={{ marginBottom: 12 }}
                        loading={uploadModesMeta[uploadOption].loading}
                        showRadioInput={uploadModesMeta[uploadOption].showRadioInput}
                        radioPosition="left"
                      />
                    ))}
                  </div>
                )}
                {this.isNativeUploadAllowed(document, selectedUploadModes) && (
                  <FileUpload
                    showCloseBtn={false}
                    showFileSize
                    name={document.id}
                    stagedFileStatus="error"
                    showAcceptInfo
                    accept={document.acceptDocumentTypes}
                    size="large"
                    defaultValue={!!document.store_id}
                    uploadedFileName="Upload File here"
                    onFileChange={(file, progressTracker) =>
                      handleFileChange(document.id, file, progressTracker)
                    }
                    onCloseClick={() => onRemoveFile(document.id)}
                    dropZoneCavityClassName={document.id}
                    id={document.id}
                  />
                )}
                <div className="Input-desc">
                  {!(isProprietorshipBusiness && isBusinessRegistrationProofDoc)
                    ? document.document_group.description
                    : proprietorshipDescription}
                </div>
              </div>
            </Input.Group>
          );
        })}
      </Form>
    );
  }
}

DocumentsUpload.propTypes = {
  handleFileChange: PropTypes.any,
  documents: PropTypes.any,
  onNext: PropTypes.any,
  onRemoveFile: PropTypes.any,
};

export default DocumentsUpload;
