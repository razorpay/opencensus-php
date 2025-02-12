import { useState, useEffect } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

import { ModalContent } from 'common/new-ui/Modal';
import Form from 'common/new-ui/Form';
import Button, { AsyncBtn } from 'common/new-ui/Button';
import Input from 'common/new-ui/Input';
import PropagationStatusModal from './PropagationStatus';
import CustomClipboard from 'common/ui/Clipboard/Custom';
import { DocLink } from 'merchant/components/DocsLink';

import {
  checkDNSPropogation,
  createCustomDomainEntry,
} from 'merchant/views/PaymentPages/PaymentPages/model';
import { showNotification } from 'merchant_common/reducers/notifications';
import { updateCustomDomainDetails, updateSettings } from 'merchant/reducers/wysiwyg';
import track from 'merchant/views/PaymentPages/PaymentPages/Wysiwyg/track';

const GENERIC_ERROR_MESSAGE = 'Something went wrong, please try again later';

const DATA_MAP = {
  domain: {
    type: 'A',
    host: '@',
    pointsTo: {
      stage: '104.16.77.51',
      production: '104.16.223.226', // or 104.16.222.226
    },
    helpLinks: {
      goDaddy: 'https://in.godaddy.com/help/edit-an-a-record-19239',
      nameCheap:
        'https://www.namecheap.com/support/knowledgebase/article.aspx/319/2237/how-can-i-set-up-an-a-address-record-for-my-domain/',
      bluehost:
        'https://www.bluehost.com/help/article/dns-management-add-edit-or-delete-dns-entries#modify',
      googleDomains:
        'https://support.google.com/domains/answer/3290350#zippy=%2Cmodify-or-delete-a-resource-record',
    },
  },
  subdomain: {
    type: 'CNAME',
    host: '', // dynamically set
    pointsTo: {
      stage: 'custom-domain.np.razorpay.in',
      production: 'custom-domain.razorpay.com',
    },
    helpLinks: {
      goDaddy: 'https://in.godaddy.com/help/edit-a-cname-record-19237',
      nameCheap:
        'https://www.namecheap.com/support/knowledgebase/article.aspx/9646/2237/how-to-create-a-cname-record-for-your-domain/',
      bluehost: 'https://www.bluehost.com/hosting/help/cname#edit',
      googleDomains:
        'https://support.google.com/domains/answer/3290350#zippy=%2Cmodify-or-delete-a-resource-record',
    },
  },
};

const UpdateDNSModal = ({
  openModal,
  closeModal,
  domainName,
  isSubdomain,
  planDetails,
  showNotification,
  updateCustomDomainDetails,
  updateSettings,
}) => {
  const [isChecked, setChecked] = useState(false);
  const [staticData, setStaticData] = useState(DATA_MAP.domain);

  useEffect(() => {
    if (isSubdomain) {
      setStaticData({
        ...DATA_MAP.subdomain,
        host: domainName.split('.')[0],
      });
    } else {
      setStaticData(DATA_MAP.domain);
    }
  }, [domainName, isSubdomain]);

  const handleCheckbox = (e) => {
    setChecked(e.target.checked);

    track.settings.checkDnsConfigUpdated(e.target.checked);
  };

  const handleSubmit = (e) => {
    e.preventDefault();

    track.settings.clickVerifyConnection();

    return checkDNSPropogation(domainName)
      .then((response) => {
        if (response.data.propagated) {
          return createCustomDomainEntry(domainName, planDetails.id)
            .then((response) => {
              if (response.data.status === 'created') {
                closeModal();

                openModal({
                  size: 'medium',
                  className: 'pp-custom-domain',
                  component: <PropagationStatusModal closeModal={closeModal} status="success" />,
                });

                updateCustomDomainDetails({ value: domainName, planDetails });
                updateSettings({ custom_domain: domainName });

                track.settings.domainPropagationSuccess(domainName, planDetails);
              } else {
                showNotification({
                  type: 'error',
                  message: GENERIC_ERROR_MESSAGE,
                });
              }
            })
            .catch(() => {
              showNotification({
                type: 'error',
                message: GENERIC_ERROR_MESSAGE,
              });
            });
        } else {
          closeModal();

          openModal({
            size: 'medium',
            className: 'pp-custom-domain',
            component: <PropagationStatusModal closeModal={closeModal} status="failure" />,
          });

          track.settings.domainPropagationFailure();
        }

        return '';
      })
      .catch(() => {
        showNotification({
          type: 'error',
          message: GENERIC_ERROR_MESSAGE,
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
          <img src="https://cdn.razorpay.com/static/assets/globe.svg" alt="globe" width="20px" />
          Update DNS values
        </div>
        <i className="i i-close" onClick={handleClose} />
      </div>
      <Form>
        <main>
          <div>
            Go to your Domain provider and paste the below <br />
            <b>{staticData.type} record </b>
            value for your domain. This will point {domainName} to Razorpay.
          </div>
          <div className="table-responsive">
            <table className="table table-hover">
              <thead>
                <tr>
                  <th>Type</th>
                  <th>Host</th>
                  <th>Points to</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td>{staticData.type}</td>
                  <td>{staticData.host}</td>
                  <td>
                    <span className="m-r">
                      {staticData.pointsTo[window.APP_ENV] || staticData.pointsTo.production}
                    </span>
                    <CustomClipboard
                      value={staticData.pointsTo[window.APP_ENV] || staticData.pointsTo.production}
                    >
                      <button
                        type="button"
                        className="btn btn-default btn-xs"
                        onClick={track.settings.copyDnsConfig}
                      >
                        <i className="i i-copy" />
                        COPY
                      </button>
                    </CustomClipboard>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div className="help-text">
            <b>Need help?</b> Refer to our guides for{' '}
            <a href={staticData.helpLinks.goDaddy} target="_blank" rel="noreferrer noopener">
              GoDaddy
            </a>
            ,{' '}
            <a href={staticData.helpLinks.nameCheap} target="_blank" rel="noreferrer noopener">
              Namecheap
            </a>
            ,{' '}
            <a href={staticData.helpLinks.bluehost} target="_blank" rel="noreferrer noopener">
              Bluehost
            </a>
            ,
            <a href={staticData.helpLinks.googleDomains} target="_blank" rel="noreferrer noopener">
              Google Domains
            </a>{' '}
            or{' '}
            <DocLink href="https://razorpay.com/docs/payments/payment-pages/domain-linking#connect-your-domain-link">
              others
              <i className="i i-external-link" />
            </DocLink>
          </div>
          <Input.Check
            name="dns_updation"
            fieldLabel="I have updated the DNS value(s) in my Domain provider"
            className="spacing"
            onChange={handleCheckbox}
            checked={isChecked}
          />
        </main>
        <footer>
          <Button.Transparent className="Cancel-btn" type="button" onClick={handleClose}>
            Cancel
          </Button.Transparent>

          <AsyncBtn.Primary
            className="Save-btn"
            type="submit"
            onClick={handleSubmit}
            disabled={!isChecked}
            pendingState="Verifying..."
          >
            Verify Connection
          </AsyncBtn.Primary>
        </footer>
      </Form>
    </ModalContent>
  );
};

const mapDispatchToProps = (dispatch) => ({
  showNotification: bindActionCreators(showNotification, dispatch),
  updateCustomDomainDetails: bindActionCreators(updateCustomDomainDetails, dispatch),
  updateSettings: bindActionCreators(updateSettings, dispatch),
});

export default connect(null, mapDispatchToProps)(UpdateDNSModal);
