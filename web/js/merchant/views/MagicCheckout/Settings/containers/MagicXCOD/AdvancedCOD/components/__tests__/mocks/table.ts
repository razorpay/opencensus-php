import type { Rule } from 'merchant/reducers/magicCheckout/magicxACODRules/types';

export const shippingRules: Rule<'shipping'>[] = [
  {
    id: '001',
    merchant_id: 'm001',
    name: 'ShippingRule01',
    description: 'About ShippingRule01',
    type: 'shipping',
    rule: '',
  },
  {
    id: '002',
    merchant_id: 'm001',
    name: 'ShippingRule02',
    description: 'About ShippingRule02',
    type: 'shipping',
    rule: '',
  },
];

export const paymentRules: Rule<'payment'>[] = [
  {
    id: '003',
    merchant_id: 'm001',
    name: 'PaymentRule01',
    description: 'About PaymentRule01',
    type: 'payment',
    rule: '',
  },
  {
    id: '004',
    merchant_id: 'm001',
    name: 'PaymentRule02',
    description: 'About PaymentRule02',
    type: 'payment',
    rule: '',
  },
];
