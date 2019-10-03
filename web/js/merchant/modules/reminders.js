import { set, merge } from 'rzp/utils/immutable';
import { merchantFetch } from 'merchant/utils/ajax';

import Reminders from 'merchant/models/Reminders';

export const REMINDERS_FETCH = 'REMINDERS_FETCH';
export const REMINDERS_UPDATE = 'REMINDERS_UPDATE';
export const REMINDER_CONFIG_FETCH = 'REMINDER_CONFIG_FETCH';

export const fetchReminders = () => {
  const reminders = new Reminders();

  return {
    type: REMINDERS_FETCH,
    payload: reminders.fetchAll(),
  };
};

export const editReminders = (id, data) => {
  return {
    type: REMINDERS_UPDATE,
    payload: merchantFetch({
      url: `reminders/service/merchant_settings/${id}`,
      method: 'post',
      data,
    }),
  };
};

export const disableReminders = (id, data) => {
  return {
    type: REMINDERS_UPDATE,
    payload: merchantFetch({
      url: `reminders/service/merchant_settings/${id}`,
      method: 'patch',
      data,
    }),
  };
};

export const createReminders = namespace => {
  return {
    type: REMINDERS_FETCH,
    payload: merchantFetch({
      url: 'reminders/service/merchant_settings',
      method: 'post',
      data: {
        namespace,
      },
    }),
  };
};

export const fetchRemindersConfigs = () => {
  return {
    type: REMINDER_CONFIG_FETCH,
    payload: merchantFetch(`reminders/service/configs/`),
  };
};

let initialState = {
  reminders: {
    loading: true,
    items: [],
    count: 0,
  },
  configs: {
    loading: true,
    items: [],
    count: 0,
  },
};

export default function(state = initialState, action) {
  switch (action.type) {
    case `${REMINDERS_FETCH}::PENDING`:
      return set(state, 'reminders', set(state.reminders, 'loading', true));

    case `${REMINDERS_FETCH}::SUCCESS`: {
      return set(
        state,
        'reminders',
        merge(state.reminders, {
          loading: false,
          items: action.payload.data.items,
          count: action.payload.data.count,
        })
      );
    }

    case `${REMINDERS_UPDATE}::SUCCESS`: {
      return set(
        state,
        'reminders',
        merge(state.reminders, {
          loading: false,
          items: state.reminders.items.map(item => {
            if (action.payload.data.id) {
              return action.payload.data;
            }

            item;
          }),
        })
      );
    }

    case `${REMINDER_CONFIG_FETCH}::PENDING`:
      return set(state, 'reminders', set(state.configs, 'loading', true));

    case `${REMINDER_CONFIG_FETCH}::SUCCESS`: {
      return set(
        state,
        'configs',
        merge(state.configs, {
          loading: false,
          items: action.payload.data.items,
          count: action.payload.data.count,
        })
      );
    }

    default:
      return state;
  }
}
