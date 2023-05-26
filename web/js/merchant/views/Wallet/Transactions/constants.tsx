import React from 'react';
import * as items from 'common/ui/item';
import { idItem } from 'common/ui/item/id';

const ID = {
  title: 'Transaction ID',
  value: (item) => idItem(item.id),
};

const REFERENCE_ID = {
  title: 'Reference ID',
  value: (item) => <div>{item.reference_id}</div>,
};

const ACCOUNT_ID = {
  title: 'Account ID',
  value: (item) => <div>{item.account_id}</div>,
};

const SOURCE = {
  title: 'Source',
  value: (item) => <div>{item.source}</div>,
};

const TYPE = {
  title: 'Type',
  value: (item) => <div>{item.credit ? 'Credit' : 'Debit'}</div>,
};

const CREATED_AT = {
  title: 'Created At',
  value: items.createdAtShort,
};

const AMOUNT = {
  title: 'Amount',
  value: items.getAmount('amount'),
};

export { ID, AMOUNT, ACCOUNT_ID, CREATED_AT, TYPE, SOURCE, REFERENCE_ID };
