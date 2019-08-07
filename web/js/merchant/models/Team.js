import GenericEntity from './GenericEntity';

export default class MerchantUser extends GenericEntity {
  resourceUrl = 'merchants-users';

  fetchAll(params) {
    return this.makeGenericAjaxCall({ data: params }).then(
      ({ data, ...response }) => ({
        ...response,
        data: { items: data },
      })
    );
  }

  deleteMember(memberId) {
    return this.makeGenericAjaxCall({
      url: `users/${memberId}/detach`,
      method: 'put',
    });
  }
}
