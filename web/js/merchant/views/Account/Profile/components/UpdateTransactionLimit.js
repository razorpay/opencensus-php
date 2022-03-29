import { useState } from 'react';
import { connect } from 'react-redux';
import ModalHeader from 'common/ui/ModalHeader';
import * as ModalActions from 'merchant_common/reducers/modals';
import * as NotificationsActions from 'merchant_common/reducers/notifications';
import { bindActionCreators, compose } from 'redux';
import Input from 'common/new-ui/Input';
import { merchantFetch } from 'merchant/utils/ajax';
import Amount from 'common/ui/Amount';
import FileUpload from 'merchant/components/File/Upload';
import { getCommonAnalyticsProperties, rupeesToPaise } from 'common/utils/rzp-utils';
import { analyticsTrack } from 'common/utils/analytics';

function UpdateTransactionLimit(props) {
  const { user, transactionType, showNotification, closeModal, onComplete } = props;
  const [file, setfile] = useState(null);
  const [isReasonValid, setisReasonValid] = useState(null); // Validity => minimum 100 words
  const isTypeDomestic = transactionType === 'domestic';
  const modalTitle = isTypeDomestic
    ? 'Increase Domestic Transaction Limit'
    : 'Increase International Transaction Limit';
  const amountValue = isTypeDomestic
    ? user.merchant.max_payment_amount
    : user.merchant.max_international_payment_amount;

  const save = async (e) => {
    e.preventDefault();
    const formFieldValues = Array.from(e.target.elements).reduce((acc, ele) => {
      acc[ele.name] = ele.value;
      return acc;
    }, {});
    const formData = new FormData();
    if (file instanceof File) formData.append('transaction_limit_increase_invoice_url', file);

    formData.append('new_transaction_limit_by_merchant', rupeesToPaise(+formFieldValues.limit));
    formData.append('transaction_limit_increase_reason', formFieldValues.reason);
    formData.append('transaction_type', transactionType);

    // Track when limit is submitted
    analyticsTrack({
      objectName: isTypeDomestic
        ? 'Transaction limit submitted'
        : 'International Transaction limit submitted',
      actionName: 'merchant clicks on submit',
      screen: 'My account screen',
      properties: {
        currentLimit: `${amountValue}`,
        newLimit: `${formFieldValues.limit}`,
        limitReason: `${formFieldValues.description}`,
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });

    const response = await merchantFetch({
      url: 'merchant/transaction_limit',
      method: 'POST',
      mode: 'live',
      data: formData,
    }).catch(({ errors }) => {
      showNotification({ type: 'error', message: errors });

      // Track API failure
      analyticsTrack({
        objectName: isTypeDomestic
          ? 'Transaction limit workflow created'
          : 'International Transaction limit workflow created',
        actionName: 'API response',
        screen: 'My account screen',
        properties: {
          result: 'Failure',
          failureReason: `${errors}`,
          ...getCommonAnalyticsProperties(window.rzp_user),
        },
      });
    });
    if (response) {
      showNotification({
        type: 'success',
        message: `Request sent successfully`,
      });
      onComplete();
      closeModal();

      // Track API success
      analyticsTrack({
        objectName: isTypeDomestic
          ? 'Transaction limit workflow created'
          : 'International Transaction limit workflow created',
        actionName: 'API response',
        screen: 'My account screen',
        properties: {
          result: 'Success',
          newLimit: `${formFieldValues.limit}`,
          ...getCommonAnalyticsProperties(window.rzp_user),
        },
      });
    }
  };

  const handleFileChange = (uploadedFile, _) => {
    setfile(uploadedFile);

    // Track file upload
    analyticsTrack({
      objectName: isTypeDomestic ? 'Upload invoice' : 'Change International transaction limit',
      actionName: 'Upload invoice clicked',
      screen: 'My account screen',
      properties: {
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });
  };

  const onCloseClick = () => setfile(null);

  const onBiggerFileSize = () => {
    const err = new Error('Document too large. Max limit 1MB', { cause: 'FILE_SIZE_EXCEEDED' });
    setfile(err);
  };

  const onTextInputBlur = (e) => {
    const input = e.target.value;
    const tokens = input.split(' ');

    if (tokens.length >= 100) setisReasonValid(true);
    else setisReasonValid(false);
  };

  return (
    <div>
      <ModalHeader title={modalTitle} onCloseClick={closeModal} />
      <div className="modal-body transaction-limit-update-form">
        <form onSubmit={save}>
          <div>
            You can request to increase the transaction limit per payment made to you once in 30
            days.
          </div>

          <span className="highlight-limit">
            Current limit:
            <Amount value={amountValue} currency="INR" />
          </span>

          <div className="actions-header">
            <Input name="limit" label="Required limit" required type="number" addonBefore="₹" />
            <Input.Textarea
              placeholder="Min 100 Words"
              label="Why do you want to increase limit?"
              required
              name="reason"
              onBlur={onTextInputBlur}
            />
            {isReasonValid === false && <p className="notify-error">Minimum 100 words required</p>}

            <div className="note">
              Attach an invoice which has amount close to limit required by you
            </div>

            <FileUpload
              accept={['jpg', 'png', 'pdf']}
              maxSize={1048576} // 1MB
              showCloseBtn
              showFileSize={true}
              showAcceptInfo={false}
              customClassName="transactionlimit-fileupload"
              onBiggerFileSize={onBiggerFileSize}
              onFileChange={handleFileChange}
              onCloseClick={onCloseClick}
            />
            {file?.message && <p className="notify-error">{file.message}</p>}
          </div>

          <div className="Modal__actions">
            <button
              type="submit"
              className="btn btn-primary btn-block"
              disabled={isReasonValid === false}
            >
              Submit Details
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}

const mapStateToProps = (state) => {
  return {
    user: state.session.user,
  };
};

const mapDispatchToProps = (dispatch) => {
  return bindActionCreators(
    {
      ...ModalActions,
      ...NotificationsActions,
    },
    dispatch,
  );
};

export default compose(connect(mapStateToProps, mapDispatchToProps))(UpdateTransactionLimit);
