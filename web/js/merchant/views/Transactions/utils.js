import { Link } from 'react-router-dom';
import { selfServeTrackInitiate } from 'common/utils/selfServeAnalytics';

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

export const getSelfServeSuccessData = (selfServeActionName, screenType) => {
  const { initiatePoint, initiatePage, screen, page } = getInitiatePointAndPageAndScreenName();

  const selfServeSuccessData = {
    selfServeAction: selfServeActionName,
    screen: screenType,
    props: {},
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
) => {
  const { hash } = window.location;

  const screen = initiatePage?.split('.')[0] || 'Transactions';
  const page = initiatePage?.split('.')[1];
  const selfServeInitiateData = {
    selfServeAction: selfServeActionName,
    props: {},
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
