import GenericEntity from './GenericEntity';

export default class MerchantUser extends GenericEntity {
  resourceUrl = 'merchants-users';

  fetchTeamMembers() {
    return this.makeGenericAjaxCall({
      url: this.resourceUrl,
    });
  }

  fetchInvitations() {
    return this.makeGenericAjaxCall({
      url: 'invitations',
    });
  }

  fetchAll() {
    return Promise.all([this.fetchInvitations(), this.fetchTeamMembers()]).then(
      ([invitationResponse, teamMembersResponse]) => {
        return {
          data: {
            items: [
              ...[...invitationResponse.data],
              ...[...teamMembersResponse.data],
            ],
          },
        };
      }
    );
  }

  deleteMember(memberId) {
    return this.makeGenericAjaxCall({
      url: `users/${memberId}/detach`,
      method: 'put',
    });
  }
}
