import store from 'merchant/store';
import GenericEntity from './GenericEntity';

import { pickProps } from 'common/utils/rzp-utils';

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

  updateContact({ id, contact_mobile }) {
    return this.makeGenericAjaxCall({
      url: `users/contact`,
      method: 'PATCH',
      data: {
        user_id: id,
        contact_mobile,
      },
    }).then(response => {
      return { ...pickProps(response.data, 'contact_mobile') };
    });
  }

  updateRole({ id, role }) {
    return this.makeGenericAjaxCall({
      url: `users/${id}/update`,
      method: 'PUT',
      data: {
        role,
        mode: 'live',
      },
    }).then(response => {
      return { ...pickProps(response.data, 'role') };
    });
  }

  updateMember(data) {
    // 2 API calls because of bad implentation from Backend
    // They're keeping different API calls for updating contact_mobile and role
    const apiCalls = [];
    if (data.contact_mobile) {
      apiCalls.push(
        this.updateContact({ ...pickProps(data, ['contact_mobile', 'id']) })
      );
    }

    if (data.role) {
      apiCalls.push(this.updateRole({ ...pickProps(data, ['role', 'id']) }));
    }

    return Promise.all(apiCalls).then(responses => {
      return [...responses, { id: data.id, role: data.role }].reduce(
        (newObject, response) => ({ ...response, ...newObject })
      );
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
}
