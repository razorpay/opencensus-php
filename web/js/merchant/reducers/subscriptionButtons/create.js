import { set, merge, removeItem, updateItem, push, deepMerge } from 'common/utils/immutable';

import { paiseToRupees } from 'common/utils/rzp-utils';
import { fetchPaymentPageEntity as getSubscriptionButtonDetails } from 'merchant/views/PaymentPages/PaymentPages/model';
import { FIXED_FIELDS } from 'merchant/views/PaymentPages/PaymentPages/Wysiwyg/FormSection/UDF/helpers/preAddedFields';
import { getButtonThemes } from 'merchant/views/PaymentButton/PaymentButton/Create/constants/buttonThemes';

const FETCH_SUBSCRIPTION_BUTTON_ENTITY = 'FETCH_SUBSCRIPTION_BUTTON_ENTITY';
const RESET_SUBSCRIPTION_BUTTON_DATA = 'RESET_SUBSCRIPTION_BUTTON_DATA';

const UPDATE_PAYMENT_FIELD_SUBSCRIPTION = 'UPDATE_PAYMENT_FIELD_SUBSCRIPTION';
const DELETE_PAYMENT_FIELD_SUBSCRIPTION = 'DELETE_PAYMENT_FIELD_SUBSCRIPTION';
const DELETE_ALL_ONE_TIME_PAYMENTS_FIELD_SUBSCRIPTION =
  'DELETE_ALL_ONE_TIME_PAYMENTS_FIELD_SUBSCRIPTION';

const UPDATE_UDF_FIELD_SUBSCRIPTION = 'UPDATE_UDF_FIELD_SUBSCRIPTION';
const DELETE_UDF_FIELD_SUBSCRIPTION = 'DELETE_UDF_FIELD_SUBSCRIPTION';

const UPDATE_SUBSCRIPTION_BUTTON_DATA = 'UPDATE_SUBSCRIPTION_BUTTON_DATA';
const UPDATE_SUBSCRIPTION_BUTTON_RECEIPT_DETAILS = 'UPDATE_SUBSCRIPTION_BUTTON_RECEIPT_DETAILS';

const UPDATE_STEP_REVIEW_PROGRESS_SUBSCRIPTION = 'UPDATE_STEP_REVIEW_PROGRESS_SUBSCRIPTION';

const UPDATE_BUTTON_SETTINGS_HIGHLIGHTER_SUBSCRIPTION =
  'UPDATE_BUTTON_SETTINGS_HIGHLIGHTER_SUBSCRIPTION';

const buttonThemes = getButtonThemes();

export const fetchSubscriptionButtonDetails = (id, isIntentDuplicate) => {
  return {
    type: FETCH_SUBSCRIPTION_BUTTON_ENTITY,
    payload: getSubscriptionButtonDetails(id),
    isIntentDuplicate, // This indicates whether the payment items needs to clear off the ids in the fetched entity
    id,
  };
};

export const resetPageData = () => ({
  type: RESET_SUBSCRIPTION_BUTTON_DATA,
});

export const updateStepReviewProgress = (data) => {
  return {
    type: UPDATE_STEP_REVIEW_PROGRESS_SUBSCRIPTION,
    payload: data,
  };
};

export const updatePaymentField = (paymentField, index) => ({
  type: UPDATE_PAYMENT_FIELD_SUBSCRIPTION,
  payload: {
    field: paymentField,
    index,
  },
});

export const deletePaymentField = (index) => ({
  type: DELETE_PAYMENT_FIELD_SUBSCRIPTION,
  index,
});

export const removeAllOneTimePaymentFields = () => ({
  type: DELETE_ALL_ONE_TIME_PAYMENTS_FIELD_SUBSCRIPTION,
});

export const updateUDFField = (field, index) => ({
  type: UPDATE_UDF_FIELD_SUBSCRIPTION,
  payload: {
    field,
    index,
  },
});

export const deleteUDFField = (index) => ({
  type: DELETE_UDF_FIELD_SUBSCRIPTION,
  index,
});

export const updatePaymentButtonData = (data) => ({
  type: UPDATE_SUBSCRIPTION_BUTTON_DATA,
  payload: data,
});

export const updateReceiptDetails = (data) => ({
  type: UPDATE_SUBSCRIPTION_BUTTON_RECEIPT_DETAILS,
  payload: data,
});

