import {
  makeActionCollectionReducer,
  listFetchPendingState,
  listFetchSuccessState,
  listFetchErrorState,
} from 'merchant/modules/collection';
import Customer from 'merchant/models/Customer';

export const CUSTOMERS_FETCH = 'CUSTOMERS_FETCH';
export const CUSTOMER_FETCH = 'CUSTOMER_FETCH';
export const CUSTOMERS_AUTOCOMPLETE_FETCH = 'CUSTOMERS_AUTOCOMPLETE_FETCH';
export const CUSTOMER_CREATE = 'CUSTOMER_CREATE';
export const CUSTOMER_EDIT = 'CUSTOMER_EDIT';
export const CUSTOMER_DELETED = 'CUSTOMER_DELETED';
export const CUSTOMER_ADDRESS_FETCH = 'CUSTOMER_ADDRESS_FETCH';
export const CUSTOMER_ADDRESS_ADD = 'CUSTOMER_ADDRESS_ADD';

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

/**
 * Fetches customer's addresses.
 * @param {Customer} params
 */
export const fetchCustomerAddresses = params => {
  let customer = new Customer(params);

  return {
    type: CUSTOMER_ADDRESS_FETCH,
    payload: customer.fetchAddresses(),
  };
};

/**
 * Adds a customer's address.
 * @param {Customer} customerParams
 * @param {Object} address
 */
export const addCustomerAddress = (customerParams, address) => {
  let customer = new Customer(customerParams);

  return {
    type: CUSTOMER_ADDRESS_ADD,
    payload: customer.addAddress(address),
  };
};

export default makeActionCollectionReducer('CUSTOMERS', {
  [`${CUSTOMERS_AUTOCOMPLETE_FETCH}::PENDING`]: listFetchPendingState,
  [`${CUSTOMERS_AUTOCOMPLETE_FETCH}::SUCCESS`]: listFetchSuccessState,
  [`${CUSTOMERS_AUTOCOMPLETE_FETCH}::ERROR`]: listFetchErrorState,
});
