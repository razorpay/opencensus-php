import { set, merge, removeItem, updateItem, push, deepMerge } from 'common/utils/immutable';

import { getCurrency } from 'common/ui/Amount';
import { i18CurrencyConversionFromMinorUnitToCommonUnit } from 'common/utils/rzp-utils';
import { fetchPaymentPageEntity as getPaymentButtonDetails } from 'merchant/views/PaymentPages/PaymentPages/model';
import { FIXED_FIELDS } from 'merchant/views/PaymentPages/PaymentPages/Wysiwyg/FormSection/UDF/helpers/preAddedFields';
import { getButtonThemes } from 'merchant/views/PaymentButton/PaymentButton/Create/constants/buttonThemes';
import { templateTypes } from 'merchant/views/PaymentButton/PaymentButton/Create/components/Templates/meta';
import FIELD_TYPES from 'merchant/views/PaymentPages/PaymentPages/Wysiwyg/FormSection/Amount/helpers/fieldTypes';
import { getBaseFieldForAmountFieldType } from 'merchant/views/PaymentPages/PaymentPages/Wysiwyg/FormSection/Amount/helpers';

const FETCH_PAYMENT_BUTTON_ENTITY = 'FETCH_PAYMENT_BUTTON_ENTITY';
const RESET_PAYMENT_BUTTON_DATA = 'RESET_PAYMENT_BUTTON_DATA';

const UPDATE_AMOUNT_FIELD = 'UPDATE_AMOUNT_FIELD';
const DELETE_AMOUNT_FIELD = 'DELETE_AMOUNT_FIELD';

const UPDATE_UDF_FIELD = 'UPDATE_UDF_FIELD';
const DELETE_UDF_FIELD = 'DELETE_UDF_FIELD';

const INIT_PAYMENT_BUTTON_TEMPALTE_DATA = 'INIT_PAYMENT_BUTTON_TEMPALTE_DATA';
const UPDATE_PAYMENT_BUTTON_DATA = 'UPDATE_PAYMENT_BUTTON_DATA';
const UPDATE_PAYMENT_BUTTON_RECEIPT_DETAILS = 'UPDATE_PAYMENT_BUTTON_RECEIPT_DETAILS';

const UPDATE_STEP_REVIEW_PROGRESS = 'UPDATE_STEP_REVIEW_PROGRESS';

const UPDATE_BUTTON_SETTINGS_HIGHLIGHTER = 'UPDATE_BUTTON_SETTINGS_HIGHLIGHTER';

const DEFAULT_CURRENCY = 'INR';

const buttonThemes = getButtonThemes();
/*
 * Save all the default configs related to template.
 * 'data' can be used to pass presets like pre-defined amountFields / udfFields
 * TODO: Use this to pre-add the payment_button_text
 * */
export const updateTemplateType = (data, templateKey) => {
  let paymentButtonText = 'Pay Now';
  const amountFields = [];
  const udfFields = [FIXED_FIELDS.email, FIXED_FIELDS.phone]; // Email and Phone are added by default to display in UI and will NOW be sent in

  if (templateKey === templateTypes.donation.key) {
    // 1.
    paymentButtonText = 'Donate Now';

    // 2.
    const donationAmountField = getBaseFieldForAmountFieldType(FIELD_TYPES.dynamic_price.key);
    donationAmountField.item.name = 'Donate an Amount of your Choice';
    donationAmountField.mandatory = true;
    donationAmountField.min_amount = i18CurrencyConversionFromMinorUnitToCommonUnit(
      getCurrency(DEFAULT_CURRENCY).min_value,
      DEFAULT_CURRENCY,
    );

    amountFields.push(donationAmountField);

    // 3.
    udfFields.push(
      FIXED_FIELDS.name,
      FIXED_FIELDS.address,
      FIXED_FIELDS.city,
      FIXED_FIELDS.pincode,
      FIXED_FIELDS.state,
    );
  }

  return {
    type: INIT_PAYMENT_BUTTON_TEMPALTE_DATA,
    payload: {
      amountFields,
      udfFields,
      paymentButtonEntity: {
        settings: {
          payment_button_template_type: templateKey,
          payment_button_text: paymentButtonText,
        },
      },
    },
  };
};

export const fetchPaymentButtonDetails = (id, isIntentDuplicate) => {
  return {
    type: FETCH_PAYMENT_BUTTON_ENTITY,
    payload: getPaymentButtonDetails(id),
    isIntentDuplicate, // This indicates whether the payment items needs to clear off the ids in the fetched entity
    id,
  };
};

export const resetPageData = () => ({
  type: RESET_PAYMENT_BUTTON_DATA,
});

export const updateStepReviewProgress = (data) => {
  return {
    type: UPDATE_STEP_REVIEW_PROGRESS,
    payload: data,
  };
};

export const updateAmountField = (amountField, index) => ({
  type: UPDATE_AMOUNT_FIELD,
  payload: {
    field: amountField,
    index,
  },
});

export const deleteAmountField = (index) => ({
  type: DELETE_AMOUNT_FIELD,
  index,
});

export const updateUDFField = (field, index) => ({
  type: UPDATE_UDF_FIELD,
  payload: {
    field,
    index,
  },
});

export const deleteUDFField = (index) => ({
  type: DELETE_UDF_FIELD,
  index,
});

export const updatePaymentButtonData = (data) => ({
  type: UPDATE_PAYMENT_BUTTON_DATA,
  payload: data,
});

export const updateReceiptDetails = (data) => ({
  type: UPDATE_PAYMENT_BUTTON_RECEIPT_DETAILS,
  payload: data,
});

