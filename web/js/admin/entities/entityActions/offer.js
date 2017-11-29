import { openModal, closeModal, confirm } from 'common/modal';
import fetch from 'util/fetch';
import { notifyError, notifySuccess } from 'common/modal';

import ShowWhen from 'admin/components/ShowWhen';
import AsyncButton from 'ui/AsyncButton';
import BaseModal from 'ui/BaseModal';
import Form from 'ui/Form';
import Field from 'ui/Field';

// Offer Actions
export default ({ entity, mode, updateEntity }) => {
  function updateOffer(body) {
    let successMsg;
    if (body.hasOwnProperty('active')) {
      successMsg = 'Offer is deactivated successfully';
    } else {
      successMsg = 'Offer is updated successfully';
    }

    return fetch({
      url: '/admin/generic',
      method: 'patch',
      params: {
        route_name: 'offer_update',
        mode: mode,
        url_params: {
          '{id}': entity.id,
        },
      },
      data: {
        merchant_id: entity.merchant_id,
        body,
      },
    })
      .then(data => {
        if (data) {
          notifySuccess(successMsg);
          updateEntity(data);
          closeModal();
        }
      })
      .catch(err => {
        notifyError(JSON.stringify(err));
      });
  }

  function openEditOffer() {
    openModal(<EditOfferForm entity={entity} handleSubmit={updateOffer} />);
  }

  return (
    <ShowWhen permission="edit_merchant_offer">
      <button class="label-info" onClick={openEditOffer}>
        Edit Offer
      </button>
      <AsyncButton
        onClick={() => updateOffer({ active: 0 })}
        class="btn btn-default text-danger"
        pendingClass="btn btn-default text-danger btn-pending"
        confirm="Are you sure you want to deactivate this offer?"
      >
        Deactivate Offer
        <span class="spin-btn" />
      </AsyncButton>
    </ShowWhen>
  );
};

// Edit Offer Form
const EditOfferForm = ({ entity, handleSubmit }) => {
  return (
    <BaseModal header="Edit Offer">
      <Form class="full-span full-elements">
        <Field label="Name" name="name" defaultValue={entity.name} />
        <Field
          label="Linked Offer ids"
          name="linked_offer_ids"
          defaultValue={entity.linked_offer_ids}
        />
        <Field
          label="Display Text"
          name="display_text"
          defaultValue={entity.display_text}
        />
        <Field
          label="Error Message"
          name="error_message"
          defaultValue={entity.error_message}
        />
        <Field label="Terms" name="terms" defaultValue={entity.terms} />

        <AsyncButton
          text="Submit"
          class="btn"
          pendingClass="small spinner"
          onSubmit={handleSubmit}
        />
      </Form>
    </BaseModal>
  );
};
