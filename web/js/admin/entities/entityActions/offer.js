import { openModal, closeModal, confirm } from 'common/modal';
import fetch from 'common/fetch';
import { notifyError, notifySuccess } from 'common/modal';

import ShowWhen from 'admin/components/ShowWhen';
import AsyncButton from 'ui/AsyncButton';
import BaseModal from 'ui/BaseModal';
import Form from 'ui/Form';
import Field from 'ui/Field';
import { formatDate } from 'common/util';

// Offer Actions
export default ({ entity, mode, updateEntity }) => {
  function updateOffer(body) {
    let successMsg;
    if (body.hasOwnProperty('active')) {
      successMsg = 'Offer is deactivated successfully';
    } else {
      successMsg = 'Offer is updated successfully';

      Object.keys(body).forEach(key => {
        body[key].trim();

        if (key === 'iins' && body.iins) {
          if (
            entity.iins &&
            body.iins
              .split(',')
              .sort()
              .join() === entity.iins.sort().join()
          ) {
            delete body.iins;
          } else {
            body.iins = body.iins.split(',');
          }
        } else if (body[key] === entity[key]) {
          delete body[key];
        }
      });

      if (!Object.keys(body).length) {
        notifyError('Make changes before submit');

        return;
      }
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
      {entity.active && (
        <button class="label-info" onClick={openEditOffer}>
          Edit Offer
        </button>
      )}

      {entity.active && (
        <AsyncButton
          onClick={() => updateOffer({ active: 0 })}
          class="btn btn-default text-danger"
          pendingClass="btn btn-default text-danger btn-pending"
          confirm="Are you sure you want to deactivate this offer?"
        >
          Deactivate Offer
          <span class="spin-btn" />
        </AsyncButton>
      )}
    </ShowWhen>
  );
};

// Edit Offer Form
const EditOfferForm = ({ entity, handleSubmit }) => {
  return (
    <BaseModal header="Edit Offer">
      <Form class="full-span full-elements">
        <Field label="Name" name="name" defaultValue={entity.name} />
        {['netbanking', 'wallet', 'upi'].indexOf(entity.payment_method) ===
          -1 && (
          <Field
            label="iins"
            name="iins"
            placeholder="Enter comma(,) separated values"
            defaultValue={entity.iins}
          />
        )}
        {entity.payment_method === 'card' && (
          <Field
            label="max Payment Count"
            name="max_payment_count"
            defaultValue={entity.max_payment_count}
          />
        )}
        <Field label="Name" name="name" defaultValue={entity.name} />

        <Field
          label="Linked Offer ids"
          name="linked_offer_ids"
          defaultValue={entity.linked_offer_ids}
        />
        <Field
          label="From"
          defaultValue={formatDate(entity.starts_at)}
          disabled
        />
        <Field label="To" defaultValue={formatDate(entity.ends_at)} disabled />
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
