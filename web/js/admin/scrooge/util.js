import React from 'react';
import { adminPost } from 'common/fetch';
import { ModalContent } from 'component/Modal';
import {
  openModal,
  closeModal,
  notifySuccess,
  notifyError,
} from 'common/modal';
import { splitAndFilter } from 'common/util';

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

export function bulkUpdateRefundsReference1(refundIds, mode) {
  let postData = {
    refund_reference1: [],
  };

  refundIds.forEach(refundId => {
    const entity = splitAndFilter(refundId, ':');

    if (entity.length === 2) {
      postData.refund_reference1.push({
        id: entity[0],
        reference1: entity[1],
      });
    } else if (entity.length === 1) {
      postData.refund_reference1.push({
        id: entity[0],
        reference1: 'NA',
      });
    } else {
      notifyError('Format Error in ' + refundId);
    }
  });

  adminPost({
    url: `${mode}/scrooge/refunds/bulk-reference1-update`,
    data: postData,
  }).then(response => {
    if (response) {
      notifySuccess('Update reference1 request is successful');
      closeModal();
      openModal(
        <ModalContent header="Bulk Reference1 Update Response" noPadding>
          <div class="code" style={{ width: '650px' }}>
            {JSON.stringify(response, null, 4)}}
          </div>
        </ModalContent>
      );
    } else {
      notifyError('Update reference1 request failed');
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
