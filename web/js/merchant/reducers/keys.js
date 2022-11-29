import { set, merge, unshift, remove } from 'common/utils/immutable';
import Key from 'merchant/models/Key';

const KEYS_FETCH = 'KEYS_FETCH';
const KEY_GENERATE = 'KEY_GENERATE';
const KEY_ROLL = 'KEY_ROLL';

export const fetchKeys = (params = {}, hasKeyAccess) => {
  const key = new Key();

  let request;

  if (params.mode === 'live' && !hasKeyAccess) {
    request = Promise.resolve({
      success: true,
      data: { count: 0, items: [], entity: 'collection' },
    });
  } else {
    request = key.fetchAll(params);
  }

  return {
    type: KEYS_FETCH,
    payload: request,
  };
};

export const generateKey = (params) => {
  const key = new Key(params);

  return {
    type: key.isNew ? KEY_GENERATE : KEY_ROLL,
    payload: key.save(),
  };
};

const initialState = {
  loading: true,
  isLoaded: false,
  keys: [],
  count: 0,
};

export default (state = initialState, action) => {
  switch (action.type) {
    case `${KEYS_FETCH}::PENDING`:
    case `${KEY_GENERATE}::PENDING`:
    case `${KEY_ROLL}::PENDING`:
      return merge(state, {
        loading: true,
      });

    case `${KEYS_FETCH}::SUCCESS`:
      return merge(state, {
        loading: false,
        isLoaded: true,
        keys: action.payload.data.items,
        count: action.payload.data.count,
      });

    case `${KEY_GENERATE}::SUCCESS`:
      return merge(state, {
        loading: false,
        keys: [action.payload],
      });
    case `${KEY_ROLL}::SUCCESS`:
      // eslint-disable-next-line no-case-declarations
      let tmpState = set(state, `loading`, false);
      // eslint-disable-next-line no-case-declarations
      const oldKey = action.payload.old;
      if (!action.payload.delayRoll) {
        tmpState = set(
          tmpState,
          'keys',
          remove(tmpState.keys, (key) => key.id === oldKey.id),
        );
      } else {
        const keyIndex = tmpState.keys.findIndex((item) => item.id === oldKey.id);
        tmpState = set(tmpState, `keys.${keyIndex}`, oldKey);
      }

      tmpState = set(tmpState, 'keys', unshift(tmpState.keys, action.payload.new));

      return tmpState;
    default:
      return state;
  }
};
