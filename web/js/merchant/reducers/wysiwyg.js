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

import { paiseToRupees, arrayMove } from 'common/utils/rzp-utils';
import { fetchPaymentPageEntity } from 'merchant/views/PaymentPages/PaymentPages/model';

// TODO: Remove dependency from here
import { FIXED_FIELDS } from 'merchant/views/PaymentPages/PaymentPages/Wysiwyg/FormSection/UDF_Fields/preAddedFields';

const FETCH_ENTITY = 'FETCH_ENTITY';
const REFRESH_PAGE_DATA = 'REFRESH_PAGE_DATA';
const UPDATE_DATA = 'UPDATE_DATA';
const DELETE_IN_FORM_ITEMS = 'DELETE_IN_FORM_ITEMS';
const UPDATE_IN_FORM_ITEMS = 'UPDATE_IN_FORM_ITEMS';
const ADD_IN_FORM_ITEMS = 'ADD_IN_FORM_ITEMS';
const MARK_DATA_SAVED = 'MARK_DATA_SAVED';
const REORDER_FORM_ITEMS = 'REORDER_FORM_ITEMS';

export const isFormItemOfTypeAmount = formItem =>
  formItem.hasOwnProperty('item');

export const updateTemplateType = (data, templateKey) => {
  const isPageDirty = false;

  return updateData(
    {
      description: data ? JSON.stringify({ value: data, metaText: '' }) : null, // No meta text if nothing updated by user
      template_type: templateKey,
    },
    isPageDirty
  );
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
    isIntentDuplicate: isIntentDuplicate,
    id,
  };
};

export const updateData = (formItem, isPageDirty) => ({
  type: UPDATE_DATA,
  formItems: formItem,
  isPageDirty,
});

export const refreshPageData = () => ({
  type: REFRESH_PAGE_DATA,
});

export const deleteInFormItems = index => ({
  type: DELETE_IN_FORM_ITEMS,
  index,
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

export const addInFormItems = formItem => ({
  type: ADD_IN_FORM_ITEMS,
  formItem,
});

export const markDataSaved = _ => ({
  type: MARK_DATA_SAVED,
});

let initialState = {
  paymentPageEntity: {
    currency: 'INR', // Initialising with INR currency
    settings: {
      payment_button_label: 'Pay',
      checkout_options: {
        email: FIXED_FIELDS.email.name, // email key in form to be used in prefill checkout
        phone: FIXED_FIELDS.phone.name, // phone key in form to be used in prefill checkout
      },
    },
  },
  payment_page_id: null,
  FORM_ITEMS: [FIXED_FIELDS.email, FIXED_FIELDS.phone], // Email and Phone are added by default to display in UI and will NOW be sent in udf_schema to API.
  isPageDirty: false,
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

export default function(state = initialState, action) {
  switch (action.type) {
    case `${FETCH_ENTITY}::PENDING`:
      return set(state, 'paymentPageEntity', { id: action.id });

    case `${FETCH_ENTITY}::SUCCESS`: {
      const entityData = { ...action.payload.data };

      /*
      *
      *  Normalize expire_by for FE consumption
      *
      * */
      if (entityData.expire_by) {
        entityData.expire_by *= 1000;
      }

      entityData.settings.allow_social_share =
        entityData.settings.allow_social_share === '1';

      entityData.settings.allow_multiple_units =
        entityData.settings.allow_multiple_units === '1';

      let formItems;

      const udfSchema = JSON.parse(entityData.settings.udf_schema);

      entityData.payment_page_items.forEach(pi => {
        // While creation/editing, all amounts are converted to Paisa (or smaller unit)

        if (pi.item.amount) {
          pi.item.amount = paiseToRupees(pi.item.amount); // Convert in Rupees (or bigger unit)
        }

        if (pi.min_amount) {
          pi.min_amount = paiseToRupees(pi.min_amount); // Convert in Rupees (or bigger unit)
        }

        if (pi.max_amount) {
          pi.max_amount = paiseToRupees(pi.max_amount); // Convert in Rupees (or bigger unit)
        }
      });

      // 1-1. If intention while fetching is to duplicate, then remove id for each of payment page item
      if (action.isIntentDuplicate) {
        entityData.payment_page_items.forEach(fi => {
          // Removing payment_page_id is enough since removing/adding id for items is handled in handleSavePublish. However, this is just for sanity.

          delete fi.id;
          delete fi.payment_link_id;
          delete fi.item.id;
        });
      }

      formItems = [].concat(udfSchema).concat(entityData.payment_page_items);

      formItems.sort(function(a, b) {
        const positionA = a.settings.position;
        const positionB = b.settings.position;

        return Number(positionA) - Number(positionB);
      });

      // 2. If intention while fetching is to duplicate, then delete entity id
      if (action.isIntentDuplicate) {
        delete entityData.id;
      }

      // 3. If intention while fetching is to duplicate, then remove slug as well
      if (action.isIntentDuplicate) {
        delete entityData.slug;
      }

      const storeState = {
        paymentPageEntity: entityData,
        FORM_ITEMS: formItems, // Sorted items having udf_schema and amount items mixed
      };

      // 4. If intention while fetching is not to duplicate, then only add payment_page_id
      if (!action.isIntentDuplicate) {
        storeState.payment_page_id = entityData.id;
      }

      return storeState;
    }

    case `${FETCH_ENTITY}::ERROR`:
      return set(state, 'paymentPageEntity', null);

    case UPDATE_DATA:
      if (action.formItems.hasOwnProperty('id')) {
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
        return {
          ...state,
          isPageDirty:
            action.isPageDirty !== void 0 ? action.isPageDirty : true,
          paymentPageEntity: deepMerge(
            // Needed for settings
            state.paymentPageEntity,
            action.formItems
          ),
        };
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
        formItems = updateItem(
          state.FORM_ITEMS,
          action.payload.index,
          action.payload.formItem
        );
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

    case MARK_DATA_SAVED:
      return {
        ...state,
        isPageDirty: false,
      };

    case REORDER_FORM_ITEMS:
      return {
        ...state,
        FORM_ITEMS: arrayMove(
          state.FORM_ITEMS,
          action.payload.oldIndexInFormItems,
          action.payload.newIndexInFormItems
        ),
      };

    case REFRESH_PAGE_DATA:
      return {
        ...initialState,
      };

    default:
      return state;
  }
}
