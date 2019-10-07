import { PossibleStatuses } from 'rzp/utils/constants';

import ShowTransferPreviewModal from '../Transfers/ShowTransferPreviewModal';

const { done } = PossibleStatuses;

export const getQuickGuideData = {
  LinkedAccount: status => {
    if (status === done) {
      return {
        title: '1. Linked Account Created',
        content:
          'Easily add your vendor/seller/service provider account details as a linked account.',
      };
    }

    return {
      title: '1. Create a Linked Account',
      content:
        'Easily add your vendor/seller/service provider account details as a linked account.',
    };
  },
  Transfers: status => {
    if (status === done) {
      return {
        title: '2. Transfer Initiated',
        content: (
          <div>
            Initiate the payment to be transferred to a linked account from your
            transactions. <br />
            <ShowTransferPreviewModal>Show me how</ShowTransferPreviewModal>
          </div>
        ),
      };
    }

    return {
      title: '2. Initiate transfers',
      content: (
        <div>
          Initiate the payment to be transferred to a linked account from your
          transactions. <br />
          <ShowTransferPreviewModal>Show me how</ShowTransferPreviewModal>
        </div>
      ),
    };
  },
};
