import { useState } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

import { ModalContent } from 'common/new-ui/Modal';
import Form from 'common/new-ui/Form';
import Button, { AsyncBtn } from 'common/new-ui/Button';
import Input from 'common/new-ui/Input';
import UpdateDNSModal from './UpdateDNSValues';
import { DocLink } from 'merchant/components/DocsLink';

import GlobeImage from '../../../../../../../../css/assets/payment_pages/globe.svg';

import { getIfDomainAlreadyLinked, getIfSubDomain } from '../../../model';
import { showNotification } from 'merchant_common/reducers/notifications';

// allowing user to enter domain like https://mydomain.com/ for better UX, pruning later
const DOMAIN_SUBDOMAIN_REGEX = new RegExp(
  /^(?:https?:\/\/)?(?!-)((?:(?:[a-zA-Z\d][a-zA-Z\d-]{0,61})?[a-zA-Z\d]\.){1,126}(?!\d+)[a-zA-Z\d]{1,63})\/?$/,
);

const validateDomainOrSubdomain = (value) => {
  let error = '';

  if (value && !DOMAIN_SUBDOMAIN_REGEX.test(value)) {
    error = 'This does not look like a valid domain or subdomain';
  }

  return error;
};

const DomainAddressModal = ({ openModal, closeModal, showNotification }) => {
  const [value, setValue] = useState();

  const handleInputChange = (e) => {
    setValue(e.target.value);
  };

  const handleSubmit = () => {
    let isDomainAlreadyUsed, isSubdomain;

    // extracting domain/subdomain
    const prunedDomainName = DOMAIN_SUBDOMAIN_REGEX.exec(value)[1];

    // API call to check if domain not already been setup by some other merchant
    const domainAlreadyLinkedPromise = getIfDomainAlreadyLinked(prunedDomainName).then(
      (response) => {
        isDomainAlreadyUsed = response.data.exists;

        if (isDomainAlreadyUsed) {
          showNotification({
            type: 'error',
            message: 'The domain is already being used, please try again with some other domain',
          });
        }
      },
    );

    // API call to check if subdomain or domain entered
    const ifSubDomainPromise = getIfSubDomain(prunedDomainName).then((response) => {
      isSubdomain = response.data.is_sub_domain;
    });

    return Promise.all([domainAlreadyLinkedPromise, ifSubDomainPromise]).then(() => {
      if (!isDomainAlreadyUsed) {
        closeModal();

        openModal({
          size: 'medium',
          className: 'pp-custom-domain',
          component: (
            <UpdateDNSModal
              openModal={openModal}
              closeModal={closeModal}
              domainName={prunedDomainName}
              isSubdomain={isSubdomain}
            />
          ),
        }).catch(() => {
          showNotification({
            type: 'error',
            message: 'Something went wrong, please try again later',
          });
        });
      }
    });
  };

  const handleClose = () => {
    closeModal();
  };

  return (
    <ModalContent>
      <div class="main-title">
        <div class="heading">
          <img src={GlobeImage} alt="globe" width="20px" />
          Enter your domain address
        </div>
        <i className="i i-close" onClick={handleClose} />
      </div>
      <Form>
        <main>
          <div>
            Enter the domain or subdomain address that you want to use for this payment page’s URL
          </div>
          <Input
            name="domain_address"
            defaultValue={value}
            description={
              <div>
                An example of a domain is: <i>mydomain.com</i> <br />
                and example of a subdomain is: <i>example.mydomain.com</i>
              </div>
            }
            placeholder="mydomain.com"
            validator={validateDomainOrSubdomain}
            onChange={handleInputChange}
          />
          <div class="help-text spacing">
            <b>Need help?</b> Refer to{' '}
            <DocLink href="https://razorpay.com/docs/payments/payment-pages/domain-linking#connect-your-domain-link">
              <b>detailed steps </b>
              <i class="i i-external-link" />
            </DocLink>
          </div>
        </main>
        <footer>
          <Button.Transparent class="Cancel-btn" type="button" onClick={handleClose}>
            Cancel
          </Button.Transparent>

          <AsyncBtn.Primary
            class="Save-btn"
            type="submit"
            disabled={!value || !DOMAIN_SUBDOMAIN_REGEX.test(value)}
            onClick={handleSubmit}
            pendingState="Checking..."
          >
            Next
          </AsyncBtn.Primary>
        </footer>
      </Form>
    </ModalContent>
  );
};

const mapDispatchToProps = (dispatch) => ({
  showNotification: bindActionCreators(showNotification, dispatch),
});

export default connect(null, mapDispatchToProps)(DomainAddressModal);
