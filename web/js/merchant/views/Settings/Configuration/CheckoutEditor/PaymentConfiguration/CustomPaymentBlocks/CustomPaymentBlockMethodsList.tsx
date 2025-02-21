import React, { useState } from 'react';

import { CustomPaymentBlockMethod } from 'merchant/views/Settings/Configuration/CheckoutEditor/PaymentConfiguration/CustomPaymentBlocks/CustomPaymentBlockForm';
import { SortableListItemData } from 'merchant/views/Settings/Configuration/CheckoutEditor/components/SortableList';

import { nonNullable } from 'merchant/views/Settings/Configuration/CheckoutEditor/helpers/index';
import { SortableCustomPaymentBlockMethodsList } from './SortableCustomPaymentBlockMethodsList';

export type CustomPaymentBlockMethodsListProps = {
  list: CustomPaymentBlockMethod[];
  blockKey: string;
};
export function CustomPaymentBlockMethodsList({
  list,
  blockKey,
}: CustomPaymentBlockMethodsListProps) {
  const customPaymentBlockMethodOrder = list.map((block) => block.slug);
  const customPaymentBlockMethodsMap = new Map(list.map((value) => [value.slug, value]));
  const [customPaymentBlockMethodOrderState, setCustomPaymentBlockMethodOrderState] = useState(
    customPaymentBlockMethodOrder,
  );

  const sortableCustomPaymentBlockMethodsList: SortableListItemData<CustomPaymentBlockMethod>[] =
    customPaymentBlockMethodOrderState
      ?.map((blockMethodName) => {
        const customBlockMethod = customPaymentBlockMethodsMap.get(blockMethodName);
        if (customBlockMethod) {
          return {
            item: customBlockMethod,
            id: `${customBlockMethod.slug}-${customBlockMethod.isVisible}-${customBlockMethod.isSingleInstrument}`,
          };
        }
        return null;
      })
      .filter(nonNullable);
  const sortableBlocksListIDKey = sortableCustomPaymentBlockMethodsList
    .map((block) => block.id)
    .join('-');

  function onUpdateSortList(updatedSequence: string[]) {
    setCustomPaymentBlockMethodOrderState(updatedSequence);
  }

  return (
    <SortableCustomPaymentBlockMethodsList
      key={sortableBlocksListIDKey}
      blockKey={blockKey}
      list={sortableCustomPaymentBlockMethodsList}
      onUpdateSortList={onUpdateSortList}
    />
  );
}
