import GenericEntity from './GenericEntity';

export default class VirtualAccount extends GenericEntity {
  listRouteName = 'virtual_account_fetch_multiple';
  detailsRouteName = 'virtual_account_fetch';

  resourceFields = ['id', 'name', 'descriptor', 'receiver_type', 'customer_id'];
  receiver_type = 'bank_account';

  getRouteName() {
    return this.isNew ? 'virtual_account_create' : 'virtual_account_update';
  }
}
