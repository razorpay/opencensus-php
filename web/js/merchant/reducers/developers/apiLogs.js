import { makeCollectionReducer } from 'merchant/reducers/collection';
import { merge } from 'common/utils/immutable';
import { merchantFetch } from 'merchant/utils/ajax';
import moment from 'moment';

const API_LOGS = 'API_LOGS';

export const fetchApiLogs = (params) => {
  const statusMap = {
    '2xx': [200, 201, 202, 204],
    '3xx': [301, 302],
    '4xx': [400, 401, 403, 404],
    '5xx': [500, 502, 503],
  };

  const terms = {};

  if (params.httpStatus) {
    terms['response.http_status_code'] = {
      values: statusMap[params.httpStatus],
    };
  }

  return {
    type: `${API_LOGS}_FETCH`,
    payload: merchantFetch({
      url: 'developer_console/incoming/fetch/search',
      method: 'post',
      data: {
        range: {
          from: moment(Number(params.from)).unix(),
          to: moment(Number(params.to)).unix(),
        },
        pagination: {
          from: params.skip,
          size: params.count,
        },
        terms,
        q: params.searchField,
      },
    }),
  };
};

const apiLogsReducer = makeCollectionReducer(
  API_LOGS,
  {
    [`${API_LOGS}_FETCH::SUCCESS`]: (state, action) => {
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

export default apiLogsReducer;
