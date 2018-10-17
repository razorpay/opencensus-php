import { set, merge, removeItem, updateItem, push } from 'rzp/utils/immutable';
import {
  createEmailField,
  createPhoneField,
} from 'merchant/containers/PaymentPages/Pages/V2/views/Form/Fields/helpers';

export const updateData = field => ({
  type: 'UPDATE_DATA',
  fields: field,
});

export const deleteInSchema = index => ({
  type: 'DELETE_IN_SCHEMA',
  index,
});

export const updateInSchema = ({ index, field }) => ({
  type: 'UPDATE_IN_SCHEMA',
  payload: { index, field },
});

export const updateAmount = amountObj => ({
  type: 'UPDATE_DATA',
  fields: amountObj,
});

export const addInSchema = fields => ({
  type: 'ADD_IN_SCHEMA',
  fields,
});

let initialState = {
  paymentPageEntity: {},
  FORM_SCHEMA: [createEmailField(), createPhoneField()],
};

export default function(state = initialState, action) {
  switch (action.type) {
    case 'UPDATE_DATA':
      return set(state, 'paymentPageEntity', {
        ...state.paymentPageEntity,
        ...action.fields,
      });

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

/* This is just dummy data. To be  from API call + taken from props */
const dummy_paymentPageEntity = {
  title: 'Invoice and Bill Payments',
  description:
    "Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. I. Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, A when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. I. Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type a A  And scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. I. Lorem Ipsum is simply dummy text of the printing and  A  A typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book.  AIt has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. I",
  social_share: 1,
  support: {
    email: 'support@savethewhales.org',
    phone: '1800-1234-1323 (Timings: 9AM to 6PM)',
  },
  terms: 'If payment fails, we give free even ticket within 4 days. Enjoy!',
};
