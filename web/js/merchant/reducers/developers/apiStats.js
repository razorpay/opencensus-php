import { makeCollectionReducer } from 'merchant/reducers/collection';
import { merchantFetch } from 'merchant/utils/ajax';
import moment from 'moment';
import { merge } from 'common/utils/immutable';

const requestURI = 'developer_console/incoming/fetch/stats';

const API_STATS = 'API_STATS';

export function fetchStats(filters) {
  const { duration } = filters;
  const { from, to } = duration;

  const requestBody = {
    range: {
      from: moment(Number(from)).unix(),
      to: moment(Number(to)).unix(),
    },
    terms: {},
  };

  return {
    type: `${API_STATS}_FETCH`,
    payload: merchantFetch({
      url: requestURI,
      method: 'post',
      data: requestBody,
    }),
  };
}

const apiStatsReducer = makeCollectionReducer(
  API_STATS,
  {
    [`${API_STATS}_FETCH::SUCCESS`]: (state, action) => {
      return merge(state, {
        loading: false,
        data: {
          stats: action.payload.data.body?.stats.map((stat) => ({
            ...stat,
            count: Number(stat.count),
          })),
        },
      });
    },
    [`${API_STATS}_FETCH::ERROR`]: (state, action) => {
      return merge(state, {
        loading: false,
        data: null,
        error: action.payload,
      });
    },
  },
  {
    loading: true,
    data: null,
    error: null,
  },
);

export default apiStatsReducer;
