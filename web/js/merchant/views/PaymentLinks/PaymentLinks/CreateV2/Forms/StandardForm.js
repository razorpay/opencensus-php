import React from 'react';
import { Modal, ModalContent } from 'common/new-ui/Modal';
import FormWizard from 'merchant/views/PaymentLinks/PaymentLinks/CreateV2/components/FormWizard';
import {
  Amount,
  PaymentFor,
  ContactDetails,
  Notify,
  ReferenceId,
  Reminders,
  PartialPayment,
  LinkExpiry,
  Notes,
  MWebContactDetails,
  DynamicFields,
} from 'merchant/views/PaymentLinks/PaymentLinks/CreateV2/components/Fields';
import { analyticsTrack } from 'common/utils/analytics';
import { classList, getURLQueryParams, getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import * as LocalStorageService from 'common/utils/localStorage';

// TODO: Feels like, can be written in better.
export default class StandardForm extends React.Component {
  onSubmit = () => {
    return this.props.onSubmit();
  };
  componentDidMount() {
    const getLandingProduct = LocalStorageService.getItem('merchant_landing_page');
    const params = getURLQueryParams(location.search);
    if (params?.link_type === 'standard' && getLandingProduct === 'payment_link') {
      LocalStorageService.removeItem('merchant_landing_page');
      analyticsTrack({
        objectName: 'Payment Link PopUp',
        actionName: 'Loaded',
        screen: 'payment link page',
        properties: {
          auto_pl_product: 'payment_link',
          ...getCommonAnalyticsProperties(window.rzp_user),
        },
      });
    }
  }
  render() {
    const { props } = this;
    const {
      formData,
      disableCurrencySelect,
      contactPlaceholder,
      dynamicFields,
      disabled,
      user,
      applications,
    } = props;

    const content = (
      <FormWizard
        isLoading={props.isLoading}
        isFormLocked={props.isFormLocked}
        isModalView={props.isModalView}
        title="Standard Payment Link"
        onSubmit={this.onSubmit}
        onChange={props.onChange}
        onClose={props.onClose}
        disableSubmit={props.disableSubmit}
        history={props.history}
      >
        <Amount
          disableCurrencySelect={disableCurrencySelect}
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
        <DynamicFields
          payerName={formData.name}
          dynamicFields={dynamicFields}
          disabled={disabled}
        />
        {props.isMobileResolution ? (
          <MWebContactDetails
            disabled={props.disabled}
            defaultContactNumber={formData.contact}
            defaultEmailAddress={formData.email}
            defaultContactValue={formData.sms_notify}
            defaultEmailValue={formData.email_notify}
            contactPlaceholder={contactPlaceholder}
          />
        ) : (
          <ContactDetails
            disabled={props.disabled}
            defaultContactNumber={formData.contact}
            defaultEmailAddress={formData.email}
            contactPlaceholder={contactPlaceholder}
          />
        )}
        <Notify
          disabled={props.disabled}
          defaultContactValue={formData.sms_notify}
          defaultEmailValue={formData.email_notify}
          user={user}
          applications={applications}
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
          checked={formData.reminder_enable}
        />
        <PartialPayment
          currency={formData.currency}
          amount={formData.amount}
          disabled={props.disabled}
          defaultValue={formData.accept_partial}
          defaultFirstMinAmount={formData.first_payment_min_amount}
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
          className={classList(
            'PaymentLink--CreateV2',
            props.showAnimationOnLoading && 'animate-down',
          )}
          showCloseBtn={false}
        >
          <ModalContent>{content}</ModalContent>
        </Modal>
      );
    }

    return <div className="StandAloneContainer">{content}</div>;
  }
}
