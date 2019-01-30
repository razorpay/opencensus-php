/**
 * TODO: move this action to coupons route entity page later
 */
import { Component } from 'react';

import Form from 'ui/Form';
import AsyncButton from 'ui/AsyncButton';
import { DateField } from 'ui/Field';
import { ModalContent } from 'component/Modal';

import { openModal, closeModal, notifySuccess } from 'common/modal';
import { adminPatch } from 'common/fetch';

class EditCoupons extends Component {
  handleSubmit = body => {
    const { id } = this.props.entity;

    const endDate = moment(
      `${body.end_at_date} ${body.end_at_time}`,
      'DD/MM/YYYY HH:mm'
    ).unix();

    return adminPatch({
      url: `live/coupons/${id}`,
      data: { end_at: endDate },
    }).then(response => {
      if (response) {
        notifySuccess(
          `Coupon will deactivate on ${moment(endDate * 1000).format(
            'DD MMM YYYY hh:mm A'
          )}.`
        );
        closeModal();
        this.props.updateEntity(response);
      }
    });
  };
  render() {
    return (
      <ModalContent header="Deactivate Coupon">
        <Form class="full-span sql-report-generator-form promotion-form">
          <DateField
            required
            name="end_at_date"
            label="Deactivate at"
            component={
              <input type="time" name="end_at_time" defaultValue="23:59" />
            }
            disablePastDates={true}
            defaultValue={moment()}
          />
          <AsyncButton
            text="Confirm"
            pendingClass="btn-pending"
            class="btn"
            onSubmit={this.handleSubmit}
          />
        </Form>
      </ModalContent>
    );
  }
}

// credit Actions
export default ({ entity, updateEntity }) => {
  function deactivateCoupon() {
    openModal(<EditCoupons entity={entity} updateEntity={updateEntity} />);
  }

  return (
    <button class="btn" onClick={deactivateCoupon}>
      Deactivate
    </button>
  );
};
