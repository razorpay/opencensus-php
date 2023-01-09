import { makeCollectionReducer } from 'merchant/reducers/collection';
import { merchantFetch } from 'merchant/utils/ajax';
import moment from 'moment';
import { merge } from 'common/utils/immutable';

const requestURI = 'developer_console/merchant/outgoing/stats';

const WEBHOOK_STATS = 'WEBHOOK_STATS';

export function fetchStats(params) {
  const { duration, eventType, webhookId } = params;
  const { from, to } = duration;

  const requestBody = {
    range: {
      from: moment(Number(from)).unix(),
      to: moment(Number(to)).unix(),
    },
  };

  const terms = {
    webhook_id: {
      values: [webhookId],
    },
  };

  if (eventType) {
    terms['request.route_name'] = {
      values: [eventType],
    };
  }

  requestBody.terms = terms;

  return {
    type: `${WEBHOOK_STATS}_FETCH`,
    payload: merchantFetch({
      url: requestURI,
      method: 'post',
      data: requestBody,
    }),
  };
}

const webhookStatsReducer = makeCollectionReducer(
  WEBHOOK_STATS,
  {
    [`${WEBHOOK_STATS}_FETCH::SUCCESS`]: (state, action) => {
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
    [`${WEBHOOK_STATS}_FETCH::ERROR`]: (state, action) => {
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

export default webhookStatsReducer;
