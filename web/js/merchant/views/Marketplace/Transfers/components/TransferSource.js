import { Link } from 'react-router-dom';
import { selfServeTrackInitiate } from 'common/utils/selfServeAnalytics';
import { SelfServeActionPages } from 'common/constant/enums';

const URLS = {
  order_: (source, initiatePoint, initiatePage) =>
    `/orders/${source}?init_point=${initiatePoint}&init_page=${initiatePage}`,
  pay_: (source, initiatePoint, initiatePage) =>
    `/payments/${source}?init_point=${initiatePoint}&init_page=${initiatePage}`,
};

const selfServeActionName = {
  order_: 'Order Details Fetched',
  pay_: 'Payment Details Fetched',
};

export default function TransferSource(props) {
  const { source, initiatePoint = 'transfer-details' } = props;

  let initiatePage = SelfServeActionPages.RouteTransfers;

  const params = new Proxy(new URLSearchParams(window.location?.search), {
    get: (searchParams, prop) => searchParams.get(prop),
  });

  if (params?.init_page) {
    initiatePage = params?.init_page;
  }

  if (!source) return '-';

  let URL;

  const selfServeInitiateData = {
    page: 'Transfers',
    screen: 'Route',
    props: {
      initiatePoint,
    },
  };

  Object.keys(URLS).forEach((key) => {
    if (source.includes(key)) {
      URL = URLS[key](source, initiatePoint, initiatePage);

      selfServeInitiateData.selfServeAction = selfServeActionName[key];
      if (window?.session_id) selfServeInitiateData.props.sessionId = window.session_id;
    }
  });

  if (!URL) {
    return 'Direct Transfer';
  }

  return (
    <Link
      to={URL}
      onClick={() => {
        selfServeTrackInitiate(selfServeInitiateData);
      }}
    >
      {source}
    </Link>
  );
}
