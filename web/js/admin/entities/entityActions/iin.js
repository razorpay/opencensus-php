import { openModal, closeModal, confirm } from 'common/modal';
import { adminPut } from 'util/fetch';
import { notifyError, notifySuccess } from 'common/modal';

import ShowWhen from 'admin/components/ShowWhen';
import AsyncButton from 'ui/AsyncButton';
import BaseModal from 'ui/BaseModal';
import Form from 'ui/Form';
import Field, { SelectField, CheckField } from 'ui/Field';

// iin Actions
export default ({ entity, updateEntity }) => {
  function updateIIN(body) {
    const iin = {
      category: body.category,
      country: body.country,
      emi: body.emi ? 1 : 0,
      issuer_name: body.issuer_name,
      issuer: body.issuer,
      trivia: body.trivia,
      network: body.network,
      type: body.type,
    };

    // Lets remove all the empty variables
    for (let key in iin) {
      if (iin[key] === '' || iin[key] === null) {
        delete iin[key];
      }
    }

    return adminPut({
      route_name: 'iin_edit',
      url_params: {
        id: body.iin,
      },
      body: iin,
    })
      .then(data => {
        if (data) {
          notifySuccess('IIN is updated successfully');
          updateEntity(data);
          closeModal();
        }
      })
      .catch(err => notifyError(JSON.stringify(err)));
  }

  function openEditIIN() {
    openModal(<EditIINForm entity={entity} handleSubmit={updateIIN} />);
  }

  return (
    <ShowWhen permission="edit_merchant_offer">
      <button class="label-info" onClick={openEditIIN}>
        Edit IIN
      </button>
    </ShowWhen>
  );
};

// Edit iin Form
const EditIINForm = ({ entity, handleSubmit }) => {
  console.log('ENTTIY..', entity);
  return (
    <BaseModal header="Edit iin">
      <Form class="full-span">
        <Field
          label="IIN (6 digit)"
          name="iin"
          defaultValue={entity.iin}
          disabled
        />

        <SelectField
          label="Network"
          name="network"
          defaultValue={entity.network}
        >
          <option value="" disabled>
            Select..
          </option>
          <option value="American Express">American Express</option>
          <option value="Diners Club">Diners Club</option>
          <option value="Discover">Discover</option>
          <option value="JCB">JCB</option>
          <option value="Maestro">Maestro</option>
          <option value="MasterCard">MasterCard</option>
          <option value="RuPay">RuPay</option>
          <option value="Visa">Visa</option>
          <option value="Union Pay">Union Pay</option>
          <option value="Unknown">Unknown</option>
        </SelectField>

        <SelectField label="Type" name="type" defaultValue={entity.type}>
          <option value="" disabled>
            Select..
          </option>
          <option value="credit">Credit</option>
          <option value="debit">Debit</option>
          <option value="unkown">Unknown</option>
        </SelectField>

        <Field
          label="Country (2 character)"
          name="country"
          defaultValue={entity.country}
          length="2"
        />
        <Field
          label="Category"
          name="category"
          defaultValue={entity.category}
          length="2"
        />
        <Field label="Issuer" name="issuer" defaultValue={entity.issuer} />

        <Field
          label="Issuer Name"
          name="issuer_name"
          defaultValue={entity.issuer_name}
          style={{ width: '200px' }}
        />

        <CheckField label="EMI" name="emi" defaultChecked={entity.emi}>
          <span class="m-l">EMI Available</span>
        </CheckField>

        <Field label="Trivia" name="trivia" defaultValue={entity.trivia} />

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
