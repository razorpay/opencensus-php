import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

import { ModalContent } from 'common/new-ui/Modal';
import Form from 'common/new-ui/Form';
import Button, { AsyncBtn } from 'common/new-ui/Button';
import Alert from 'common/new-ui/Alert';

import { removeCustomDomainEntry } from 'merchant/views/PaymentPages/PaymentPages/model';
import { updateCustomDomainDetails, updateSettings } from 'merchant/reducers/wysiwyg';
import { showNotification } from 'merchant_common/reducers/notifications';
import track from 'merchant/views/PaymentPages/PaymentPages/Wysiwyg/track';

const RemoveDomainModal = ({
  domainName,
  nextBillingDate,
  closeModal,
  updateCustomDomainDetails,
  updateSettings,
  showNotification,
}) => {
  const handleSubmit = () => {
    track.settings.removeDomainConfirm();

    return removeCustomDomainEntry(domainName)
      .then(() => {
        updateCustomDomainDetails({ value: '' });
        updateSettings({ custom_domain: '' }); // setting page level domain name to '' so that it is set to the default -> pages.razorpay.com

        closeModal();

        showNotification({
          type: 'success',
          message: 'Your domain is removed',
        });
      })
      .catch(() => {
        showNotification({
          type: 'error',
          message: 'Something went wrong, please try again later',
        });
      });
  };

  const handleClose = () => {
    closeModal();
  };

  return (
    <ModalContent>
      <div className="main-title">
        <div className="heading">
          <i className="i i-warning-o" />
          Remove domain
        </div>
        <i className="i i-close" onClick={handleClose} />
      </div>
      <Form>
        <main>
          <div>
            <b>Do you want to remove the domain {domainName}?</b> If you proceed, the URL of all
            payment pages that are using this domain will be changed.
          </div>
          <br />
          <Alert.Warning iconBefore="i-info-outline">
            Your plan will be active till <strong>{nextBillingDate}</strong> and cancelled on the
            following day
          </Alert.Warning>
          <br />
        </main>
        <footer>
          <Button.Transparent className="Cancel-btn" type="button" onClick={handleClose}>
            No, don't remove
          </Button.Transparent>
          <AsyncBtn.Primary
            className="Save-btn"
            type="submit"
            onClick={handleSubmit}
            pendingState="Removing..."
          >
            Yes, remove domain
          </AsyncBtn.Primary>
        </footer>
      </Form>
    </ModalContent>
  );
};

const mapDispatchToProps = (dispatch) => ({
  updateCustomDomainDetails: bindActionCreators(updateCustomDomainDetails, dispatch),
  updateSettings: bindActionCreators(updateSettings, dispatch),
  showNotification: bindActionCreators(showNotification, dispatch),
});

export default connect(null, mapDispatchToProps)(RemoveDomainModal);
