import React from 'react';
import Input from 'common/new-ui/Input';
import Form from 'common/new-ui/Form';
import * as PropTypes from 'prop-types';
import FileUpload from 'merchant/components/File/Upload';
import ToggleWithDescription from '../components/ToggleWithDescription';

class DocumentsUpload extends React.Component {
  render() {
    let {
      handleFileChange,
      documents,
      onRemoveFile,
      canUpload,
      selectedUploadModes,
      uploadModesMeta,
      handleUploadModeChange,
      handleDocumentTypeChange,
    } = this.props;
    return (
      <Form layout="tabular" onSubmit={() => {}}>
        {documents.map(document => (
          <Input.Group
            key={document.document_group.id}
            label={document.document_group.name}
            className="InputGroup--inline"
            required
          >
            <div className="Input-content m-b">
              {document.master_documents.length > 1 && (
                <Input.Select
                  name={document.document_group.id}
                  required
                  disabled={!!document.store_id}
                  onChange={event =>
                    handleDocumentTypeChange(document, event.target.value)
                  }
                  // defaultValue={document.document_masters_id}
                  value={document.document_masters_id}
                  options={document.master_documents.map(doc => ({
                    name: doc.id,
                    label: doc.name || doc.type,
                  }))}
                />
              )}
            </div>
            <div className="Input-content">
              {document.documentUploadOptions.length > 1 &&
                canUpload &&
                !document.store_id && (
                  <div class="document-upload-options-wrapper">
                    {document.documentUploadOptions.map(uploadOption => (
                      <ToggleWithDescription
                        title={uploadModesMeta[uploadOption].title}
                        description={uploadModesMeta[uploadOption].description}
                        selected={
                          uploadOption === selectedUploadModes[document.id]
                        }
                        onClick={() =>
                          handleUploadModeChange(document, uploadOption)
                        }
                        disabled={uploadModesMeta[uploadOption].disabled}
                        style={{ marginBottom: 12 }}
                      />
                    ))}
                  </div>
                )}
              {(selectedUploadModes[document.id] === 'native_upload' ||
                selectedUploadModes[document.id] === 'native_xml_upload') && (
                <FileUpload
                  showCloseBtn={false}
                  showFileSize
                  name={document.id}
                  stagedFileStatus="error"
                  showAcceptInfo
                  // maxSize={document.maxDocumentSize}
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
                  //need not to disable as we are not showing close button.
                  // as this cannot be modified.
                  // disabled={!canUpload}
                />
              )}
              <div className="Input-desc">
                {document.document_group.description}
              </div>
            </div>
          </Input.Group>
        ))}
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
