import ajax from 'merchant/utils/ajax'
import { set } from 'rzp/utils/immutable'

// graph data
// fetched everytime date is changed
const FETCH_ANALYTICS = 'FETCH_ANALYTICS'

// numbers apart from graph
const FETCH_AGGREGATIONS = 'FETCH_AGGREGATIONS'

const intervals = [
  {
    value: 'day',
    label: 'Daily'
  },
  {
    value: 'week',
    label: 'Weekly'
  },
  {
    value: 'month',
    label: 'Monthly'
  },
  {
    value: 'year',
    label: 'Yearly'
  }
]

let initialState = {
  analytics: null,
  aggregations: null
}

export default function (state = initialState, action) {
  switch(action.type) {
    case `${FETCH_ANALYTICS}::PENDING`:
      return set(state, 'analytics', null)

    case `${FETCH_ANALYTICS}::SUCCESS`:
      return set(state, 'analytics', action.payload)

    case `${FETCH_AGGREGATIONS}::SUCCESS`:
      return set(state, 'aggregations', action.payload)

    default:
      return state
  }
}

export const fetchAnalytics = (state)=> {
  return (dispatch) => {
    return dispatch({
      type: FETCH_ANALYTICS,
      payload: ajax({
        url: '/analytics/transactions',
        data: {
          type: intervals[state.interval].value,
          from: state.from.unix(),
          to: state.to.unix()
        }
      })
    })
  }
}

export const fetchAggregrations = ()=> {
  return (dispatch) => {
    return dispatch({
      type: FETCH_AGGREGATIONS,
      payload: Promise.all([
        ajax('/analytics/aggregations'),
        ajax('/analytics/payment/aggregations'),
        ajax('/user/generic', {
          appendModeInQueryParam: true,
          data: {
            route_name: 'balance_fetch'
          }
        }),
        ajax('/user/generic', {
          appendModeInQueryParam: true,
          data: {
            route_name: 'payment_fetch_multiple'
          }
        }),
        ajax('/user/generic', {
          appendModeInQueryParam: true,
          data: {
            route_name: 'refund_fetch_multiple'
          }
        }),
        ajax('/user/generic', {
          appendModeInQueryParam: true,
          data: {
            route_name: 'setl_fetch_multiple'
          }
        })
      ]).then((values) => {
        return {
          entity_totals: values[0],
          payment_breakup: values[1],
          current_balance: values[2],
          recent_payments: values[3],
          recent_refunds: values[4],
          recent_settlements: values[5]
        }
      })
    })
  }
}