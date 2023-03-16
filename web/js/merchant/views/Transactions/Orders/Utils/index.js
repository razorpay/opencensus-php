import { addIdLinkAndSelfServeInitiate } from 'merchant/views/Transactions/utils';

export const makeIdLink =
  (type) =>
  (item, initiatePage, initiatePoint = 'orders-table') => {
    const id = item[`${item.entity === type ? '' : `${type}_`}id`];
    let url = /orders/;
    const element = <code>{id}</code>;
    url += id;

    const selfServeActionName = 'Order Details Fetched';

    return addIdLinkAndSelfServeInitiate(
      initiatePage,
      initiatePoint,
      selfServeActionName,
      url,
      element,
    );
  };
