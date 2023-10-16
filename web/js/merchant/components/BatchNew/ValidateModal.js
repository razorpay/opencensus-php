import { Component } from 'react';
import {
  Box,
  Heading,
  List,
  Link as LinkBlade,
  ListItem,
  ListItemText,
} from '@razorpay/blade/components';
import { connect } from 'react-redux';
import { Link as RouterLink } from 'react-router-dom';
import { bindActionCreators } from 'redux';

import { withI18Service } from 'common/i18';
import { titleCase, monetaryUnitText } from 'common/utils/rzp-utils';
import { DocLink } from 'merchant/components/DocsLink';
import FileUpload from 'merchant/components/File/Upload';
import ShowWhen from 'merchant/components/ShowWhen';
import {
  BATCH_TYPE,
  BATCH_UPLOAD_POINTS,
} from 'merchant/views/PaymentPages/PaymentPages/constants';
import { closeModal } from 'merchant_common/reducers/modals';

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
      onSampleFileDownload = false,
      onErrorReportDownload = () => {},
      user,
      batchClass,
      acceptFileInfo,
      accept,
      disabled,
      onDocumentClick = () => {},
      onError = () => {},
      onSuccess = () => {},
      isDragDropDisabled,
      hideCloseBtn,
      isSampleFileLoading = false,
      shouldShowSampleDownloadBtn = false,
      nullStatusNotification = null,
      i18: { isConfigTagEnabled },
    } = this.props;

    let { batchTypeText = '' } = this.props;

    if (batchType === 'payment_link_v2') {
      batchTypeText = 'Payment Link'; // We don't want to unnececssarily expose that merchant is using V2
    }
    const countryCode = user?.merchant?.country_code ?? 'IN';
    const monetaryUnit = monetaryUnitText(countryCode);
    const isInviteFlow = [
      'partner_submerchant_invite',
      'partner_submerchant_referral_invite',
    ].includes(batchType);
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
            showCloseBtn={!hideCloseBtn}
            batchType={batchType}
            showStagedFileStatus
            showFileSize={false}
            onError={onError}
            onSuccess={onSuccess}
            disabled={disabled}
            isDragDropDisabled={isDragDropDisabled}
          />
          {notifyMsg && (
            <h5 className={`notification ${status}`}>
              <i className="i i-info-circle m-r" />
              {notifyMsg}
            </h5>
          )}
          {!status && !notifyMsg ? nullStatusNotification : null}
        </div>

        {/* Show batch upload modal info when no file uploaded */}
        {!status || status === 'exceed' ? (
          <>
            <div className="modal-info partner-submerchant">
              <Box
                display="flex"
                flexDirection="column"
                gap="spacing.2"
                justifyContent="center"
                alignItems="flex-start"
                paddingTop="spacing.3"
                marginBottom="spacing.1"
                backgroundColor="surface.background.level3.lowContrast"
              >
                <Heading>Keep in mind</Heading>
                <Box
                  display="flex"
                  flexDirection="column"
                  gap="spacing.3"
                  justifyContent="center"
                  alignItems="center"
                >
                  <List size="small">
                    <ListItem>
                      <ListItemText>
                        File should follow the template format. Download&nbsp;
                        <LinkBlade href={sampleUrl} onClick={onSampleFileDownload}>
                          sample file
                        </LinkBlade>{' '}
                        for the template.
                      </ListItemText>
                    </ListItem>
                    {isInviteFlow ? (
                      <ListItem>
                        <ListItemText>
                          Name and email fields are mandatory for each account & phone number is
                          optional
                        </ListItemText>
                      </ListItem>
                    ) : null}
                    <ListItem>
                      <ListItemText>
                        The number of accounts in the file should not exceed 500.
                      </ListItemText>
                    </ListItem>
                    <ListItem>
                      {isInviteFlow ? (
                        <ListItemText>
                          Once file is processed invite will be sent to all accounts on email/sms.
                        </ListItemText>
                      ) : (
                        <ListItemText>
                          Once the file is processed email invite will be sent to all accounts.
                        </ListItemText>
                      )}
                    </ListItem>
                    <ListItem>
                      <ListItemText>
                        These accounts will be listed under affiliate accounts on your dashboard.
                      </ListItemText>
                    </ListItem>
                  </List>
                </Box>
              </Box>
            </div>
            {this.props.modalInfo || (
              <div className="modal-info">
                <h5 className="modal-info-heading">
                  Getting Started with Batch Uploads?
                  <ShowWhen
                    additionalCondition={(usr) => usr.isOrgAllowedFunctionality('external_links')}
                  >
                    <DocLink
                      className="btn btn-link m-l doc-url"
                      href={docUrl}
                      target="_blank"
                      onClick={onDocumentClick}
                    >
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
                    <li>The amount mentioned should be in {monetaryUnit}.</li>
                    {batchType &&
                      [
                        'refund',
                        'payment_transfer',
                        'transfer_reversal',
                        'linked_account_create',
                        BATCH_TYPE,
                      ].indexOf(batchType) === -1 && (
                        <li>
                          The {user?.isPaymentlinksV2Enabled ? 'reference id' : 'receipt id'} for
                          all {batchTypeText ? batchTypeText : titleCase(batchType)}s should be
                          unique.
                        </li>
                      )}

                    {batchType === BATCH_TYPE ? <li>{BATCH_UPLOAD_POINTS[1]}</li> : null}

                    {batchType === 'refund' ? (
                      user.isOrgCurlec ? (
                        'Mention refund speed of each payment Id as normal and refunds will be processed at default refund speed.'
                      ) : (
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
                              <RouterLink
                                to={{
                                  pathname: '/config',
                                  hash: 'instantrefunds',
                                }}
                              >
                                settings
                              </RouterLink>
                            </strong>{' '}
                            for default refund speed).
                          </li>
                        </>
                      )
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
                      Each row must contain a unique Customer Identifier ID and it should not
                      already be in expired or closed state.
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
                  <ShowWhen additionalCondition={() => sampleUrl}>
                    <LinkBlade
                      href={sampleUrl}
                      onClick={onSampleFileDownload}
                      accessibilityLabel="batch-upload-download-sample-file"
                    >
                      download sample file
                    </LinkBlade>
                  </ShowWhen>
                  <ShowWhen additionalCondition={() => !sampleUrl && shouldShowSampleDownloadBtn}>
                    <LinkBlade
                      variant="button"
                      onClick={onSampleFileDownload}
                      isDisabled={isSampleFileLoading}
                      accessibilityLabel="batch-upload-download-sample-file"
                    >
                      download Sample File
                    </LinkBlade>
                  </ShowWhen>
                </p>
              </div>
            )}
            {batchType === 'refund' && !isConfigTagEnabled('refunds.instant_refunds') && (
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
  withI18Service(BatchValidateModal),
);
