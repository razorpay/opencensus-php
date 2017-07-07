import {
  makeActionCollectionReducer,
  listFetchPendingState,
  listFetchSuccessState,
  listFetchErrorState,
} from 'rzp/modules/collection';
import Customer from 'merchant/models/Customer';

export const CUSTOMERS_FETCH = 'CUSTOMERS_FETCH';
export const CUSTOMER_FETCH = 'CUSTOMER_FETCH';
export const CUSTOMERS_AUTOCOMPLETE_FETCH = 'CUSTOMERS_AUTOCOMPLETE_FETCH';
export const CUSTOMER_CREATE = 'CUSTOMER_CREATE';
export const CUSTOMER_EDIT = 'CUSTOMER_EDIT';
export const CUSTOMER_DELETED = 'CUSTOMER_DELETED';

export const fetchCustomers = params => {
  let customer = new Customer();

  return {
    type: CUSTOMERS_FETCH,
    payload: customer.fetchAll(params),
  };
};

export const fetchCustomer = id => {
  let customer = new Customer();

  return {
    type: CUSTOMER_FETCH,
    payload: customer.fetch(id),
  };
};

export const fetchCustomersForAutocomplete = () => {
  let customer = new Customer();

  return {
    type: CUSTOMERS_AUTOCOMPLETE_FETCH,
    payload: customer.fetchForAutocomplete(),
  };
};

export const saveCustomer = params => {
  let customer = new Customer(params);

  return {
    type: customer.isNew ? CUSTOMER_CREATE : CUSTOMER_EDIT,
    payload: customer.save(),
  };
};

export const deleteCustomer = params => {
  let customer = new Customer(params);

  return {
    type: CUSTOMER_DELETED,
    payload: customer.delete(),
    id: customer.id,
  };
};

export default makeActionCollectionReducer('CUSTOMERS', {
  [`${CUSTOMERS_AUTOCOMPLETE_FETCH}::PENDING`]: listFetchPendingState,
  [`${CUSTOMERS_AUTOCOMPLETE_FETCH}::SUCCESS`]: listFetchSuccessState,
  [`${CUSTOMERS_AUTOCOMPLETE_FETCH}::ERROR`]: listFetchErrorState,
});
