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
  const [file, setfile] = useState(null);
  const [isReasonValid, setisReasonValid] = useState(null); // Validity => minimum 100 words

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

    // Track when limit is submitted
    analyticsTrack({
      objectName: 'Transaction limit submitted',
      actionName: 'merchant clicks on submit',
      screen: 'My account screen',
      properties: {
        currentLimit: `${props.user.merchant.max_payment_amount}`,
        newLimit: `${formFieldValues.limit}`,
        limitReason: `${formFieldValues.description}`,
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });

    try {
      const response = await merchantFetch({
        url: 'merchant/transaction_limit',
        method: 'POST',
        data: formData,
      });
      if (response) {
        props.showNotification({
          type: 'success',
          message: `Request sent successfully`,
        });

        props.onComplete();
        props.closeModal();

        // Track API success
        analyticsTrack({
          objectName: 'Transaction limit workflow created',
          actionName: 'API response',
          screen: 'My account screen',
          properties: {
            result: 'Success',
            newLimit: `${formFieldValues.limit}`,
            ...getCommonAnalyticsProperties(window.rzp_user),
          },
        });
      }
    } catch ({ errors }) {
      props.showNotification({
        type: 'error',
        message: errors,
      });

      // Track API failure
      analyticsTrack({
        objectName: 'Transaction limit workflow created',
        actionName: 'API response',
        screen: 'My account screen',
        properties: {
          result: 'Failure',
          failureReason: `${errors}`,
          ...getCommonAnalyticsProperties(window.rzp_user),
        },
      });
    }
  };

  const { user: usr } = props;

  const handleFileChange = (uploadedFile, _) => {
    setfile(uploadedFile);

    // Track file upload
    analyticsTrack({
      objectName: 'Upload invoice',
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
      <ModalHeader title="Increase Transaction Limit" onCloseClick={props.closeModal} />

      <div class="modal-body transaction-limit-update-form">
        <form onSubmit={save}>
          <div>
            You can request to increase the transaction limit per payment made to you once in 30
            days.
          </div>

          <span class="highlight-limit">
            {' '}
            Current limit: <Amount value={usr.merchant.max_payment_amount} currency="INR" />
          </span>

          <div class="actions-header">
            <Input name="limit" label="Required limit" required type="number" addonBefore="₹" />

            <Input.Textarea
              placeholder="Min 100 Words"
              label="Why do you want to increase limit?"
              required
              name="reason"
              onBlur={onTextInputBlur}
            />
            {isReasonValid === false && <p class="notify-error">Minimum 100 words required</p>}

            <div class="note">
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
            {file?.message ? <p class="notify-error">{file.message}</p> : null}
          </div>

          <div class="Modal__actions">
            <button
              type="submit"
              class="btn btn-primary btn-block"
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
