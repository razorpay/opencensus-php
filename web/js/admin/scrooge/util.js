import { adminPost } from 'common/fetch';
import { closeModal, notifySuccess } from 'common/modal';

export default function bulkUpdateRefundsStatus(refundIds, event, mode) {
  let postData = {
    refunds: [],
  };

  refundIds.forEach(refundId => {
    postData.refunds.push({
      refund_id: refundId,
      event: event,
    });
  });

  adminPost({
    url: `${mode}/scrooge/refunds/bulk-status-update`,
    data: postData,
  }).then(response => {
    if (response) {
      notifySuccess('Update status request is successful');
      closeModal();
    }
  });
}
