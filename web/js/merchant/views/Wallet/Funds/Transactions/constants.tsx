import React from 'react';
import * as items from 'common/ui/item';
import { idItem } from 'common/ui/item/id';

const ID = {
  title: 'Transaction ID',
  value: (item) => idItem(item.id),
};

const CREATED_AT = {
  title: 'Created At',
  value: items.createdAtShort,
};

const AMOUNT = {
  title: 'Amount',
  value: items.getAmount('amount'),
};

const TYPE = {
  title: 'Type',
  value: (item) => <div>{item.credit ? 'Credit' : 'Debit'}</div>,
};

const REFERENCE_ID = {
  title: 'Reference ID',
  value: (item) => <div>{item.reference_id}</div>,
};

export { ID, CREATED_AT, AMOUNT, TYPE, REFERENCE_ID };
