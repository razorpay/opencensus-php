import { generateDynamicComponent, customerDetail } from '../PaymentsTable/columns';

import { COLUMNS } from './constants';

export const createCustomColumnView = (cols, selectedColumnsList: string[]) => {
  const fixedColumnComponents = cols.filter(
    (item) => item.title.props?.children !== COLUMNS.CUSTOMER_DETAIL,
  );
  const selectedColumnComponents = selectedColumnsList.reduce(
    (components: object[], columnName: string) => {
      const componentToAdd =
        columnName === COLUMNS.CUSTOMER_DETAIL
          ? customerDetail
          : generateDynamicComponent(columnName);

      return columnName === COLUMNS.CUSTOMER_DETAIL
        ? [componentToAdd, ...components]
        : [...components, componentToAdd];
    },
    [],
  );
  return [...fixedColumnComponents, ...selectedColumnComponents];
};

export const createPayloadForSavePreferences = (selectedColumnsListData: string[]) => {
  return selectedColumnsListData.reduce(
    (
      acc: { user_notes_key_columns: string[]; payment_optional_keys_columns: string[] },
      columnName,
    ) => {
      if (columnName === COLUMNS.CUSTOMER_DETAIL)
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
