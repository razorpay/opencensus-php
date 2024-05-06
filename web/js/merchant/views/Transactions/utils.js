import { generateDynamicComponent, email, contact } from 'common/ui/item/pair';

import { COLUMNS } from './constants';
import {
  customerDetail,
  generateDynamicComponentV2,
} from './v2/Payments/components/PaymentsTable/columns';

export const createCustomColumnView = (cols, selectedColumnsList, isTransactionsV2View) => {
  const fixedColumnComponents = cols.filter(
    (item) =>
      item.title !== COLUMNS.EMAIL &&
      item.title !== COLUMNS.CONTACT &&
      item.title.props?.children !== COLUMNS.CUSTOMER_DETAIL,
  );
  const selectedColumnComponents = selectedColumnsList.reduce((components, columnName) => {
    const componentToAdd = (() => {
      switch (columnName) {
        case COLUMNS.EMAIL:
          return email;
        case COLUMNS.CONTACT:
          return contact;
        case COLUMNS.CUSTOMER_DETAIL:
          return customerDetail;
        default:
          return isTransactionsV2View
            ? generateDynamicComponentV2(columnName)
            : generateDynamicComponent(columnName);
      }
    })();

    return columnName === COLUMNS.EMAIL ||
      columnName === COLUMNS.CONTACT ||
      columnName === COLUMNS.CUSTOMER_DETAIL
      ? [componentToAdd, ...components]
      : [...components, componentToAdd];
  }, []);
  return [...fixedColumnComponents, ...selectedColumnComponents];
};

export const createPayloadForSavePreferences = (selectedColumnsListData) => {
  return selectedColumnsListData.reduce(
    (acc, columnName) => {
      if (columnName === COLUMNS.EMAIL || columnName === COLUMNS.CONTACT)
        acc.payment_optional_keys_columns.push(columnName);
      else acc.user_notes_key_columns.push(columnName);
      return acc;
    },
    {
      user_notes_key_columns: [],
      payment_optional_keys_columns: [],
    },
  );
};
