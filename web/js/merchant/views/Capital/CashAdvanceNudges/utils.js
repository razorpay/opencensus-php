import moment from 'moment';
import { getItem, setItem } from 'common/utils/localStorage';
import { APPLICATION_STATES } from 'merchant/views/Capital/Loans/constants';
import store from 'merchant/store';
import { PILL_VARIENTS, DOCS_ITEMS, OFFER_ITEMS } from './constants';

export const getPillContent = (variant) => {
  switch (variant) {
    case PILL_VARIENTS.PRE_APPLICATION:
      return [<span key="need-money">Need more money?</span>];

    case PILL_VARIENTS.PROGRESS_APPLICATION:
      return [
        <>
          Unlock upto ₹<span>10 lakh</span>
        </>,
        <span key="few-steps">Few steps remaining</span>,
        <>
          Unlock upto ₹<span>10 lakh</span>
        </>,
        <span key="few-steps-2">Few steps remaining</span>,
      ];

    default:
      return null;
  }
};

const CASH_ADVANCE_PILL_RENDER_DATE = 'CASH_ADVANCE_PILL_RENDER_DATE';
export const getPillRenderDateKey = () => {
  const merchantId = store.getState()?.session?.user?.current;
  return `${CASH_ADVANCE_PILL_RENDER_DATE}--${merchantId}`;
};

export const setPillRenderDate = () => {
  setItem(getPillRenderDateKey(), moment().format('YYYY-MM-DD'));
};

export const getPillRenderDate = () => {
  return getItem(getPillRenderDateKey());
};

export const getTimelineData = (status) => {
  const data = {
    items: [],
    img: null,
  };

  if (status === APPLICATION_STATES.PREVERIFICATION_IN_PROGRESS) {
    data.items = OFFER_ITEMS;
    data.img = 'offer';
  } else if (status === APPLICATION_STATES.DOCUMENTS_UNDER_REVIEW) {
    data.items = DOCS_ITEMS;
    data.img = 'docs';
  }

  return data;
};

export const getExpiresIn = (date) => {
  const diff = 25 - moment().diff(date, 'days');
  return diff > 0 ? diff : 1;
};

export const isApplicationClosedOrRejected = (application) => {
  return (
    application?.status === APPLICATION_STATES.RZP_REJECTED ||
    application?.status === APPLICATION_STATES.CLOSED
  );
};

export const getPillVariant = (latestApplication) => {
  const isApplicationInvalid = isApplicationClosedOrRejected(latestApplication);

  switch (true) {
    case !latestApplication:
    case latestApplication && isApplicationInvalid:
      return PILL_VARIENTS.PRE_APPLICATION;

    case latestApplication && !isApplicationInvalid:
      return PILL_VARIENTS.PROGRESS_APPLICATION;

    default:
      return null;
  }
};

export const getCurrentBalance = (withdrawlConfig) => {
  const { data, loading } = withdrawlConfig;
  if (loading) return 0;

  const balance =
    Number(data?.configuration?.internal_credit_limit) -
    Number(data?.principal_outstanding_balance || 0);
  return balance > 0 ? balance : 0;
};