export const updateHighlightButtonSettings = (id) => {
  return {
    type: UPDATE_BUTTON_SETTINGS_HIGHLIGHTER,
    payload: {
      id,
    },
  };
};

const initialState = {
  paymentButtonId: null,
  paymentButtonEntity: {
    currency: DEFAULT_CURRENCY, // Initialising with INR currency
    settings: {
      template_type: null, // Note: until the template is loaded, the UI won't be shown bcoz it's critical part of flow unlike in case of Payment Page
      payment_button_label: null, // Not used for Payment Button product
      checkout_options: {
        email: FIXED_FIELDS.email.name, // email key in form to be used in prefill checkout
        phone: FIXED_FIELDS.phone.name, // phone key in form to be used in prefill checkout
      },
      payment_button_theme: buttonThemes.BTN_DARK_STANDARD.value,
      payment_button_text: null,
    },
    receipt: {
      enable_receipt: '1',
      selected_udf_field: '',
      enable_custom_serial_number: '0', // Default is automatic receipt
      enable_80g_details: '0',
    },
  },
  amountFields: [],
  udfFields: [],
  current_highlighted_button_settings: null,
  stepsProgress: {
    isButtonDetailsReviewed: false,
    isAmountDetailsReviewed: false,
    isCustomerDetailsReviewed: false,
  },
};

export default function paymentButtonCreateReducer(state = initialState, action) {
  switch (action.type) {
    case `${FETCH_PAYMENT_BUTTON_ENTITY}::PENDING`: {
      return {
        ...initialState,
        paymentButtonId: action.id,
        paymentButtonEntity: {},
      };
    }

    case `${FETCH_PAYMENT_BUTTON_ENTITY}::SUCCESS`: {
      const entityData = { ...action.payload.data };

      // 1. Normalize expire_by for FE consumption
      if (entityData.expire_by) {
        entityData.expire_by *= 1000;
      }

      // 2.
      entityData.settings.allow_social_share = entityData.settings.allow_social_share === '1';

      // 3.
      entityData.payment_page_items.forEach((pi, index) => {
        // While creation/editing, all amounts are converted to Paisa (or smaller unit)
        pi.uniqueKey = new Date().getTime() * index; // Used as React keys if required, eg: For preset amount fields in Donation template's

        if (pi.item.amount) {
          pi.item.amount = i18CurrencyConversionFromMinorUnitToCommonUnit(
            pi.item.amount,
            entityData.currency,
          ); // Convert in Rupees (or bigger unit)
        }

        if (pi.min_amount) {
          pi.min_amount = i18CurrencyConversionFromMinorUnitToCommonUnit(
            pi.min_amount,
            entityData.currency,
          ); // Convert in Rupees (or bigger unit)
        }

        if (pi.max_amount) {
          pi.max_amount = i18CurrencyConversionFromMinorUnitToCommonUnit(
            pi.max_amount,
            entityData.currency,
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
      }

      // No concept of slug for Payment Button product
      delete entityData.slug;

      // 6.
      const udfSchema = JSON.parse(entityData.settings.udf_schema);
      const udfFields = udfSchema.sort((a, b) => {
        const positionA = a.settings.position;
        const positionB = b.settings.position;

        return Number(positionA) - Number(positionB);
      });

      // 7.
      const amountItems = entityData.payment_page_items;
      const amountFields = amountItems.sort((a, b) => {
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

      const storeState = {
        paymentButtonEntity: entityData,
        udfFields, // Sorted fields udf schema
        amountFields, // Sorted fields from amount items

        stepsProgress: {
          isButtonDetailsReviewed: true,
          isAmountDetailsReviewed: true,
          isCustomerDetailsReviewed: true,
        },
      };

      // 4. If intention while fetching is not to duplicate, then only add paymentButtonId
      if (!action.isIntentDuplicate) {
        storeState.paymentButtonId = entityData.id;
      }

      return storeState;
    }

    case `${FETCH_PAYMENT_BUTTON_ENTITY}::ERROR`: {
      return set(state, 'paymentButtonEntity', null);
    }

    case UPDATE_PAYMENT_BUTTON_DATA: {
      return {
        ...state,
        paymentButtonEntity: deepMerge(
          // Needed for settings, currency, page receipts
          state.paymentButtonEntity,
          action.payload,
        ),
      };
    }

    case INIT_PAYMENT_BUTTON_TEMPALTE_DATA: {
      return deepMerge(state, action.payload);
    }

    case DELETE_AMOUNT_FIELD: {
      return {
        ...state,
        amountFields: removeItem(state.amountFields, action.index), // Position of items is not updated until page is created(/saved)
      };
    }

    case DELETE_UDF_FIELD: {
      return {
        ...state,
        udfFields: removeItem(state.udfFields, action.index), // Position of items is not updated until page is created(/saved)
      };
    }

    case UPDATE_AMOUNT_FIELD: {
      // Insert in starting of the form items
      let amountFields;

      if (action.payload.hasOwnProperty('index') && typeof action.payload.index !== 'undefined') {
        // Modifying existing amount field
        amountFields = updateItem(state.amountFields, action.payload.index, action.payload.field);
      } else {
        // Adding new amount field
        amountFields = push(state.amountFields, action.payload.field); // Position of items is updated before creating(/saving) the page, otherwise deleting a form item will creating inconsistency
      }

      return {
        ...state,
        amountFields,
      };
    }

    case UPDATE_UDF_FIELD: {
      // Insert in starting of the form items
      let udfFields;

      if (typeof action.payload.index !== 'undefined') {
        // Modifying existing amount field
        udfFields = updateItem(state.udfFields, action.payload.index, action.payload.field);
      } else {
        // Adding new amount field
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
        paymentButtonEntity: merge(state.paymentButtonEntity, {
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
