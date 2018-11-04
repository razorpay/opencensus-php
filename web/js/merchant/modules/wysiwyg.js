import {
  set,
  merge,
  removeItem,
  updateItem,
  push,
  deepMerge,
} from 'rzp/utils/immutable';
import { fetchPaymentPageEntity } from 'merchant/containers/PaymentPages/Pages/model';
import { FIELD_CONST } from 'merchant/containers/PaymentPages/Pages/V2/views/Form/Fields/helpers';

const FETCH_ENTITY = 'FETCH_ENTITY';

export const fetchPaymentPage = id => {
  if (!id) {
    return {
      type: 'UPDATE_DATA',
      fields: {
        id: null, // To handle case where intial UI schema to be shown
      },
    };
  } else {
    return {
      type: FETCH_ENTITY,
      payload: fetchPaymentPageEntity(id),
      id,
    };
  }
};

export const updateData = field => ({
  type: 'UPDATE_DATA',
  fields: field,
});

export const deleteInSchema = index => ({
  type: 'DELETE_IN_SCHEMA',
  index,
});

export const updateInSchema = ({ field, index }) => {
  if (!index) {
    return addInSchema(field);
  }

  return {
    type: 'UPDATE_IN_SCHEMA',
    payload: { index, field },
  };
};

export const addInSchema = field => ({
  type: 'ADD_IN_SCHEMA',
  field,
});

let initialState = {
  paymentPageEntity: {},
  payment_page_id: null,
  FORM_SCHEMA: [FIELD_CONST.email, FIELD_CONST.phone], // Email and Phone not to be sent in udf_schema in all cases.
};

export default function(state = initialState, action) {
  switch (action.type) {
    case `${FETCH_ENTITY}::PENDING`:
      return set(state, 'paymentPageEntity', { id: action.id });

    case `${FETCH_ENTITY}::SUCCESS`:
      const entityData = { ...action.payload.data };

      /*
      *
      *  Normalize expire_by for FE consumption
      *
      * */
      if (entityData.expire_by) {
        entityData.expire_by *= 1000;
      }

      if (entityData.amount) {
        entityData.amount /= 100;
      }

      entityData.settings.allow_social_share =
        entityData.settings.allow_social_share === '1';

      entityData.settings.allow_multiple_units =
        entityData.settings.allow_multiple_units === '1';

      entityData.stock = entityData.times_payable;
      delete entityData.times_payable;

      return {
        paymentPageEntity: entityData,
        payment_page_id: entityData.id,
        FORM_SCHEMA: JSON.parse(entityData.settings.udf_schema), // Must have phone and email already with it. FE hardcodes only for new payment page.
      };

    case `${FETCH_ENTITY}::ERROR`:
      return set(state, 'paymentPageEntity', null);

    case 'UPDATE_DATA':
      if (action.fields.hasOwnProperty('id')) {
        // re-Initialise FE if ID is changed to other ID/null
        return {
          ...initialState,
          payment_page_id: action.id,
          paymentPageEntity: { id: action.id },
        };
      } else {
        return set(
          state,
          'paymentPageEntity',
          deepMerge(
            // Needed for settings
            state.paymentPageEntity,
            action.fields
          )
        );
      }

    case 'DELETE_IN_SCHEMA':
      return set(
        state,
        'FORM_SCHEMA',
        removeItem(state.FORM_SCHEMA, action.index)
      );

    case 'UPDATE_IN_SCHEMA':
      return set(
        state,
        'FORM_SCHEMA',
        updateItem(
          state.FORM_SCHEMA,
          action.payload.index,
          action.payload.field
        )
      );

    case 'ADD_IN_SCHEMA':
      return set(state, 'FORM_SCHEMA', push(state.FORM_SCHEMA, action.field));

    default:
      return state;
  }
}
