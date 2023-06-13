import { Link } from 'react-router-dom';

const baseUrl = {
  pay: '/payments/',
  rfnd: '/refunds/',
  order: '/orders/',
  va: '/smartcollect/virtualaccounts/',
  plan: '/plans/',
  sub: '/subscriptions/',
  trf: '/route/transfers/',
  disp: '/disputes/',
  acc: '/partners/submerchants/',
  token: '/tokens/',
  rvrsl: '/route/reversals/',
  offer: '/offers/',
  qr: '/qr_codes/',
  ppi: '/stores/products/',
  iacc: '/wallet/accounts/',
};

const sources = {
  payment: 'order_id',
  refund: 'payment_id',
  transfer: 'source',
  reversal: 'transfer_id',
};

const batchBaseUrls = {
  payment_link: 'paymentlinks',
  payment_link_v2: 'paymentlinks',
  auth_link: 'subscriptions',
  recurring_charge: 'subscriptions',
  recurring_charge_bulk: 'subscriptions',
  recurring_charge_axis: 'subscriptions',
  linked_account_reversal: 'reversals',
  payment_transfer: 'route',
  linked_account_create: 'route',
  transfer_reversal: 'route',
  virtual_account_edit: 'smartcollect',
  payment_page: 'paymentpages',
};

const commissionBase = {
  subvention: 'subventions',
  commission: 'earnings',
};

export const idItem = (id) => <code>{id}</code>;

/* if label not present id will be used as label */
export const idLink = (id = '', label, _baseUrl = baseUrl, onClick, { type }) => {
  let url = _baseUrl[id.split('_')[0]];
  const item = label || idItem(id);
  if (url) {
    url += id;

    // Currently, being used in details view of payment page, in order to maintain the context that transactions tab is opened via payment page's detail view.
    const hash = window.location.hash;
    if (hash) {
      url += hash;
    }

    return (
      <Link to={url} {...(onClick ? { onClick: () => onClick({ type }) } : {})}>
        {item}
      </Link>
    );
  }
  return item;
};
/*
  idKey: value of this key in item object will be appened to url
  labeKey: value of this key in item object will be displayed as label in link
*/
const makePropLink = (idKey, labelKey) => (item) => idLink(item[idKey], item[labelKey]);

export const makeIdLink = (type) => (item, onClick) => {
  return idLink(
    item[`${item.entity === type ? '' : `${type}_`}id`],
    undefined,
    undefined,
    onClick,
    { type },
  );
};

export const payment = makeIdLink('payment');
export const refund = makeIdLink('refund');
export const batch = (item) => idItem(item.id);
export const settlement = makeIdLink('settlement');
export const order = makeIdLink('order');
export const dispute = makeIdLink('dispute');
export const token = makeIdLink('token');

export const transfer = makeIdLink('transfer');
export const source = (item, custom_base_url) => {
  const id = item[sources[item.entity]];

  return idLink(id, idItem(id), custom_base_url);
};
export const recipient = (item) => idItem(item.recipient);
export const reversal = makeIdLink('reversal');
export const credit = makeIdLink('credits');
export const qrCode = makeIdLink('qr_code');
export const storeProduct = makeIdLink();

export const batchLink = (item) => {
  const url = batchBaseUrls[item.type];
  return url ? (
    <Link to={`/${url}/batchuploads/${item.id}`}>{idItem(item.id)}</Link>
  ) : (
    idItem(item.id)
  );
};

export const commission = (item) => {
  return (
    <Link to={`/partners/${commissionBase[item.model]}/transactional/${item.id}`}>
      {idItem(item.id)}
    </Link>
  );
};

export const submerchant = makePropLink('id', 'name');
export const submerchantId = (item) => idItem(item.id);
