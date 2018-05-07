import { Component, Fragment } from 'react';

import FileUpload from 'merchant/components/File/Upload';
import FileStaged from 'merchant/components/File/Staged';

import ModalHeader from 'rzp/ui/ModalHeader';
import { titleCase } from 'rzp/utils/rzp-utils';

const MAX_FILE_SIZE = 1048576; // 1MB in bytes.

export default function BatchValidateModal({
  status,
  stagedFileStatus,
  fileUrl,
  notifyMsg,
  shouldLoadMore,
  sampleUrl,
  docUrl,
  batchType,
  maxRows,
  onLoadMore,
  onFileChange,
  onBiggerFileSize,
  onCloseClick,
  closeModal,
  files,
  fileUploadProgress,
  onSampleFileDownload = () => {},
  onErrorReportDownload = () => {},
}) {
  return (
    <div class="modal-body">
      <h4 class="modal-heading">UPLOAD FILE</h4>
      <div class="modal-file">
        <FileUpload
          accept={['csv', 'xlsx', 'xls']}
          uploadedFileName="Upload File here"
          maxSize={MAX_FILE_SIZE}
          onBiggerFileSize={onBiggerFileSize}
          onFileChange={onFileChange}
          onCloseClick={onCloseClick}
          stagedFileStatus={stagedFileStatus}
          uploadedBytes={fileUploadProgress}
          files={files}
          showCloseBtn={false}
          showStagedFileStatus
        />
        {notifyMsg && (
          <h5 class={`notification ${status}`}>
            <i class="i i-info-circle m-r" />
            {notifyMsg}
          </h5>
        )}
      </div>

      {/* Show batch upload modal info when no file uploaded */}
      {!status || status === 'exceed' ? (
        <div class="modal-info">
          <h5 style={{ fontSize: '16px' }}>
            Getting Started with Batch Uploads?{' '}
            <a class="btn btn-link m-l doc-url" href={docUrl} target="_blank">
              View Documentation <i class="i i-external-link" />
            </a>
          </h5>
          <p>
            Upload a batch file to continue. Please note the following things
            before proceeding further:{' '}
            {!shouldLoadMore && (
              <span class="btn-link clickable" onClick={onLoadMore}>
                Load More
              </span>
            )}
          </p>
          {shouldLoadMore && (
            <Fragment>
              <ol>
                <li>The Amount mentioned should be in Paise.</li>
                {batchType && (
                  <li>
                    The receipt id for all {titleCase(batchType)}s should be
                    unique.
                  </li>
                )}
                {maxRows && (
                  <li>The number of rows should not exceed {maxRows}.</li>
                )}
              </ol>
              <p>
                In case of any issues, please{' '}
                <a
                  class="btn-link"
                  href={sampleUrl}
                  onClick={onSampleFileDownload}
                >
                  download sample file
                </a>
              </p>
            </Fragment>
          )}
        </div>
      ) : null}

      {/* Show batch modal error-info when file upload */}
      {fileUrl ? (
        <div class="modal-info error stretch">
          <div class="row">
            <div class="col-sm-9">
              <h4 class="m-b">How to fix an error?</h4>
              <p>
                The errors are marked in a the same file in a separate column.
                Download the error file, fix the errors and upload again to
                proceed.
              </p>
            </div>
            <div class="col-sm-3">
              <a
                class="btn btn-primary btn-block"
                href={fileUrl}
                onClick={onErrorReportDownload}
              >
                {' '}
                <i class="i i-download m-r" /> Download File
              </a>
            </div>
          </div>
        </div>
      ) : null}
    </div>
  );
}
