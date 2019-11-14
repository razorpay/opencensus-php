import { titleCase } from 'common/utils/rzp-utils';

const _entityToPrefix = {
  account: 'acc',
  balance_account: 'ba',
  balance_transfer: 'bt',
  card: 'card',
  customer: 'cust',
  dispute: 'dispute',
  payment: 'pay',
  offer: 'offer',
  order: 'order',
  plan: 'plan',
  refund: 'rfnd',
  reversal: 'rvrsl',
  settlement: 'setl',
  subscription: 'sub',
  token: 'token',
  transaction: 'txn',
  transfer: 'trf',
  virtual_account: 'va',
};

export function prefixEntityValue(entityName, value) {
  // TODO: Ideally api should fix this. In some cases, eg- 'pay_' is prepended and in some cases not.
  if (value.includes('_')) return value;
  let prefix = _entityToPrefix[entityName];

  if (prefix) {
    value = `${prefix}_${value}`;
  }
  return value;
}
