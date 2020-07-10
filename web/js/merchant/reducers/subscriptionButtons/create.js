import {
  set,
  merge,
  removeItem,
  unshift,
  updateItem,
  push,
  deepMerge,
} from 'common/utils/immutable';

import { getCurrency } from 'common/ui/Amount';
import { paiseToRupees } from 'common/utils/rzp-utils';
import { fetchPaymentPageEntity as getSubscriptionButtonDetails } from 'merchant/views/PaymentPages/PaymentPages/model';
import { FIXED_FIELDS } from 'merchant/views/PaymentPages/PaymentPages/Wysiwyg/FormSection/UDF/helpers/preAddedFields';
import { buttonThemes } from 'merchant/views/PaymentButton/PaymentButton/Create/constants/buttonThemes';
import { templateTypes } from 'merchant/views/PaymentButton/PaymentButton/Create/components/Templates/meta';

const FETCH_PAYMENT_BUTTON_ENTITY = 'FETCH_PAYMENT_BUTTON_ENTITY';
const RESET_PAYMENT_BUTTON_DATA = 'RESET_PAYMENT_BUTTON_DATA';

const UPDATE_PLAN_FIELD = 'UPDATE_PLAN_FIELD';
const DELETE_PLAN_FIELD = 'DELETE_PLAN_FIELD';

const UPDATE_UDF_FIELD = 'UPDATE_UDF_FIELD';
const DELETE_UDF_FIELD = 'DELETE_UDF_FIELD';

const UPDATE_PAYMENT_BUTTON_DATA = 'UPDATE_PAYMENT_BUTTON_DATA';
const UPDATE_PAYMENT_BUTTON_RECEIPT_DETAILS =
  'UPDATE_PAYMENT_BUTTON_RECEIPT_DETAILS';

const UPDATE_STEP_REVIEW_PROGRESS = 'UPDATE_STEP_REVIEW_PROGRESS';

const UPDATE_BUTTON_SETTINGS_HIGHLIGHTER = 'UPDATE_BUTTON_SETTINGS_HIGHLIGHTER';

export const fetchSubscriptionButtonDetails = (id, isIntentDuplicate) => {
  return {
    type: FETCH_PAYMENT_BUTTON_ENTITY,
    payload: getSubscriptionButtonDetails(id),
    isIntentDuplicate: isIntentDuplicate, // This indicates whether the payment items needs to clear off the ids in the fetched entity
    id,
  };
};

export const resetPageData = () => ({
  type: RESET_PAYMENT_BUTTON_DATA,
});

export const updateStepReviewProgress = data => {
  return {
    type: UPDATE_STEP_REVIEW_PROGRESS,
    payload: data,
  };
};

export const updatePlanField = (planField, index) => ({
  type: UPDATE_PLAN_FIELD,
  payload: {
    field: planField,
    index,
  },
});

export const deletePlanField = index => ({
  type: DELETE_PLAN_FIELD,
  index,
});

export const updateUDFField = (field, index) => ({
  type: UPDATE_UDF_FIELD,
  payload: {
    field,
    index,
  },
});

export const deleteUDFField = index => ({
  type: DELETE_UDF_FIELD,
  index,
});

export const updatePaymentButtonData = data => ({
  type: UPDATE_PAYMENT_BUTTON_DATA,
  payload: data,
});

export const updateReceiptDetails = data => ({
  type: UPDATE_PAYMENT_BUTTON_RECEIPT_DETAILS,
  payload: data,
});

export const updateHighlightButtonSettings = id => {
  return {
    type: UPDATE_BUTTON_SETTINGS_HIGHLIGHTER,
    payload: {
      id,
    },
  };
};

