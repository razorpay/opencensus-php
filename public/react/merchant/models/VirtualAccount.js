import GenericEntity from './GenericEntity';

export default class VirtualAccount extends GenericEntity {
  listRouteName = 'virtual_account_fetch_multiple';
  detailsRouteName = 'virtual_account_fetch';
  deleteRouteName = 'virtual_account_delete';

  resourceFields = [
    'id',
    'name',
    'descriptor',
    'receiver_type',
    'customer_id',
    'status',
  ];
  receiver_type = 'bank_account';

  getRouteName() {
    return this.isNew ? 'virtual_account_create' : 'virtual_account_update';
  }
}