export const updateHighlightButtonSettings = (id) => {
  return {
    type: UPDATE_BUTTON_SETTINGS_HIGHLIGHTER_SUBSCRIPTION,
    payload: {
      id,
    },
  };
};

export function filterSubscriptionPaymentItems(paymentFields, isOneTimePayments) {
  const items = [];

  paymentFields.forEach((field, index) => {
    const isItemAllowed = isOneTimePayments ? !field.plan_id : !!field.plan_id;

    if (isItemAllowed) {
      items[index] = field; // This is amazing javascript hack. Maintaining 2 different arrays in frontend would lead to issues in indexes a lot. One bug in handling index would ruin all the items, so this is the safest approach.

      // NOTE: Only catch is that items.length won't give right value, so have to find accordingly
    }
  });

  return items;
}

const initialState = {
  subscriptionButtonId: null,
  subscriptionButtonEntity: {
    currency: '', // Initialising with INR currency. TODO: Not initialising right now, would do only when there is dropdown to select currency
    settings: {
      payment_button_label: null, // Not used for Payment Button product
      checkout_options: {
        email: FIXED_FIELDS.email.name, // email key in form to be used in prefill checkout
        phone: FIXED_FIELDS.phone.name, // phone key in form to be used in prefill checkout
      },
      payment_button_theme: buttonThemes.BRAND_COLOR.value,
      payment_button_text: 'Subscribe Now',
    },
    receipt: {
      enable_receipt: '1',
      selected_udf_field: '',
      enable_custom_serial_number: '0', // Default is automatic receipt
      enable_80g_details: '0',
    },
  },
  paymentFields: [], // This has both one time payment items and plan fields. Can't maintain separate arrays bcoz they're saved in common array in backend and they've common index value for sort.
  udfFields: [FIXED_FIELDS.email, FIXED_FIELDS.phone], // Email and Phone are added by default to display in UI and will NOW be sent in
  current_highlighted_button_settings: null,
  stepsProgress: {
    isButtonDetailsReviewed: false,
    isPlansDetailsReviewed: false,
    isOneTimePaymentsDetailsReviewed: false,
    isCustomerDetailsReviewed: false,
  },
};

