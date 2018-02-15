import React, { Component, Fragment } from 'react';

// TODO: temporary file upload till the file upload component is built
import FileUploadInputButton from 'rzp/ui/FileUpload/InputButton';
import ModalHeader from 'rzp/ui/ModalHeader';
import { titleCase } from 'rzp/utils/rzp-utils';

export default function UploadModal({
  closeModal,
  sampleUrl,
  docUrl,
  batchType,
  loadMore,
  onLoadMore,
}) {
  return (
    <div class="batch-upload-modal">
      <ModalHeader title="Batch Upload" onCloseClick={closeModal} />
      <div class="modal-body">
        <h4 class="modal-heading">Upload File</h4>
        <FileUploadInputButton
          accept="xlsx,csv"
          uploadedFileName="Upload File here"
          maxSize="1000000"
          onChange={event => {
            console.log(event);
          }}
        />
        <div class="modal-info">
          <h5>
            Getting Started with Batch Uploads?{' '}
            <a class="btn btn-link m-l" href={docUrl} target="_blank">
              View Documentation <i class="i i-external-link" />
            </a>
          </h5>
          <p>
            Upload a batch file to continue. Please note the following things
            before proceeding further:{' '}
            {!loadMore && (
              <span class="btn-link clickable" onClick={onLoadMore}>
                Load More
              </span>
            )}
          </p>
          {loadMore && (
            <Fragment>
              <ul>
                <li>1. The Amount mentioned should be in Paise.</li>
                <li>
                  2. The receipt id for all {titleCase(batchType)} should be
                  unique.
                </li>
                <li>3. The number of rows should not exceed 5000.</li>
              </ul>
              <p>
                In case of any issues, please{' '}
                <a class="btn-link" href={sampleUrl}>
                  download sample file
                </a>
              </p>
            </Fragment>
          )}
        </div>
      </div>
    </div>
  );
}
