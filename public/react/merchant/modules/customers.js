import { set, merge, unshift, remove } from 'rzp/utils/immutable';
import Customer from 'merchant/models/Customer';

const CUSTOMERS_FETCH = 'CUSTOMERS_FETCH';
const CUSTOMERS_AUTOCOMPLETE_FETCH = 'CUSTOMERS_AUTOCOMPLETE_FETCH';
const CUSTOMER_CREATE = 'CUSTOMER_CREATE';
const CUSTOMER_EDIT = 'CUSTOMER_EDIT';
const CUSTOMER_DELETED = 'CUSTOMER_DELETED';

export const fetchCustomers = params => {
  return dispatch => {
    let customer = new Customer();
    return dispatch({
      type: CUSTOMERS_FETCH,
      payload: customer.fetchAll(params),
    });
  };
};

export const fetchCustomersForAutocomplete = () => {
  return dispatch => {
    let customer = new Customer();
    return dispatch({
      type: CUSTOMERS_AUTOCOMPLETE_FETCH,
      payload: customer.fetchForAutocomplete(),
    });
  };
};

export const saveCustomer = params => {
  return dispatch => {
    let customer = new Customer(params);
    return dispatch({
      type: customer.isNew ? CUSTOMER_CREATE : CUSTOMER_EDIT,
      payload: customer.save(),
    });
  };
};

export const deleteCustomer = params => {
  return dispatch => {
    let customer = new Customer(params);
    return customer.delete().then(() => {
      dispatch({
        type: CUSTOMER_DELETED,
        payload: customer,
      });
    });
  };
};

let initialState = {
  loading: true,
  customers: [],
  count: 0,
};

export default function(state = initialState, action) {
  switch (action.type) {
    case `${CUSTOMERS_FETCH}::PENDING`:
    case `${CUSTOMERS_AUTOCOMPLETE_FETCH}::PENDING`:
      return set(state, 'loading', true);

    case `${CUSTOMERS_FETCH}::SUCCESS`:
    case `${CUSTOMERS_AUTOCOMPLETE_FETCH}::SUCCESS`:
      return merge(state, {
        loading: false,
        customers: action.payload.data.items,
        count: action.payload.data.count,
      });

    case `${CUSTOMERS_FETCH}::ERROR`:
    case `${CUSTOMERS_AUTOCOMPLETE_FETCH}::ERROR`:
      return merge(state, {
        loading: false,
        error: action.error,
      });

    case `${CUSTOMER_CREATE}::SUCCESS`:
      return set(state, 'customers', unshift(state.customers, action.payload));

    case `${CUSTOMER_EDIT}::SUCCESS`:
      let customerIndex = state.customers.findIndex(
        item => item.id === action.payload.id
      );
      return set(state, `customers.${customerIndex}`, action.payload);

    case CUSTOMER_DELETED:
      var customersList = remove(
        state.customers,
        customer => customer.id === action.payload.id
      );
      return set(state, 'customers', customersList);

    default:
      return state;
  }
}
