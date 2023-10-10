import React from 'react';
import Amount from 'common/ui/Amount';
import * as items from 'common/ui/item';
import { idItem } from 'common/ui/item/id';
import { Box } from '@razorpay/blade/components';

const ID = {
  title: 'Transaction Id',
  value: (item) => idItem(item.transaction_id),
};

const REFERENCE_ID = {
  title: 'Reference Id',
  value: (item) => <div>{item.reference_id}</div>,
};

const ACCOUNT_ID = {
  title: 'Account Id',
  value: (item) => <div>{item.account_id}</div>,
};

const SOURCE = {
  title: 'Source',
  value: (item) => <div>{item.transaction_reference_id}</div>,
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
  value: (item): JSX.Element => {
    return <Amount currency={item.currency} value={item.credit ? item.credit : item.debit} />;
  },
};

const CONTACT = {
  title: 'Contact',
  value: (item) => <Box>{item.contact}</Box>,
};

export { ID, AMOUNT, ACCOUNT_ID, CREATED_AT, TYPE, SOURCE, REFERENCE_ID, CONTACT };
