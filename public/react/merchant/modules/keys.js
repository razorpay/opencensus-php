import { set, merge, unshift, remove } from 'rzp/utils/immutable'
import Key from 'merchant/models/Key'

const KEYS_FETCH = 'KEYS_FETCH'
const KEY_GENERATE = 'KEY_GENERATE'
const KEY_ROLL = 'KEY_ROLL'

export const fetchKeys = (params) => {
  return (dispatch) => {
    let key = new Key()
    return dispatch({
      type: KEYS_FETCH,
      payload: key.fetchAll(params)
    })
  }
}

export const generateKey = (params) => {
  var key = new Key(params)
  return (dispatch) => {
    return dispatch({
      type: key.isNew ? KEY_GENERATE : KEY_ROLL,
      payload: key.save()
    })
  }
}

let initialState = {
  loading: true,
  keys: [],
  count: 0
}

export default function (state = initialState, action) {
  switch(action.type) {
    case `${KEYS_FETCH}::PENDING`:
    case `${KEY_GENERATE}::PENDING`:
    case `${KEY_ROLL}::PENDING`:
      return merge(state, {
        loading: true
      })

    case `${KEYS_FETCH}::SUCCESS`:
      return merge(state, {
        loading: false,
        keys: action.payload.data.items,
        count: action.payload.data.count,
      })

    case `${KEY_GENERATE}::SUCCESS`:
      return merge(state, {
        loading: false,
        keys: [action.payload]
      })
    case `${KEY_ROLL}::SUCCESS`:
      let tmpState = set(state, `loading`, false)
      let oldKey = action.payload.old
      if (!action.payload.delayRoll) {
        tmpState = set(tmpState, 'keys', remove(tmpState.keys,
          (key) => key.id === oldKey.id)
        )
      } else {
        let keyIndex = tmpState.keys.findIndex(
          (item) => item.id === oldKey.id
        )
        tmpState = set(tmpState, `keys.${keyIndex}`, oldKey)
      }

      tmpState = set(tmpState, 'keys',
        unshift(tmpState.keys,action.payload.new))

      return tmpState
    default:
      return state
  }
}
