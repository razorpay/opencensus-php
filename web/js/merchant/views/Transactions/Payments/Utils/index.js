import { selfServeTrackInitiate } from 'common/utils/selfServeAnalytics';
import { Link } from 'react-router-dom';

export const makeIdLink = (type) => (item, initiatePage) => {
  const id = item[`${item.entity === type ? '' : `${type}_`}id`];
  let url = /payments/;
  const element = <code>{id}</code>;
  url += id;

  const { hash } = window.location;
  const initiatePoint = 'payments-table';

  if (hash) {
    url += hash;
  }

  const screen = initiatePage?.split('.')[0];
  const page = initiatePage?.split('.')[1];
  const selfServeInitiateData = {
    selfServeAction: 'Payment Details Fetched',
    props: {},
  };
  selfServeInitiateData.props.initiatePoint = initiatePoint;
  if (screen) selfServeInitiateData.screen = screen;
  if (page) selfServeInitiateData.page = page;
  if (window && window.session_id) selfServeInitiateData.props.sessionId = window.session_id;

  url = `${url}?init_point=${initiatePoint}&init_page=${initiatePage}`;

  const onLinkClick = () => selfServeTrackInitiate(selfServeInitiateData);

  return (
    <Link to={url} onClick={onLinkClick}>
      {element}
    </Link>
  );
};

export const _paymentId = (item, page) => {
  const intermediateElement = makeIdLink('payment')(item, page);
  return <div>{intermediateElement}</div>;
};
