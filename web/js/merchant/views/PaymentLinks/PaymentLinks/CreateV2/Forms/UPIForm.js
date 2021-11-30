import React from 'react';
import { classList } from 'common/utils/rzp-utils';

import { Modal, ModalContent } from 'common/new-ui/Modal';
import FormWizard from '../components/FormWizard';
import {
  Amount,
  PaymentFor,
  ContactDetails,
  Notify,
  ReferenceId,
  Reminders,
  LinkExpiry,
  Notes,
} from '../components/Fields';

export default class UPIForm extends React.Component {
  onSubmit = () => {
    return this.props.onSubmit({
      upi_link: true,
    });
  };

  render() {
    const { props } = this;
    const { formData } = props;

    const content = (
      <FormWizard
        isLoading={props.isLoading}
        isFormLocked={props.isFormLocked}
        isModalView={props.isModalView}
        title="UPI Payment Link"
        onChange={props.onChange}
        onClose={props.onClose}
        disableSubmit={props.disableSubmit}
        onSubmit={this.onSubmit}
        history={props.history}
      >
        <Amount
          disableCurrencySelect
          isIntentDuplicate={props.isIntentDuplicate}
          disabled={props.disabled}
          defaultCurrency={formData.currency}
          defaultAmount={formData.amount}
        />
        <PaymentFor
          disabled={props.disabled}
          defaultValue={formData.description}
          required={props.isDescriptionRequired}
        />
        <ContactDetails
          disabled={props.disabled}
          defaultContactNumber={formData.contact}
          defaultEmailAddress={formData.email}
        />
        <Notify
          disabled={props.disabled}
          defaultContactValue={formData.sms_notify}
          defaultEmailValue={formData.email_notify}
        />
        <ReferenceId disabled={props.disabled} />
        <LinkExpiry
          onChange={props.updateDate}
          disabled={props.disabled}
          defaultValue={formData.expire_by}
        />
        <Reminders
          disabled={props.disabled}
          config={props.remindersConfig}
          hasNoExpiry={!props.formData.expire_by}
          defaultValue={formData.reminder_enable}
        />
        <Notes
          onChange={props.onChangeNotes}
          disabled={props.disabled}
          defaultValue={formData.notes}
        />
      </FormWizard>
    );

    if (props.isModalView) {
      return (
        <Modal
          class={classList('PaymentLink--CreateV2', props.showAnimationOnLoading && 'animate-down')}
          showCloseBtn={false}
        >
          <ModalContent>{content}</ModalContent>
        </Modal>
      );
    }

    return <div class="StandAloneContainer">{content}</div>;
  }
}
