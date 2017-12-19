import { openModal, closeModal, confirm } from 'common/modal';
import { adminPost } from 'util/fetch';
import { notifyError, notifySuccess } from 'common/modal';

import ShowWhen from 'admin/components/ShowWhen';
import AsyncButton from 'ui/AsyncButton';

// refund Actions
export default ({ entity, mode }) => {
  function retryRefund(body) {
    return adminPost({
      route_name: 'refund_verify_failed',
      mode,
      url_params: {
        id: entity.id,
      },
    })
      .then(data => {
        if (data) {
          notifySuccess('Refund is successful');
          setTimeout(() => window.location.reload(), 1500);
        }
      })
      .catch(err => {
        notifyError(
          'There was an error while retrying to refund. ' + JSON.stringify(err)
        );
      });
  }

  return (
    <ShowWhen permission="retry_refund_failed">
      {entity.status === 'failed' && (
        <AsyncButton
          class="btn btn-default text-primary"
          pendingClass="btn btn-default text-primary btn-pending"
          confirm="Are you sure you want retry this refund?"
          onClick={retryRefund}
        >
          Retry Refund
          <span class="spin-btn" />
        </AsyncButton>
      )}
    </ShowWhen>
  );
};
