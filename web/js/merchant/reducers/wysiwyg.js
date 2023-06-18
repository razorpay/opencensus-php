import store from 'merchant/store';

import {
  set,
  merge,
  removeItem,
  unshift,
  updateItem,
  push,
  deepMerge,
} from 'common/utils/immutable';

import { arrayMove, i18CurrencyConversionFromMinorUnitToCommonUnit } from 'common/utils/rzp-utils';
import {
  fetchPaymentPageEntity,
  fetchCustomDomain,
  fetchCustomDomainCurrentPlan,
} from 'merchant/views/PaymentPages/PaymentPages/model';
import { DEFAULT_RULE } from 'merchant/views/MagicCheckout/constants';
import { transfeeRuleToNormalFormat } from 'merchant/views/PaymentPages/PaymentPages/helpers';

// TODO: Remove dependency from here
import { FIXED_FIELDS } from 'merchant/views/PaymentPages/PaymentPages/Wysiwyg/FormSection/UDF/helpers/preAddedFields';

const INIT_DEFAULT_FORM_ITEMS = 'INIT_DEFAULT_FORM_ITEMS';
const FETCH_ENTITY = 'FETCH_ENTITY';
const REFRESH_PAGE_DATA = 'REFRESH_PAGE_DATA';
const UPDATE_DATA = 'UPDATE_DATA';
const UPDATE_SETTINGS = 'UPDATE_SETTINGS';
const SETTINGS_MODAL = 'SETTINGS_MODAL';
const SHIPROCKET_MODAL = 'SHIPROCKET_MODAL';
const DELETE_IN_FORM_ITEMS = 'DELETE_IN_FORM_ITEMS';
const UPDATE_IN_FORM_ITEMS = 'UPDATE_IN_FORM_ITEMS';
const REPLACE_IN_FORM_ITEMS = 'REPLACE_IN_FORM_ITEMS';
const ADD_IN_FORM_ITEMS = 'ADD_IN_FORM_ITEMS';
const MARK_DATA_SAVED = 'MARK_DATA_SAVED';
const REORDER_FORM_ITEMS = 'REORDER_FORM_ITEMS';
const UPDATE_RECEIPT_DETAILS = 'UPDATE_RECEIPT_DETAILS';
const PREFILL_CONTACT_DETAILS = 'PREFILL_CONTACT_DETAILS';
const FETCH_CUSTOM_DOMAIN = 'FETCH_CUSTOM_DOMAIN';
const UPDATE_CUSTOM_DOMAIN = 'UPDATE_CUSTOM_DOMAIN';
const UPDATE_MAGIC_CHECKOUT_DATA = 'UPDATE_MAGIC_CHECKOUT_DATA';
const FETCH_CUSTOM_DOMAIN_PLAN = 'FETCH_CUSTOM_DOMAIN_PLAN';
const PP_BATCH_PAYMENT_PAGE_ACTIVE = 'PP_BATCH_PAYMENT_PAGE_ACTIVE';

export const updateTemplateType = (data, templateKey) => {
  const isPageDirty = false;

  // eslint-disable-next-line no-use-before-define
  return updateData(
    {
      description: data ? JSON.stringify({ value: data, metaText: '' }) : null, // No meta text if nothing updated by user
      template_type: templateKey,
    },
    isPageDirty,
  );
};

export const initDefaultFormItems = (isBatchPaymentPages = false) => {
  return {
    type: INIT_DEFAULT_FORM_ITEMS,
    payload: {
      user: store.getState().session.user,
      org: store.getState().session?.org,
      isBatchPaymentPages,
    },
  };
};

export const fetchPaymentPage = (id, isIntentDuplicate) => {
  if (!id) {
    return {
      type: UPDATE_DATA,
      formItems: {
        id: null, // To handle case where intial UI schema to be shown
      },
    };
  }

  return {
    type: FETCH_ENTITY,
    payload: fetchPaymentPageEntity(id),
    isIntentDuplicate,
    id,
  };
};

export const fetchCustomDomainDetails = () => {
  return {
    type: FETCH_CUSTOM_DOMAIN,
    payload: fetchCustomDomain(),
  };
};

export const updateCustomDomainDetails = (payload = {}) => {
  return {
    type: UPDATE_CUSTOM_DOMAIN,
    payload,
  };
};

export const fetchCustomDomainPlanDetails = () => {
  return {
    type: FETCH_CUSTOM_DOMAIN_PLAN,
    payload: fetchCustomDomainCurrentPlan(),
  };
};

export const setSettingsModal = (status) => ({
  type: SETTINGS_MODAL,
  isSettingsOpened: status,
});

