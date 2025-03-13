import React, { useState } from 'react';

import { CustomPaymentBlockMethod } from 'merchant/views/Settings/Configuration/CheckoutEditor/PaymentConfiguration/CustomPaymentBlocks/CustomPaymentBlockForm';
import { SortableListItemData } from 'merchant/views/Settings/Configuration/CheckoutEditor/components/SortableList';

import { nonNullable } from 'merchant/views/Settings/Configuration/CheckoutEditor/helpers/index';
import { SortableCustomPaymentBlockMethodsList } from './SortableCustomPaymentBlockMethodsList';
import CardConfigurationModal from 'merchant/views/Settings/Configuration/CheckoutEditor/PaymentConfiguration/CardConfiguration/CardConfigurationModal';
import UpiConfigurationModal from 'merchant/views/Settings/Configuration/CheckoutEditor/PaymentConfiguration/UpiConfiguration/UpiConfigurationModal';
import EmiConfigurationModal from 'merchant/views/Settings/Configuration/CheckoutEditor/PaymentConfiguration/EmiConfiguration/EmiConfigurationModal';
import NetBankingConfigurationModal from 'merchant/views/Settings/Configuration/CheckoutEditor/PaymentConfiguration/NetBankingConfiguration/NetBankingConfigurationModal';
import PayLaterConfigurationModal from 'merchant/views/Settings/Configuration/CheckoutEditor/PaymentConfiguration/PayLaterConfiguration/PayLaterConfigurationModal';
import WalletConfigurationModal from 'merchant/views/Settings/Configuration/CheckoutEditor/PaymentConfiguration/WalletConfiguration/WalletConfigurationModal';

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
  const [methodModalToBeOpened, setMethodModalToBeOpened] = useState('');
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

  function closeModal() {
    setMethodModalToBeOpened('');
  }

  function handleOpenModal(slug: string) {
    switch (slug) {
      case 'upi':
        setMethodModalToBeOpened('upi');
        break;
      case 'netbanking':
        setMethodModalToBeOpened('netbanking');
        break;
      case 'wallet':
        setMethodModalToBeOpened('wallet');
        break;
      case 'emi':
        setMethodModalToBeOpened('emi');
        break;
      case 'paylater':
        setMethodModalToBeOpened('paylater');
        break;
      case 'card':
        setMethodModalToBeOpened('card');
        break;
      default:
        setMethodModalToBeOpened('');
        closeModal();
    }
  }

  return (
    <>
      <SortableCustomPaymentBlockMethodsList
        key={sortableBlocksListIDKey}
        blockKey={blockKey}
        list={sortableCustomPaymentBlockMethodsList}
        onUpdateSortList={onUpdateSortList}
        handleOpenModal={handleOpenModal}
      />
      <CardConfigurationModal
        isOpen={methodModalToBeOpened === 'card'}
        onClose={closeModal}
        blockName={blockKey}
      />
      <UpiConfigurationModal
        isOpen={methodModalToBeOpened === 'upi'}
        onClose={closeModal}
        blockName={blockKey}
      />
      <EmiConfigurationModal
        isOpen={methodModalToBeOpened === 'emi'}
        onClose={closeModal}
        blockName={blockKey}
      />
      <NetBankingConfigurationModal
        isOpen={methodModalToBeOpened === 'netbanking'}
        onClose={closeModal}
        blockName={blockKey}
      />
      <PayLaterConfigurationModal
        isOpen={methodModalToBeOpened === 'paylater'}
        onClose={closeModal}
        blockName={blockKey}
      />
      <WalletConfigurationModal
        isOpen={methodModalToBeOpened === 'wallet'}
        onClose={closeModal}
        blockName={blockKey}
      />
    </>
  );
}
