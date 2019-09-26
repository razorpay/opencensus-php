import { set, merge } from 'rzp/utils/immutable';
import Reminders from 'merchant/models/Reminders';

export const REMINDERS_FETCH = 'REMINDERS_FETCH';

export const fetchReminders = () => {
  const reminders = new Reminders();

  return {
    type: REMINDERS_FETCH,
    payload: reminders.fetchAll(),
  };
};

let initialState = {
  loading: false,
  items: [],
  count: 0,
};

export default function(state = initialState, action) {
  switch (action.type) {
    case `${REMINDERS_FETCH}::PENDING`:
      return set(state, 'loading', true);

    case `${REMINDERS_FETCH}::SUCCESS`: {
      return merge(state, {
        loading: false,
        items: action.payload.data.items,
        count: action.payload.data.count,
      });
    }

    default:
      return state;
  }
}
