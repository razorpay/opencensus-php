import store from 'merchant/store';
import GenericEntity from './GenericEntity';

export default class MerchantUser extends GenericEntity {
  resourceUrl = 'merchants-users';

  fetchAll() {
    return this.makeGenericAjaxCall({
      url: this.resourceUrl,
    }).then(response => {
      const currentUser = store.getState().session.user.user;
      return {
        data: {
          items: (response.data || []).filter(
            member => member.id !== currentUser.id
          ),
        },
      };
    });
  }

  fetchInvitations() {
    return this.makeGenericAjaxCall({
      url: 'invitations',
      data: { mode: 'live' },
    });
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