export default function subscriptionBtnReducer(state = initialState, action) {
  switch (action.type) {
    case `${FETCH_SUBSCRIPTION_BUTTON_ENTITY}::PENDING`: {
      return {
        ...initialState,
        subscriptionButtonId: action.id,
        subscriptionButtonEntity: {},
      };
    }

    case `${FETCH_SUBSCRIPTION_BUTTON_ENTITY}::SUCCESS`: {
      const entityData = { ...action.payload.data };

      // 1. Normalize expire_by for FE consumption
      if (entityData.expire_by) {
        entityData.expire_by *= 1000;
      }

      // 2.
      entityData.settings.allow_social_share = entityData.settings.allow_social_share === '1';

      // 3.
      entityData.payment_page_items.forEach((pi) => {
        // While creation/editing, all amounts are converted to Paisa (or smaller unit)

        // Convert only for one-time payment items. It's bcoz while creation, plans are fetched in common reducer, hence they cannot be converted to rupees,
        // and since their amount is used just for the purpose of display and not manipulation, so for plans, paiseToRupees is done only for display purpose.
        if (!pi.plan_id) {
          pi.item.amount = paiseToRupees(pi.item.amount); // Convert in Rupees (or bigger unit)
        }
      });

      // 4. If intention while fetching is to duplicate, then delete existing entity specific data
      if (action.isIntentDuplicate) {
        // 4-1. Remove id for each of payment page item
        entityData.payment_page_items.forEach((fi) => {
          // Removing payment_page_id is enough since removing/adding id for items is handled in handleSavePublish. However, this is just for sanity.

          delete fi.id;
          delete fi.payment_link_id;
          delete fi.item.id;
        });

        // 4-2.
        delete entityData.id;
      }

      // 5. No concept of slug for Payment Button product
      delete entityData.slug;

      // 6.
      const udfSchema = JSON.parse(entityData.settings.udf_schema);
      const udfFields = udfSchema.sort((a, b) => {
        const positionA = a.settings.position;
        const positionB = b.settings.position;

        return Number(positionA) - Number(positionB);
      });

      // 7.
      const paymentItems = entityData.payment_page_items;
      const paymentFields = paymentItems.sort((a, b) => {
        const positionA = a.settings.position;
        const positionB = b.settings.position;

        return Number(positionA) - Number(positionB);
      });

      // 8. Currently, receipt settings are mixed with settings, and in scattered form, hence consolidating
      const receiptSettings = {
        enable_receipt: entityData.settings.enable_receipt || '1',
        selected_udf_field: entityData.settings.selected_udf_field || '',
        enable_custom_serial_number: entityData.settings.enable_custom_serial_number || '0',
        enable_80g_details: entityData.settings.enable_80g_details || '0',
      };

      entityData.receipt = receiptSettings;

      const storeState = {
        subscriptionButtonEntity: entityData,
        udfFields, // Sorted fields udf schema
        paymentFields, // Sorted fields from payment items

        stepsProgress: {
          isButtonDetailsReviewed: true,
          isPlansDetailsReviewed: true,
          isOneTimePaymentsDetailsReviewed: true,
          isCustomerDetailsReviewed: true,
        },
      };

      // 9. If intention while fetching is not to duplicate, then only add subscriptionButtonId
      if (!action.isIntentDuplicate) {
        storeState.subscriptionButtonId = entityData.id;
      }

      return storeState;
    }

    case `${FETCH_SUBSCRIPTION_BUTTON_ENTITY}::ERROR`: {
      return set(state, 'subscriptionButtonEntity', null);
    }

    case UPDATE_SUBSCRIPTION_BUTTON_DATA: {
      return {
        ...state,
        subscriptionButtonEntity: deepMerge(
          // Needed for settings, currency, page receipts
          state.subscriptionButtonEntity,
          action.payload,
        ),
      };
    }

    case DELETE_PAYMENT_FIELD_SUBSCRIPTION: {
      return {
        ...state,
        paymentFields: removeItem(state.paymentFields, action.index), // Position of items is not updated until page is created(/saved)
      };
    }

    case DELETE_ALL_ONE_TIME_PAYMENTS_FIELD_SUBSCRIPTION: {
      const newFields = filterSubscriptionPaymentItems(state.paymentFields);

      return {
        ...state,
        paymentFields: newFields, // Position of items is not updated until page is created(/saved)
      };
    }

    case DELETE_UDF_FIELD_SUBSCRIPTION: {
      return {
        ...state,
        udfFields: removeItem(state.udfFields, action.index), // Position of items is not updated until page is created(/saved)
      };
    }

    case UPDATE_PAYMENT_FIELD_SUBSCRIPTION: {
      // Insert in starting of the form items
      let paymentFields;

      if (action.payload.hasOwnProperty('index') && typeof action.payload.index !== 'undefined') {
        // Modifying existing payment field
        paymentFields = updateItem(state.paymentFields, action.payload.index, action.payload.field);
      } else {
        // Adding new payment field
        paymentFields = push(state.paymentFields, action.payload.field); // Position of items is updated before creating(/saving) the page, otherwise deleting a form item will creating inconsistency
      }

      return {
        ...state,
        paymentFields,
      };
    }

    case UPDATE_UDF_FIELD_SUBSCRIPTION: {
      // Insert in starting of the form items
      let udfFields;

      if (typeof action.payload.index !== 'undefined') {
        // Modifying existing payment field
        udfFields = updateItem(state.udfFields, action.payload.index, action.payload.field);
      } else {
        // Adding new udf field
        udfFields = push(state.udfFields, action.payload.field); // Position of items is updated before creating(/saving) the page, otherwise deleting a form item will creating inconsistency
      }

      return {
        ...state,
        udfFields,
      };
    }

    case UPDATE_SUBSCRIPTION_BUTTON_RECEIPT_DETAILS:
      return {
        ...state,
        subscriptionButtonEntity: merge(state.subscriptionButtonEntity, {
          receipt: action.payload,
        }),
      };

    case UPDATE_STEP_REVIEW_PROGRESS_SUBSCRIPTION: {
      return set(state, 'stepsProgress', {
        ...state.stepsProgress,
        ...action.payload,
      });
    }

    case RESET_SUBSCRIPTION_BUTTON_DATA:
      return {
        ...initialState,
      };

    case UPDATE_BUTTON_SETTINGS_HIGHLIGHTER_SUBSCRIPTION: {
      return {
        ...state,
        current_highlighted_button_settings: action.payload.id,
      };
    }

    default:
      return state;
  }
}
