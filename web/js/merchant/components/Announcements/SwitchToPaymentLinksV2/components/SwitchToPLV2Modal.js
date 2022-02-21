import ModalHeader from 'common/ui/ModalHeader';
import Button from 'common/new-ui/Button';
import { DocLink } from 'merchant/components/DocsLink';

import React from 'react';
import { connect } from 'react-redux';
import { showNotification } from 'merchant_common/reducers/notifications';
import { switchToV2 } from '../model';

const DOCS_LINK = 'https://razorpay.com/docs/payment-links/api/new/';
const DEPRECATION_DATE = '30 April, 2021';

@connect((state) => ({ user: state.session.user }), {
  showNotification,
})
class SwitchToPLV2Modal extends React.Component {
  static contextTypes = {
    // eslint-disable-next-line no-undef
    confirm: PropTypes.func,
  };

  handleSwitchNow = () => {
    this.props.closeModal();

    this.props.track.lj.switchModal.confirm();

    this.openConfirmModal();
  };

  handleSwitchLater = () => {
    this.props.closeModal();

    this.props.track.lj.switchModal.cancel();
  };

  openConfirmModal = () => {
    this.context.confirm({
      header: 'Updating to New APIs',
      message: () => (
        <span>
          This is a one-time change and it cannot be reverted back.
          <br />
          <br />
          Please contact support if you face any issues after migration.
          <br />
          <br />
        </span>
      ),
      affirmativeLabel: 'Yes, Update Now',
      abortLabel: 'No, Update Later',
      action: () => {
        this.props.track.lj.confirmModal.confirm();

        return switchToV2()
          .then(() => {
            setTimeout(this.openSuccessModal, 500);

            this.props.track.lj.confirmModal.success();
          })
          .catch(({ errors }) => {
            this.props.showNotification({
              type: 'error',
              message: errors,
            });

            this.props.track.lj.confirmModal.failure();
          });
      },
      abort: () => {
        this.props.track.lj.confirmModal.cancel();
      },
    });
  };

  openSuccessModal = () => {
    this.context.confirm({
      header: (
        <span>
          <i class="i-check-circle" /> &nbsp; Migrated to new API service
        </span>
      ),
      message: () => (
        <span>
          Migration to new API service successful.
          <br />
          <br />
          To learn more about new service and its features, visit our{' '}
          <DocLink href={DOCS_LINK} target="_blank" rel="noopener">
            documentation page &nbsp;
            <i className="i i-external-link" />
          </DocLink>
          .
          <br />
          <br />
        </span>
      ),
      affirmativeLabel: 'Close',
      className: 'PaymentLinks--MigrationSuccess',
      action: () => {
        window.location.reload();
      },
    });
  };

  render() {
    return (
      <div>
        <ModalHeader title="New APIs for Payment Link" />

        <div class="modal-body">
          <div>Switch to new Payment Link API to start enjoying new features like UPI PL, etc.</div>
          <br />
          <div>
            <b>
              Switching to new API contract will result in a change in API URL, request, and
              response parameters.
            </b>
          </div>
          <br />
          <div>
            Current Payment Link APIs will be <b>deprecated by {DEPRECATION_DATE}.</b>
          </div>
          <br />
          <a href={DOCS_LINK} target="_blank" rel="noopener noreferrer">
            Click here to know more &nbsp;
            <i className="i i-external-link " />
          </a>
          <br />
          <br />
          <footer>
            <Button onClick={this.handleSwitchLater}>Switch Later</Button>

            <Button.Primary onClick={this.handleSwitchNow}>Switch Now</Button.Primary>
          </footer>
        </div>
      </div>
    );
  }
}

export default SwitchToPLV2Modal;
