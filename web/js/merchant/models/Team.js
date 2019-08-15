import store from 'merchant/store';
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
      data: { mode: 'live' },
    });
  }

  fetchAll() {
    return Promise.all([this.fetchInvitations(), this.fetchTeamMembers()]).then(
      ([invitationResponse, teamMembersResponse]) => {
        const currentUser = store.getState().session.user.user;
        return {
          data: {
            items: [
              ...[...invitationResponse.data],
              ...[
                ...teamMembersResponse.data.filter(
                  member => member.id !== currentUser.id
                ),
              ],
            ],
          },
        };
      }
    );
  }

  unlock(memberId) {
    return this.makeGenericAjaxCall({
      url: `users/account/${memberId}/unlock`,
      method: 'put',
    }).then(({ data }) => ({ ...data }));
  }

  deleteMember(memberId) {
    return this.makeGenericAjaxCall({
      url: `users/${memberId}/detach`,
      method: 'put',
    });
  }

  cancelInvitation(id) {
    return this.makeGenericAjaxCall({
      url: 'invitations/' + id,
      method: 'delete',
      data: { mode: 'live' },
    });
  }
}
