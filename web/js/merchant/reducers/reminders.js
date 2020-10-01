import { set, merge } from 'common/utils/immutable';
import { merchantFetch } from 'merchant/utils/ajax';
import { findBy } from 'common/utils/rzp-utils';
import Reminders from 'merchant/models/Reminders';

export const REMINDERS_FETCH = 'REMINDERS_FETCH';
export const REMINDERS_UPDATE = 'REMINDERS_UPDATE';
export const REMINDER_CONFIG_FETCH = 'REMINDER_CONFIG_FETCH';
export const REMINDER_MERCHANT_CONFIG_FETCH = 'REMINDER__MERCHANT_CONFIG_FETCH';
export const REMINDER_MERCHANT_CONFIG_UPDATE = 'REMINDER__MERCHANT_CONFIG_UPDATE';

export const fetchReminders = () => {
  const reminders = new Reminders();

  return {
    type: REMINDERS_FETCH,
    payload: reminders.fetchAll(),
  };
};

export const disableEnableReminders = (id, data) => {
  return merchantFetch({
    url: `reminders/service/merchant_settings/${id}`,
    method: 'PATCH',
    data: {
      active: data.active,
    },
    headers: {
      'Content-Type': 'application/json',
    },
  });
};

export const createReminders = (namespace) => {
  return merchantFetch({
    url: 'reminders/service/merchant_settings',
    method: 'POST',
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
  product_configs: {
    payment_link: {
      isEnabled: false,
      configs_count: {
        with_expiry: null,
        without_expiry: null,
      },
    },
  },
};

export default function (state = initialState, action) {
  switch (action.type) {
    case `${REMINDERS_FETCH}::PENDING`:
      return set(state, 'reminders', set(state.reminders, 'loading', true));

    case `${REMINDERS_FETCH}::ERROR`:
      return set(state, 'reminders', set(state.reminders, 'loading', false));

    case `${REMINDERS_FETCH}::SUCCESS`: {
      const newState = set(
        state,
        'reminders',
        merge(state.reminders, {
          loading: false,
          items: action.payload.data.items,
          count: action.payload.data.count,
        }),
      );

      const paymentLinkReminders =
        findBy(action.payload.data.items, 'namespace', 'payment_link_v2') || {};

      return set(
        newState,
        'product_configs',
        merge(state.product_configs, {
          ...state.product_configs,
          payment_link: {
            ...state.product_configs.payment_link,
            isEnabled: paymentLinkReminders.active,
            reminders: action.payload.data.items.filter(
              (item) => item.namespace === 'payment_link_v2',
            ),
          },
        }),
      );
    }

    case `${REMINDER_MERCHANT_CONFIG_FETCH}::PENDING`:
      return set(state, 'merchant_config', set(state.merchant_config, 'loading', true));

    case `${REMINDER_MERCHANT_CONFIG_FETCH}::SUCCESS`: {
      const newState = set(
        state,
        'merchant_config',
        merge(state.merchant_config, {
          loading: false,
          ...action.payload.data,
        }),
      );

      const paymentLinkConfigsCount = {
        with_expiry: null,
        without_expiry: null,
      };

      action.payload.data.items.forEach((ele) => {
        if (ele.reminder_config.config_template.attr_key === 'expire_by') {
          if (ele.reminder_config.namespace === 'payment_link_v2') {
            paymentLinkConfigsCount.with_expiry += 1;
          }

          return;
        }

        if (ele.reminder_config.namespace === 'payment_link_v2') {
          paymentLinkConfigsCount.without_expiry += 1;
        }
      });

      return set(
        newState,
        'product_configs',
        merge(state.product_configs, {
          ...state.product_configs,
          payment_link: {
            ...state.product_configs.payment_link,
            configs_count: paymentLinkConfigsCount,
          },
        }),
      );
    }

    case `${REMINDER_MERCHANT_CONFIG_FETCH}::ERROR`:
      return set(state, 'merchant_config', set(state.merchant_config, 'loading', false));

    case `${REMINDER_MERCHANT_CONFIG_UPDATE}::SUCCESS`: {
      return set(
        state,
        'merchant_config',
        merge(state.merchant_config, {
          loading: false,
          ...action.payload.data,
        }),
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
        }),
      );
    }

    case `${REMINDER_CONFIG_FETCH}::ERROR`:
      return set(state, 'configs', set(state.configs, 'loading', false));

    default:
      return state;
  }
}
