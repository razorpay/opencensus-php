/* RESOURCE UTILS */

export const fields = [
  ['Merchant ID', item => item.id],
  ['Name', item => item.name],
  ['Email', item => item.email],
  ['Referrer', item => item.referrer],
  ['Marketplace Owner', item => item.parent_id],
  ['Status', item => item.count],
  ['Registered At', item => item.created_at],
  ['Submitted At', item => item.merchant_detail.submitted_at],
  ['Tags', item => item.tag_list.join()],
];

export function openMerchantEntity() {
  const url = window.location.href + '/' + this.id;
  console.log('MERCHANT ENTITY..', this.id, window.location.href);
  window.open(url);
}
