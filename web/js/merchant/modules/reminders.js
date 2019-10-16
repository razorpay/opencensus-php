import { set, merge } from 'rzp/utils/immutable';
import { merchantFetch } from 'merchant/utils/ajax';

import Reminders from 'merchant/models/Reminders';

export const REMINDERS_FETCH = 'REMINDERS_FETCH';
export const REMINDERS_UPDATE = 'REMINDERS_UPDATE';
export const REMINDER_CONFIG_FETCH = 'REMINDER_CONFIG_FETCH';
export const REMINDER_MERCHANT_CONFIG_FETCH = 'REMINDER__MERCHANT_CONFIG_FETCH';
export const REMINDER_MERCHANT_CONFIG_UPDATE =
  'REMINDER__MERCHANT_CONFIG_UPDATE';

export const fetchReminders = () => {
  const reminders = new Reminders();

  return {
    type: REMINDERS_FETCH,
    payload: reminders.fetchAll(),
  };
};

export const disableEnableReminders = (id, data) => {
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
  return merchantFetch({
    url: 'reminders/service/merchant_settings',
    method: 'post',
    data: {
      namespace,
    },
  });
};

export const fetchRemindersConfigs = () => {
  return {
    type: REMINDER_CONFIG_FETCH,
    payload: merchantFetch(`reminders/service/configs/`),
  };
};

export const fetchRemindersMerchantConfigs = () => {
  return {
    type: REMINDER_MERCHANT_CONFIG_FETCH,
    payload: merchantFetch(`reminders/service/merchant_config/`),
  };
};

export const editRemindersMerchantConfigs = (id, data) => {
  return {
    type: REMINDER_MERCHANT_CONFIG_UPDATE,
    payload: merchantFetch({
      url: `reminders/service/merchant_config/`,
      method: 'put',
      data,
    }),
  };
};

let initialState = {
  reminders: {
    loading: true,
    items: [],
    count: 0,
  },
  merchant_config: {
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

    case `${REMINDERS_FETCH}::ERROR`:
      return set(state, 'reminders', set(state.reminders, 'loading', false));

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
          ...action.payload.data,
        })
      );
    }

    case `${REMINDERS_UPDATE}::ERROR`:
      return set(state, 'reminders', set(state.reminders, 'loading', false));

    case `${REMINDER_MERCHANT_CONFIG_FETCH}::PENDING`:
      return set(
        state,
        'merchant_config',
        set(state.merchant_config, 'loading', true)
      );

    case `${REMINDER_MERCHANT_CONFIG_FETCH}::SUCCESS`: {
      return set(
        state,
        'merchant_config',
        merge(state.merchant_config, {
          loading: false,
          ...action.payload.data,
        })
      );
    }

    case `${REMINDER_MERCHANT_CONFIG_FETCH}::ERROR`:
      return set(
        state,
        'merchant_config',
        set(state.merchant_config, 'loading', false)
      );

    case `${REMINDER_MERCHANT_CONFIG_UPDATE}::SUCCESS`: {
      return set(
        state,
        'merchant_config',
        merge(state.merchant_config, {
          loading: false,
          ...action.payload.data,
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
          ...action.payload.data,
        })
      );
    }

    case `${REMINDER_CONFIG_FETCH}::ERROR`:
      return set(state, 'configs', set(state.configs, 'loading', false));

    default:
      return state;
  }
}