let initialState = {
  subscriptionButtonId: null,
  subscriptionButtonEntity: {
    currency: 'INR', // Initialising with INR currency
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
  planFields: [],
  udfFields: [FIXED_FIELDS.email, FIXED_FIELDS.phone], // Email and Phone are added by default to display in UI and will NOW be sent in
  current_highlighted_button_settings: null,
  stepsProgress: {
    isButtonDetailsReviewed: false,
    isPlansDetailsReviewed: false,
    isCustomerDetailsReviewed: false,
  },
};

export default function(state = initialState, action) {
  switch (action.type) {
    case `${FETCH_PAYMENT_BUTTON_ENTITY}::PENDING`: {
      return {
        ...initialState,
        subscriptionButtonId: action.id,
        subscriptionButtonEntity: {},
      };
    }

    case `${FETCH_PAYMENT_BUTTON_ENTITY}::SUCCESS`: {
      const entityData = { ...action.payload.data };

      // 1. Normalize expire_by for FE consumption
      if (entityData.expire_by) {
        entityData.expire_by *= 1000;
      }

      // 2.
      entityData.settings.allow_social_share =
        entityData.settings.allow_social_share === '1';

      // 3.
      entityData.payment_page_items.forEach(pi => {
        // While creation/editing, all amounts are converted to Paisa (or smaller unit)

        if (pi.item.amount) {
          pi.item.amount = paiseToRupees(pi.item.amount); // Convert in Rupees (or bigger unit)
        }
      });

      // 4. If intention while fetching is to duplicate, then delete existing entity specific data
      if (action.isIntentDuplicate) {
        // 3-1. Remove id for each of payment page item
        entityData.payment_page_items.forEach(fi => {
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
      const udfFields = udfSchema.sort(function(a, b) {
        const positionA = a.settings.position;
        const positionB = b.settings.position;

        return Number(positionA) - Number(positionB);
      });

      // 7.
      const planItems = entityData.payment_page_items;
      const planFields = planItems.sort(function(a, b) {
        const positionA = a.settings.position;
        const positionB = b.settings.position;

        return Number(positionA) - Number(positionB);
      });

      // 8. Currently, receipt settings are mixed with settings, and in scattered form, hence consolidating
      const receiptSettings = {
        enable_receipt: entityData.settings.enable_receipt || '1',
        selected_udf_field: entityData.settings.selected_udf_field || '',
        enable_custom_serial_number:
          entityData.settings.enable_custom_serial_number || '0',
        enable_80g_details: entityData.settings.enable_80g_details || '0',
      };

      entityData.receipt = receiptSettings;

      const storeState = {
        subscriptionButtonEntity: entityData,
        udfFields: udfFields, // Sorted fields udf schema
        planFields: planFields, // Sorted fields from plan items

        stepsProgress: {
          isButtonDetailsReviewed: true,
          isPlansDetailsReviewed: true,
          isCustomerDetailsReviewed: true,
        },
      };

      // 9. If intention while fetching is not to duplicate, then only add subscriptionButtonId
      if (!action.isIntentDuplicate) {
        storeState.subscriptionButtonId = entityData.id;
      }

      return storeState;
    }

    case `${FETCH_PAYMENT_BUTTON_ENTITY}::ERROR`: {
      return set(state, 'subscriptionButtonEntity', null);
    }

    case UPDATE_PAYMENT_BUTTON_DATA: {
      return {
        ...state,
        subscriptionButtonEntity: deepMerge(
          // Needed for settings, currency, page receipts
          state.subscriptionButtonEntity,
          action.payload
        ),
      };
    }

    case DELETE_PLAN_FIELD: {
      return {
        ...state,
        planFields: removeItem(state.planFields, action.index), // Position of items is not updated until page is created(/saved)
      };
    }

    case DELETE_UDF_FIELD: {
      return {
        ...state,
        udfFields: removeItem(state.udfFields, action.index), // Position of items is not updated until page is created(/saved)
      };
    }

    case UPDATE_PLAN_FIELD: {
      // Insert in starting of the form items
      let planFields;

      if (
        action.payload.hasOwnProperty('index') &&
        typeof action.payload.index !== 'undefined'
      ) {
        // Modifying existing plan field
        planFields = updateItem(
          state.planFields,
          action.payload.index,
          action.payload.field
        );
      } else {
        // Adding new plan field
        planFields = push(state.planFields, action.payload.field); // Position of items is updated before creating(/saving) the page, otherwise deleting a form item will creating inconsistency
      }

      return {
        ...state,
        planFields,
      };
    }

    case UPDATE_UDF_FIELD: {
      // Insert in starting of the form items
      let udfFields;

      if (typeof action.payload.index !== 'undefined') {
        // Modifying existing plan field
        udfFields = updateItem(
          state.udfFields,
          action.payload.index,
          action.payload.field
        );
      } else {
        // Adding new plan field
        udfFields = push(state.udfFields, action.payload.field); // Position of items is updated before creating(/saving) the page, otherwise deleting a form item will creating inconsistency
      }

      return {
        ...state,
        udfFields,
      };
    }

    case UPDATE_PAYMENT_BUTTON_RECEIPT_DETAILS:
      return {
        ...state,
        subscriptionButtonEntity: merge(state.subscriptionButtonEntity, {
          receipt: action.payload,
        }),
      };

    case UPDATE_STEP_REVIEW_PROGRESS: {
      return set(state, 'stepsProgress', {
        ...state.stepsProgress,
        ...action.payload,
      });
    }

    case RESET_PAYMENT_BUTTON_DATA:
      return {
        ...initialState,
      };

    case UPDATE_BUTTON_SETTINGS_HIGHLIGHTER: {
      return {
        ...state,
        current_highlighted_button_settings: action.payload.id,
      };
    }

    default:
      return state;
  }
}
