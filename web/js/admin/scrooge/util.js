import { adminPost } from 'common/fetch';
import { closeModal, notifySuccess } from 'common/modal';

export function bulkUpdateRefundsStatus(refundIds, event, mode) {
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

export function priorityRefunds(refundIds, mode) {
  let postData = {
    refund_ids: [],
  };

  refundIds.forEach(refundId => {
    postData.refund_ids.push(refundId);
  });

  adminPost({
    url: `${mode}/scrooge/refunds/enqueue`,
    data: postData,
  }).then(response => {
    if (response) {
      notifySuccess('Refunds have been pushed into queue');
      closeModal();
    }
  });
}
