import GenericEntity from './GenericEntity';

export default class VirtualAccount extends GenericEntity {
  listRouteName = 'virtual_account_fetch_multiple';
  detailsRouteName = 'virtual_account_fetch';
}
