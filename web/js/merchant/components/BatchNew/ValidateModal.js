import { Component } from 'react';
import ShowWhen from 'merchant/components/ShowWhen';
import FileUpload from 'merchant/components/File/Upload';
import { Link } from 'react-router-dom';
import { titleCase } from 'common/utils/rzp-utils';
import { closeModal } from 'merchant_common/reducers/modals';
import { connect } from 'react-redux';
import { DocLink } from 'merchant/components/DocsLink';
import { bindActionCreators } from 'redux';

const DEFAULT_MAX_FILE_SIZE = 1048576; // 1MB in bytes.

class BatchValidateModal extends Component {
  uploadFileModalClick = (e) => {
    const { onClickUpload } = this.props;
    if (e.target.nodeName === 'LABEL') {
      if (onClickUpload) {
        onClickUpload();
      }
    }
  };
  render() {
    const {
      status,
      stagedFileStatus,
      fileUrl,
      notifyMsg,
      sampleUrl,
      docUrl,
      batchType,
      maxRows,
      onFileChange,
      onBiggerFileSize,
      onCloseClick,
      files,
      fileUploadProgress,
      maxFileSize = DEFAULT_MAX_FILE_SIZE,
      onSampleFileDownload = () => {},
      onErrorReportDownload = () => {},
      user,
      batchClass,
      acceptFileInfo,
      accept,
    } = this.props;

    let { batchTypeText = '' } = this.props;

    if (batchType === 'payment_link_v2') {
      batchTypeText = 'Payment Link'; // We don't want to unnececssarily expose that merchant is using V2
    }

    return (
      <div className={batchClass ? batchClass : 'modal-body'}>
        {!batchClass ? <h4 className="modal-heading">UPLOAD FILE</h4> : null}
        <div className="modal-file" onClick={this.uploadFileModalClick}>
          <FileUpload
            accept={accept || ['csv', 'xlsx']}
            acceptFileInfo={acceptFileInfo}
            size="large"
            uploadedFileName="Upload File here"
            maxSize={maxFileSize}
            onBiggerFileSize={onBiggerFileSize}
            onFileChange={onFileChange}
            onCloseClick={onCloseClick}
            stagedFileStatus={stagedFileStatus}
            uploadedBytes={fileUploadProgress}
            files={files}
            showCloseBtn={true}
            batchType={batchType}
            showStagedFileStatus
            showFileSize={false}
          />
          {notifyMsg && (
            <h5 className={`notification ${status}`}>
              <i className="i i-info-circle m-r" />
              {notifyMsg}
            </h5>
          )}
        </div>

        {/* Show batch upload modal info when no file uploaded */}
        {!status || status === 'exceed' ? (
          <>
            {batchType === 'partner_submerchant_invite' ? (
              <div className="top-download-link">
                <a className="btn-link" href={sampleUrl} onClick={onSampleFileDownload}>
                  <strong>Download sample file</strong>
                </a>
              </div>
            ) : null}
            <div className="modal-info partner-submerchant">
              <h5 style={{ fontSize: '16px' }}>Keep in mind</h5>
              <ol className="validate-modal-ul">
                <li>
                  File should follow the template format. Download&nbsp;
                  <a className="btn-link" href={sampleUrl} onClick={onSampleFileDownload}>
                    <strong>sample file</strong>
                  </a>
                  &nbsp;for the template.
                </li>
                {batchType === 'partner_submerchant_invite' && user.isPartnershipForXEnabled && (
                  <li>
                    Name and email fields are mandatory for each account &amp; phone number is
                    optional
                  </li>
                )}
                <li>The number of accounts in the file should not exceed 500.</li>
                {batchType === 'partner_submerchant_invite' ? (
                  // // MobileNumber SMS Text will be added later
                  // <li>Once file is processed invite will be sent to all accounts on email/sms.</li>
                  <li>Once file is processed invite will be sent to all accounts on email.</li>
                ) : (
                  <li>Once the file is processed email invite will be sent to all accounts.</li>
                )}

                <li>These accounts will be listed under affiliate accounts on your dashboard.</li>
              </ol>
            </div>
            {this.props.modalInfo || (
              <div className="modal-info">
                <h5 className="modal-info-heading">
                  Getting Started with Batch Uploads?{' '}
                  <ShowWhen
                    additionalCondition={(usr) => usr.isOrgAllowedFunctionality('external_links')}
                  >
                    <DocLink className="btn btn-link m-l doc-url" href={docUrl} target="_blank">
                      View Documentation <i className="i i-external-link" />
                    </DocLink>
                  </ShowWhen>
                </h5>
                <p>Upload a batch file to continue.</p>
                <p className="modal-info-note">
                  <strong>Please note the following things before proceeding further: </strong>
                </p>
                {batchType !== 'virtual_account_edit' ? (
                  <ol className="validate-modal-ul">
                    <li>The amount mentioned should be in paise.</li>
                    {batchType &&
                      [
                        'refund',
                        'payment_transfer',
                        'transfer_reversal',
                        'linked_account_create',
                      ].indexOf(batchType) === -1 && (
                        <li>
                          The {user.isPaymentlinksV2Enabled ? 'reference id' : 'receipt id'} for all{' '}
                          {batchTypeText ? batchTypeText : titleCase(batchType)}s should be unique.
                        </li>
                      )}

                    {batchType === 'refund' ? (
                      <>
                        <li>The payment Id for all refunds should be unique.</li>
                        <li>
                          Mention refund speed of each payment Id otherwise refunds will be
                          processed at default refund speed (check{' '}
                          <strong
                            className="btn-link"
                            onClick={() => {
                              window.rzpAnalytics?.({
                                eventCategory: `Batch ${titleCase(this.props.batchType)}`,
                                eventAction: 'Setting -  upload modal',
                                eventLabel: `Click to setting`,
                              });
                              this.props.closeModal();
                            }}
                          >
                            <Link
                              to={{
                                pathname: '/config',
                                hash: 'instantrefunds',
                              }}
                            >
                              settings
                            </Link>
                          </strong>{' '}
                          for default refund speed).
                        </li>
                      </>
                    ) : (
                      ''
                    )}
                    {maxRows && <li>The number of rows should not exceed {maxRows}.</li>}
                    {batchType === 'refund' ? (
                      <li>Once the batch file is submitted, it will be processed after 70 mins.</li>
                    ) : null}
                  </ol>
                ) : (
                  <ol class="validate-modal-ul">
                    <li>
                      Each row must contain a unique Virtual Account ID and it should not already be
                      in expired or closed state.
                    </li>
                    <li>
                      Each row must contain an expiry date in dd-mm-yyyy hh:mm format (e.g.
                      15-01-2021 23:59).
                    </li>
                    <li>Number of rows in a batch file cannot exceed 10000.</li>
                    <li>Batch file can take upto 70 min to process.</li>
                  </ol>
                )}

                <p className="download-sample-file-p">
                  In case of any issues, please{' '}
                  <a className="btn-link" href={sampleUrl} onClick={onSampleFileDownload}>
                    <strong>download sample file</strong>
                  </a>
                </p>
              </div>
            )}
            {batchType === 'refund' && (
              <p className="process-instant-batch">
                {' '}
                <img src={`${window.cdnBaseUrl}/static/assets/notifs/instant-refunds.svg`} /> Retain
                customers and improve trust by issuing refunds instantly. &nbsp;{' '}
                <DocLink
                  onClick={() => {
                    window.rzpAnalytics?.({
                      eventCategory: `Batch ${titleCase(this.props.batchType)}`,
                      eventAction: 'Learn more - upload modal',
                      eventLabel: `Click to learn more`,
                    });
                  }}
                  target="_blank"
                  href="https://razorpay.com/docs/payment-gateway/refunds/#how-instant-refunds-work"
                >
                  <strong className="btn-link">Learn more</strong>{' '}
                </DocLink>
              </p>
            )}
          </>
        ) : null}

        {/* Show batch modal error-info when file upload */}
        {fileUrl ? (
          <div className="modal-info error stretch">
            <div className="row">
              <div className="col-sm-9">
                <h4 className="m-b">How to fix an error?</h4>
                <p>
                  The errors are marked in a the same file in a separate column. Download the error
                  file, fix the errors and upload again to proceed.
                </p>
              </div>
              <div className="col-sm-3">
                <a
                  className="btn btn-primary btn-block"
                  href={fileUrl}
                  onClick={onErrorReportDownload}
                >
                  {' '}
                  <i className="i i-download m-r" /> Download File
                </a>
              </div>
            </div>
          </div>
        ) : null}
      </div>
    );
  }
}

export default connect(null, (dispatch) => bindActionCreators({ closeModal }, dispatch))(
  BatchValidateModal,
);
