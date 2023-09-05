import { addIdLinkAndSelfServeInitiate } from 'merchant/views/Transactions/v1/utils';

export const makeIdLink =
  (type) =>
  (item, initiatePage, initiatePoint = 'disputes-table', splitz) => {
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
      splitz,
    );
  };
