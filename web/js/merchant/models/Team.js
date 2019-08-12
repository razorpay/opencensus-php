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

  deleteMember(memberId) {
    return this.makeGenericAjaxCall({
      url: `users/${memberId}/detach`,
      method: 'put',
    });
  }

  updateMember({ id, ...data }) {
    return this.makeGenericAjaxCall({
      url: `users/${id}/update`,
      method: 'put',
      data: {
        ...data,
        mode: 'live',
      },
    });
  }

  cancelInvitation(id) {
    return this.makeGenericAjaxCall({
      url: 'invitations/' + id,
      method: 'delete',
      data: { mode: 'live' },
    });
  }

  sendInvitation(data) {
    return this.makeGenericAjaxCall({
      url: 'invitations',
      method: 'post',
      data: {
        ...data,
        mode: 'live',
      },
    }).then(response => ({
      ...response.data,
    }));
  }

  updateInvitation({ id, ...data }) {
    return this.makeGenericAjaxCall({
      url: `invitations/${id}`,
      method: 'patch',
      data: {
        ...data,
        mode: 'live',
      },
    }).then(response => ({
      ...response.data,
    }));
  }
}
