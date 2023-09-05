import { addIdLinkAndSelfServeInitiate } from 'merchant/views/Transactions/v1/utils';

export const makeIdLink =
  (type) =>
  (item, initiatePage, initiatePoint = 'refunds-table') => {
    const id = item[`${item.entity === type ? '' : `${type}_`}id`];
    let url = /refunds/;
    const element = <code>{id}</code>;
    url += id;

    const { hash } = window.location;

    if (hash) {
      url += hash;
    }
    const selfServeActionName = 'Refund Details Fetched';
    return addIdLinkAndSelfServeInitiate(
      initiatePage,
      initiatePoint,
      selfServeActionName,
      url,
      element,
    );
  };
