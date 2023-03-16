import { addIdLinkAndSelfServeInitiate } from 'merchant/views/Transactions/utils';

export const makeIdLink =
  (type) =>
  (item, initiatePage, initiatePoint = 'disputes-table') => {
    const id = item[`${item.entity === type ? '' : `${type}_`}id`];
    let url = /disputes/;
    const element = <code>{id}</code>;
    url += id;

    const selfServeActionName = 'Dispute Details Fetched';

    return addIdLinkAndSelfServeInitiate(
      initiatePage,
      initiatePoint,
      selfServeActionName,
      url,
      element,
    );
  };
