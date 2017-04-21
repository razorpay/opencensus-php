import ajax from 'merchant/utils/ajax';

// const WORKFLOWS_FETCH = 'WORKFLOWS_FETCH'

// export const fetchWorkflows = () => {
//   return (dispatch) => {
//     return dispatch({
//       type: WORKFLOWS_FETCH,
//       payload: ajax('/workflows')
//     })
//   }
// }

let initialState = {
  // loading: true,
  // workflows: [],
  // count: 0
};

export default function(state = initialState, action) {
  // switch(action.type) {
  //   case `${WORKFLOWS_FETCH}::PENDING`:
  //     return state.set('loading', true)

  //   case `${WORKFLOWS_FETCH}::SUCCESS`:
  //     return state.merge({
  //       loading: false,
  //       workflows: action.payload.data.items,
  //       count: action.payload.data.count
  //     })

  //   case `${WORKFLOWS_FETCH}::ERROR`:
  //     return state.merge({
  //       loading: false,
  //       error: action.error
  //     })

  //   default:
  return state;
  // }
}
