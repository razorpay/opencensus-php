import { Link } from 'react-router-dom';

import { selfServeTrackInitiate } from 'common/utils/selfServeAnalytics';
import { getUser } from 'merchant/store';
import { isTransactionsV2Enabled } from 'merchant/views/Transactions/v2/common/utils';

export const getInitiatePointAndPageAndScreenName = () => {
  const params = new Proxy(new URLSearchParams(window.location?.search), {
    get: (searchParams, prop) => searchParams.get(prop),
  });

  const initiatePoint = params?.init_point;
  const initiatePage = params?.init_page;
  const screen = initiatePage?.split('.')[0] || 'Transactions';
  const page = initiatePage?.split('.')[1];

  return { initiatePoint, initiatePage, screen, page };
};

export const getSelfServeSuccessData = (selfServeActionName, screenType, splitz) => {
  const user = getUser();
  const version = isTransactionsV2Enabled(splitz, user) ? 'v2' : undefined;
  const { initiatePoint, initiatePage, screen, page } = getInitiatePointAndPageAndScreenName();

  const selfServeSuccessData = {
    selfServeAction: selfServeActionName,
    screen: screenType,
    props: {},
    version,
  };

  if ((initiatePoint, initiatePage)) {
    selfServeSuccessData.props.initiatePoint = initiatePoint;
    if (screen) selfServeSuccessData.screen = screen;
    if (page) selfServeSuccessData.page = page;
    if (window?.session_id) selfServeSuccessData.props.sessionId = window.session_id;
  }

  return selfServeSuccessData;
};

export const addIdLinkAndSelfServeInitiate = (
  initiatePage,
  initiatePoint,
  selfServeActionName,
  url,
  element,
  splitz,
) => {
  const user = getUser();
  const version = isTransactionsV2Enabled(splitz, user) ? 'v2' : undefined;
  const { hash } = window.location;

  const screen = initiatePage?.split('.')[0] || 'Transactions';
  const page = initiatePage?.split('.')[1];
  const selfServeInitiateData = {
    selfServeAction: selfServeActionName,
    props: {},
    version,
  };

  selfServeInitiateData.props.initiatePoint = initiatePoint;
  if (screen) selfServeInitiateData.screen = screen;
  if (page) selfServeInitiateData.page = page;
  if (window?.session_id) selfServeInitiateData.props.sessionId = window.session_id;

  let URL = `${url}?init_point=${initiatePoint}&init_page=${initiatePage}`;

  // #hash must come after the query params: https://stackoverflow.com/a/12683131/6127580
  if (hash) {
    URL += hash;
  }

  const onLinkClick = () => selfServeTrackInitiate(selfServeInitiateData);
  return (
    <Link to={URL} onClick={onLinkClick}>
      {element}
    </Link>
  );
};

export const getPaymentReferenceNumber = (method, acquirer_data) => {
  switch (method) {
    case 'netbanking':
      return acquirer_data?.bank_transaction_id;
    case 'wallet':
      return acquirer_data?.transaction_id;
    default:
      return acquirer_data?.rrn;
  }
};