export const setShiprocketModal = (status) => (dispatch) => {
  dispatch({ type: SHIPROCKET_MODAL, isShiprocketOpened: status });
  return Promise.resolve();
};

export const updateMagicData = (data) => ({
  type: UPDATE_MAGIC_CHECKOUT_DATA,
  data,
});

export const updateData = (formItem, isPageDirty, currency) => ({
  type: UPDATE_DATA,
  formItems: formItem,
  isPageDirty,
  currency,
});

export const refreshPageData = () => ({
  type: REFRESH_PAGE_DATA,
});

export const deleteInFormItems = (index) => ({
  type: DELETE_IN_FORM_ITEMS,
  index,
});

export const addInFormItems = (formItem) => ({
  type: ADD_IN_FORM_ITEMS,
  formItem,
});

export const updateInFormItems = ({ formItem, index }) => {
  if (typeof index === 'undefined') {
    return addInFormItems(formItem);
  }

  return {
    type: UPDATE_IN_FORM_ITEMS,
    payload: { index, formItem },
  };
};

export const replaceInFormItems = (formItems) => ({
  type: REPLACE_IN_FORM_ITEMS,
  formItems,
});

export const markDataSaved = (_) => ({
  type: MARK_DATA_SAVED,
});

export const updateReceiptDetails = (data) => ({
  type: UPDATE_RECEIPT_DETAILS,
  payload: data,
});

export const prefillContactDetails = (data) => ({
  type: PREFILL_CONTACT_DETAILS,
  payload: data,
});

const initialState = {
  paymentPageEntity: {
    currency: 'INR', // Initialising with INR currency
    settings: {
      payment_button_label: 'Pay',
      checkout_options: {
        email: FIXED_FIELDS.email.name, // email key in form to be used in prefill checkout
        phone: FIXED_FIELDS.phone.name, // phone key in form to be used in prefill checkout
      },
      custom_domain: '', // if custom domain used at page level ('' -> pages.razorpay.com being used)
    },
    receipt: {
      enable_receipt: '1',
      selected_udf_field: '',
      enable_custom_serial_number: '0', // Default is automatic receipt
      enable_80g_details: '0',
    },
  },
  payment_page_id: null,
  FORM_ITEMS: null, // Email and Phone are added by default to display in UI and will NOW be sent in udf_schema to API (Added only as per feature flag)
  isPageDirty: false,
  isSettingsOpened: false,
  isShiprocketOpened: false, // Modal used to enable Shiprocket
  magicCheckout: {
    enabled: false,
    formModalOpen: false,
    feeRule: { ...DEFAULT_RULE },
    /*
     * After enabling magic checkout, we are removing address related fields.
     * prevAddedFields used to store the removed address fields.
     * It will be used to add those fields again if magic checkout is disabled.
     */
    prevAddedFields: [],
  },
  customDomain: {
    value: '',
    planDetails: {},
  }, // custom domain details at a merchant level
  isBatchPaymentPages: false, // identify Batch Payment Page flow.
};

export const reorderFormItems = ({
  oldIndex: oldIndexInFormItems,
  newIndex: newIndexInFormItems,
}) => {
  return {
    type: REORDER_FORM_ITEMS,
    payload: { oldIndexInFormItems, newIndexInFormItems },
  };
};

export const updateSettings = (updatedSettings = {}) => ({
  type: UPDATE_SETTINGS,
  payload: updatedSettings,
});

export const setIsBatchPaymentPages = (isActive = false) => ({
  type: PP_BATCH_PAYMENT_PAGE_ACTIVE,
  payload: isActive,
});

