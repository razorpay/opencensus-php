import { openModal, closeModal, confirm } from 'common/modal';
import fetch, { adminPut, adminDelete } from 'util/fetch';
import { notifyError, notifySuccess } from 'common/modal';

import BaseModal from 'ui/BaseModal';
import Form from 'ui/Form';
import Field from 'ui/Field';
import AsyncButton from 'ui/AsyncButton';
import Table from 'ui/Table';

import EditTerminalForm from '../../merchants/entity/entityModals/AssignTerminal';

// Terminal Actions
export default ({ entity, mode, updateEntity }) => {
  function updateTerminal(body) {
    if (body.terminal_mode) {
      body.mode = body.terminal_mode;
      delete body.terminal_mode;
    }

    if (body.emi == 0) {
      delete body.emi_duration;
    }

    // Remove the unchanged keys inside body.type
    for (let key in body.type) {
      if (body.type[key] == '0') {
        // Remove if value is 0
        delete body.type[key];
      }
    }
    if (!Object.keys(body.type).length) {
      delete body.type;
    }

    return adminPut({
      route_name: 'terminal_edit',
      url_params: {
        id: entity.id,
      },
      mode: mode,
      body,
    })
      .then(data => {
        if (data && (typeof data.success === 'undefined' || data.success)) {
          notifySuccess('Terminal is successfully updated');
          updateEntity(data);
          closeModal();
        }
      })
      .catch(err => notifyError(JSON.stringify(err)));
  }

  function openEditTerminal() {
    openModal(
      <EditTerminalForm
        entity={entity}
        isEditMode={true}
        merchantId={entity.merchant_id}
        handleEdit={updateTerminal}
      />
    );
  }

  function changePrimaryMerchant() {
    openModal(
      <EditPrimaryMerchantForm
        id={entity.merchant_id}
        handleSubmit={updatePrimaryMerchant}
      />
    );
  }

  function terminalMerchantAssign() {
    openModal(
      <AssignSubMerchants
        submerchants={entity.sub_merchants}
        handleSubmit={updateSubmerchants}
        deleteSubmerchant={deleteSubmerchant}
      />
    );
  }

  function updateSubmerchants(body) {
    return adminPut({
      route_name: 'terminal_add_merchant',
      url_params: {
        id: entity.id,
        mid: body.merchant_id,
      },
      mode: mode,
    })
      .then(data => {
        if (data) {
          notifySuccess('Submerchant is successfully added');
          window.location.reload();
          closeModal();
        }
      })
      .catch(err => notifyError(JSON.stringify(err)));
  }

  function deleteSubmerchant(submerchantId) {
    adminDelete({
      route_name: 'terminal_remove_merchant',
      url_params: {
        id: entity.id,
        mid: submerchantId,
      },
      mode: mode,
    })
      .then(response => {
        notifySuccess('Sub merchant unassigned from the terminal successfully');
        closeModal();

        setTimeout(() => window.location.reload(), 1000); // TODO: Make request again instead of page refresh;
      })
      .catch(e => notifyError(JSON.stringify(e)));
  }

  function updatePrimaryMerchant(body) {
    return adminPut({
      route_name: 'terminal_reassign_merchant',
      url_params: {
        id: entity.id,
      },
      body: {
        merchant_id: body.merchant_id,
      },
      mode: mode,
    })
      .then(data => {
        if (data) {
          notifySuccess('Primary Merchant is successfully updated');
        }
      })
      .catch(err => notifyError(JSON.stringify(err)));
  }

  function deleteTerminal() {
    return fetch({
      method: 'delete',
      url: '/admin/generic/',
      params: {
        route_name: 'terminal_delete',
        url_params: {
          '{id}': entity.id,
        },
        mode: mode,
      },
    })
      .then(data => {
        if (data) {
          notifySuccess('Terminal is deleted successfully');
          setTimeout(
            () => window.open(`/admin/entities/${mode}/terminal`, '_self'),
            1000
          ); // TODO: Make request again instead of page refresh;
        }
      })
      .catch(err => {
        notifyError(JSON.stringify(err));
      });
  }

  function toggleEnableTerminal() {
    let isEnabled = entity.enabled;

    return adminPut({
      route_name: 'terminal_toggle',
      url_params: {
        id: entity.id,
      },
      mode: mode,
      body: { toggle: isEnabled ? 0 : 1 },
    });
  }

  return (
    <div>
      <div>
        <button class="label-info" onClick={openEditTerminal}>
          Edit Terminal
        </button>
        <button class="label-info" onClick={changePrimaryMerchant}>
          Change Primary Merchant
        </button>
        <button class="label-info" onClick={terminalMerchantAssign}>
          Assign Sub Merchants
        </button>
      </div>
      <div>
        <AsyncButton
          onClick={deleteTerminal}
          class="btn label-danger"
          pendingClass="btn btn-default label-danger btn-pending"
          confirm="Are you sure you want to delete this terminal?"
        >
          Delete Terminal
          <span class="spin-btn" />
        </AsyncButton>
        <AsyncButton
          onClick={toggleEnableTerminal}
          class={`btn btn-default text-${
            entity.enabled ? 'danger' : 'success'
          }`}
          pendingClass={`btn btn-default btn-pending text-${
            entity.enabled ? 'danger' : 'success'
          }`}
          confirm={`Are you sure you want to ${
            entity.enabled ? 'disable' : 'enable'
          } this terminal?`}
        >
          {entity.enabled ? 'Disable' : 'Enable'} Terminal
          <span class="spin-btn" />
        </AsyncButton>
      </div>
    </div>
  );
};

// Assign Submerchant to terminal form
const AssignSubMerchants = ({
  submerchants,
  handleSubmit,
  deleteSubmerchant,
}) => {
  function getSubmerchantFields() {
    return [
      ['Id', item => item.id],
      ['Name', item => item.name],
      [
        'Website',
        item =>
          item.website ? (
            <a class="link" target="_blank" href={item.website}>
              {item.website}
            </a>
          ) : (
            '--'
          ),
      ],
      [
        'Delete',
        item => (
          <div class="link danger" onClick={() => deleteSubmerchant(item.id)}>
            Delete
          </div>
        ),
      ],
    ];
  }

  return (
    <BaseModal header="Assign Sub Merchant To Terminal">
      <Form class="full-span full-elements">
        <Field label="Merchant id" name="merchant_id" />
        <AsyncButton
          text="Submit"
          class="btn"
          pendingClass="small spinner"
          onSubmit={handleSubmit}
        />
      </Form>
      {submerchants &&
        submerchants.length > 0 && (
          <Table
            items={submerchants}
            fields={getSubmerchantFields()}
            customClass="limited assign-submerchants"
          />
        )}
    </BaseModal>
  );
};

// Edit primary merchant form
const EditPrimaryMerchantForm = ({ id, handleSubmit }) => {
  return (
    <BaseModal header="Change Primary Merchant for the Terminal">
      <Form class="full-span full-elements">
        <Field
          label="Primary merchant id"
          name="merchant_id"
          defaultValue={id}
        />
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
