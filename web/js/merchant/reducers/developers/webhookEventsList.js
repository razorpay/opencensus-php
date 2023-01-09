import moment from 'moment';
import { makeCollectionReducer } from 'merchant/reducers/collection';
import { merchantFetch } from 'merchant/utils/ajax';

const WEBHOOK_EVENTS = 'WEBHOOK_EVENTS';

export const fetchWebhookEventList = (params) => {
  return {
    type: `${WEBHOOK_EVENTS}_FETCH`,
    payload: merchantFetch({
      url: 'developer_console/merchant/outgoing/apis',
      method: 'post',
      data: {
        range: {
          from: moment(Number(params.from)).unix(),
          to: moment(Number(params.to)).unix(),
        },
        terms: {
          webhook_id: {
            values: [params.webhookId],
          },
        },
      },
    }),
  };
};

const webhookEventsListReducer = makeCollectionReducer(
  WEBHOOK_EVENTS,
  {
    [`${WEBHOOK_EVENTS}_FETCH::SUCCESS`]: (state, action) => {
      return {
        ...state,
        loading: false,
        items: action.payload.data.body.result,
      };
    },
  },
  {
    loading: true,
    items: [],
  },
);

export default webhookEventsListReducer;