export default (state = initialState, action) => {
  switch (action.type) {
    case INIT_DEFAULT_FORM_ITEMS: {
      const { isBatchPaymentPages, user, org } = action.payload;
      const currentUser = user;

      const defaultFields = [];
      if (isBatchPaymentPages) {
        defaultFields.push(FIXED_FIELDS.primaryRefId);
        state = {
          ...state,
          isBatchPaymentPages,
        };
      }
      // if org feature flag 'enable_payer_name_for_pp' is enabled then add Payer Name as default filed.
      const orgDetails = org;
      const showPayerNamePP = orgDetails?.features?.indexOf('enable_payer_name_for_pp') > -1;
      if (showPayerNamePP) {
        const payerNameFiled = FIXED_FIELDS?.name;
        payerNameFiled.title = 'Payer Name';
        payerNameFiled.name = 'payer__name';
        defaultFields.push(payerNameFiled);
      }

      if (!currentUser.isPaymentPageEmailOptional || isBatchPaymentPages) {
        defaultFields.push(FIXED_FIELDS.email);
      }

      if (!currentUser.isPaymentPageContactOptional || isBatchPaymentPages) {
        defaultFields.push(FIXED_FIELDS.phone);
      }

      return set(state, 'FORM_ITEMS', defaultFields);
    }
    case `${FETCH_ENTITY}::PENDING`:
      return set(state, 'paymentPageEntity', { id: action.id });

    case `${FETCH_ENTITY}::SUCCESS`: {
      const entityData = { ...action.payload.data };

      // 1. Normalize expire_by for FE consumption
      if (entityData.expire_by) {
        entityData.expire_by *= 1000;
      }

      // 2.
      entityData.settings.allow_social_share = entityData.settings.allow_social_share === '1';

      // 3. TODO: remove this, was used in PP v1
      entityData.settings.allow_multiple_units = entityData.settings.allow_multiple_units === '1';

      // 4.
      entityData.payment_page_items.forEach((pi) => {
        // While creation/editing, all amounts are converted to Paisa (or smaller unit)

        if (pi.item.amount) {
          pi.item.amount = i18CurrencyConversionFromMinorUnitToCommonUnit(
            pi.item.amount,
            pi.item.currency,
          ); // Convert in Rupees (or bigger unit)
        }

        if (pi.min_amount) {
          pi.min_amount = i18CurrencyConversionFromMinorUnitToCommonUnit(
            pi.min_amount,
            pi.item?.currency,
            //keeping optional chaining here since i am not sure if item will be there or not
          ); // Convert in Rupees (or bigger unit)
        }

        if (pi.max_amount) {
          pi.max_amount = i18CurrencyConversionFromMinorUnitToCommonUnit(
            pi.max_amount,
            pi.item?.currency,
          ); // Convert in Rupees (or bigger unit)
        }
      });

      // 5. If intention while fetching is to duplicate, then delete existing entity specific data
      if (action.isIntentDuplicate) {
        // 5-1. Remove id for each of payment page item
        entityData.payment_page_items.forEach((fi) => {
          // Removing payment_page_id is enough since removing/adding id for items is handled in handleSavePublish. However, this is just for sanity.

          delete fi.id;
          delete fi.payment_link_id;
          delete fi.item.id;
        });

        // 5-2.
        delete entityData.id;

        // 5-3.
        delete entityData.slug;
      }

      // 6.
      const udfSchema = JSON.parse(entityData.settings.udf_schema);

      const formItems = [].concat(udfSchema).concat(entityData.payment_page_items);

      formItems.sort((a, b) => {
        const positionA = a.settings.position;
        const positionB = b.settings.position;

        return Number(positionA) - Number(positionB);
      });

      // 7. Currently, receipt settings are mixed with settings, and in scattered form, hence consolidating
      const receiptSettings = {
        enable_receipt: entityData.settings.enable_receipt || '1',
        selected_udf_field: entityData.settings.selected_udf_field || '',
        enable_custom_serial_number: entityData.settings.enable_custom_serial_number || '0',
        enable_80g_details: entityData.settings.enable_80g_details || '0',
      };

      entityData.receipt = receiptSettings;

      // 8. if checkout_options missing somehow, reinitiate
      if (!entityData.settings.checkout_options) {
        entityData.settings.checkout_options = {
          email: FIXED_FIELDS.email.name,
          phone: FIXED_FIELDS.phone.name,
        };
      }

      const storeState = {
        ...initialState,
        magicCheckout: {
          ...initialState.magicCheckout,
          enabled: entityData.settings.one_click_checkout === '1',
          feeRule: entityData.settings.shipping_fee_rule
            ? transfeeRuleToNormalFormat(JSON.parse(entityData.settings.shipping_fee_rule))
            : { ...DEFAULT_RULE },
        },
        paymentPageEntity: entityData,
        FORM_ITEMS: formItems, // Sorted items having udf_schema and amount items mixed
        isBatchPaymentPages: state.isBatchPaymentPages, // set value in edit flow
      };

      // 4. If intention while fetching is not to duplicate, then only add payment_page_id
      if (!action.isIntentDuplicate) {
        storeState.payment_page_id = entityData.id;
      }

      return storeState;
    }

    case `${FETCH_ENTITY}::ERROR`:
      return set(state, 'paymentPageEntity', null);

    case `${FETCH_CUSTOM_DOMAIN}::SUCCESS`: {
      let domainName = '';

      if (action.payload.data?.count) {
        domainName = action.payload.data.items?.[0].domain_name;
      }

      return {
        ...state,
        customDomain: merge(state.customDomain, {
          value: domainName,
        }),
      };
    }

    case `${FETCH_CUSTOM_DOMAIN}::ERROR`:
      return {
        ...state,
        customDomain: merge(state.customDomain, {
          value: '',
        }),
      };

    case `${FETCH_CUSTOM_DOMAIN_PLAN}::SUCCESS`: {
      return {
        ...state,
        customDomain: merge(state.customDomain, {
          planDetails: action.payload.data.plan,
        }),
      };
    }

    case `${FETCH_CUSTOM_DOMAIN_PLAN}::ERROR`:
      return {
        ...state,
        customDomain: merge(state.customDomain, {
          planDetails: {},
        }),
      };

    case UPDATE_CUSTOM_DOMAIN:
      return {
        ...state,
        customDomain: merge(state.customDomain, action.payload),
      };

    case UPDATE_DATA:
      if (action.formItems?.hasOwnProperty('id')) {
        // re-Initialise FE if ID is changed to other ID/null
        return {
          ...initialState,
          payment_page_id: action.id,
          paymentPageEntity: deepMerge(initialState.paymentPageEntity, {
            id: action.id,
          }),
          isPageDirty: false,
        };
      } else {
        const newState = {
          ...state,
          isPageDirty: action.isPageDirty !== undefined ? action.isPageDirty : true,
          paymentPageEntity: {
            ...state.paymentPageEntity,
            // UPDATE_DATA action getting called in multiple scenarios, currency is not always available.
            currency: action.currency || state.paymentPageEntity.currency,
          },
        };

        if (action.formItems) {
          newState.paymentPageEntity = deepMerge(
            // Needed for settings
            newState.paymentPageEntity,
            action.formItems,
          );
        }

        return newState;
      }

    case UPDATE_SETTINGS: {
      const newSettings = { ...state.paymentPageEntity.settings, ...action.payload };

      return set(state, 'paymentPageEntity.settings', newSettings);
    }

    case DELETE_IN_FORM_ITEMS:
      return {
        ...state,
        isPageDirty: true,
        FORM_ITEMS: removeItem(state.FORM_ITEMS, action.index), // Position of items is not updated until page is created(/saved)
      };

    case UPDATE_IN_FORM_ITEMS: {
      // Insert in starting of the form items
      let formItems;

      if (action.payload.index === -1) {
        formItems = unshift(state.FORM_ITEMS, action.payload.formItem);
      } else {
        formItems = updateItem(state.FORM_ITEMS, action.payload.index, action.payload.formItem);
      }

      return {
        ...state,
        isPageDirty: true,
        FORM_ITEMS: formItems,
      };
    }

    case ADD_IN_FORM_ITEMS:
      return {
        ...state,
        isPageDirty: true,
        FORM_ITEMS: push(state.FORM_ITEMS, action.formItem), // Position of items is updated before creating(/saving) the page, otherwise deleting a form item will creating inconsistency
      };

    case REPLACE_IN_FORM_ITEMS:
      return {
        ...state,
        isPageDirty: true,
        FORM_ITEMS: action.formItems, // Position of items is updated before creating(/saving) the page, otherwise deleting a form item will creating inconsistency
      };

    case MARK_DATA_SAVED:
      return {
        ...state,
        isPageDirty: false,
      };

    case UPDATE_RECEIPT_DETAILS:
      return {
        ...state,
        paymentPageEntity: merge(state.paymentPageEntity, {
          receipt: action.payload,
        }),
      };

    case REORDER_FORM_ITEMS:
      return {
        ...state,
        FORM_ITEMS: arrayMove(
          state.FORM_ITEMS,
          action.payload.oldIndexInFormItems,
          action.payload.newIndexInFormItems,
        ),
      };

    case REFRESH_PAGE_DATA:
      return {
        ...initialState,
      };

    case PREFILL_CONTACT_DETAILS:
      return {
        ...state,
        paymentPageEntity: merge(state.paymentPageEntity, {
          ...action.payload,
        }),
      };

    case SETTINGS_MODAL:
      return {
        ...state,
        isSettingsOpened: action.isSettingsOpened,
      };

    case SHIPROCKET_MODAL:
      return {
        ...state,
        isShiprocketOpened: action.isShiprocketOpened,
      };
    case UPDATE_MAGIC_CHECKOUT_DATA:
      return {
        ...state,
        magicCheckout: merge(state.magicCheckout, {
          ...action.data,
        }),
      };
    case PP_BATCH_PAYMENT_PAGE_ACTIVE:
      return {
        ...state,
        isBatchPaymentPages: action.payload,
      };
    default:
      return state;
  }
};
