import { makeCollectionReducer } from 'merchant/reducers/collection';
import { merge } from 'common/utils/immutable';
import { merchantFetch } from 'merchant/utils/ajax';
import moment from 'moment';

const WEBHOOK_LOGS = 'WEBHOOK_LOGS';

export const fetchWebhookLogs = (params) => {
  const { duration, eventType, webhookId, skip, count, searchField, httpStatus } = params;
  const { from, to } = duration;

  const statusMap = {
    '2xx': ['200', '201', '202', '204'],
    '3xx': ['300', '301', '302', '304'],
    '4xx': ['400', '401', '403', '404'],
    '5xx': ['500', '502', '503'],
  };

  const requestBody = {
    range: {
      from: moment(Number(from)).unix(),
      to: moment(Number(to)).unix(),
    },
    pagination: {
      from: skip,
      size: count,
    },
    q: searchField,
  };

  const terms = {
    webhook_id: {
      values: [webhookId],
    },
  };

  if (httpStatus) {
    terms['response.http_status_code'] = {
      values: statusMap[httpStatus],
    };
  }

  if (eventType) {
    terms['request.route_name'] = {
      values: [eventType],
    };
  }

  requestBody.terms = terms;

  return {
    type: `${WEBHOOK_LOGS}_FETCH`,
    payload: merchantFetch({
      url: 'developer_console/merchant/outgoing/search',
      method: 'post',
      data: requestBody,
    }),
  };
};

const webhooksLogsReducer = makeCollectionReducer(
  WEBHOOK_LOGS,
  {
    [`${WEBHOOK_LOGS}_FETCH::SUCCESS`]: (state, action) => {
      return merge(state, {
        loading: false,
        items: action.payload.data.body.result.map((item) => ({
          ...item,
          timestamp: Number(item.timestamp),
        })),
        count: action.payload.data.total_records,
      });
    },
  },
  {
    loading: true,
    items: [],
    count: 0,
  },
);

export default webhooksLogsReducer;
