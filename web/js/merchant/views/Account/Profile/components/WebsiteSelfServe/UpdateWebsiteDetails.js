import { useState } from 'react';
import { connect } from 'react-redux';
import ModalHeader from 'common/ui/ModalHeader';
import * as ModalActions from 'merchant_common/reducers/modals';
import * as NotificationsActions from 'merchant_common/reducers/notifications';
import Popover, { PopoverBody } from 'common/ui/Popover';
import { bindActionCreators } from 'redux';
import Input from 'common/new-ui/Input';
import { merchantFetch } from 'merchant/utils/ajax';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

function WebsiteFields() {
  return (
    <>
      <Input required label="About us" name="about_us" />

      <Input required label="Contact us" name="contact_us" />

      <Input required label="Pricing details" name="pricing_details" />

      <Input required label="Terms and conditions" name="terms_conditions" />

      <Input required label="Privacy policy" name="privacy_policy" />

      <Input required label="Refund policy" name="refund_policy" />
    </>
  );
}

function UpdateWebsiteDetails(props) {
  const [type, settype] = useState('website');
  const [doesNeedCreds, setdoesNeedCreds] = useState(true);

  const save = async (e) => {
    e.preventDefault();
    const formData = Array.from(e.target.elements).reduce((acc, ele) => {
      acc[ele.name] = ele.value;
      return acc;
    }, {});

    const payload = {};
    let urlDetails = {};

    if (type === 'website') {
      payload.business_website_main_page = formData.website_url;
      payload.business_website_about_us = formData.about_us;
      payload.business_website_contact_us = formData.contact_us;
      payload.business_website_pricing_details = formData.pricing_details;
      payload.business_website_privacy_policy = formData.privacy_policy;
      payload.business_website_tnc = formData.terms_conditions;
      payload.business_website_refund_policy = formData.refund_policy;
      urlDetails = { ...payload };

      if (doesNeedCreds) {
        payload.business_website_username = formData.username;
        payload.business_website_password = formData.passwd;
      }
    } else {
      payload.business_app_url = formData.app_url;
      urlDetails = { ...payload };

      if (doesNeedCreds) {
        payload.business_app_username = formData.username;
        payload.business_app_password = formData.passwd;
      }
    }

    const { user } = props;

    analyticsTrack({
      objectName: `Website submit`,
      actionName: 'clicked',
      screen: 'My account',
      properties: {
        flow: user.has_key_access ? 'Website edit' : 'Website add',
        currentWebsite: `${user.business_website}`,
        urlDetails,
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });

    try {
      const response = await merchantFetch({
        url: `merchant/save_business_website/${type}`,
        method: 'POST',
        data: payload,
        headers: {
          'Content-Type': 'application/json',
        },
      });

      if (response) {
        props.showNotification({
          type: 'success',
          message: `${type} submitted successfully`,
        });

        props.getWebsiteWorkflowStatus();
        props.closeModal();
        analyticsTrack({
          objectName: `Website submit result`,
          actionName: 'Submit request',
          screen: 'My account',
          properties: {
            flow: user.has_key_access ? 'Website edit' : 'Website add',
            result: `Success`,
            ...getCommonAnalyticsProperties(window.rzp_user),
          },
        });
      }
    } catch ({ errors }) {
      props.showNotification({
        type: 'error',
        message: errors,
      });

      analyticsTrack({
        objectName: `Website submit result`,
        actionName: 'Submit request',
        screen: 'My account',
        properties: {
          flow: user.has_key_access ? 'Website edit' : 'Website add',
          result: `Failure`,
          failureReason: `${errors}`,
          ...getCommonAnalyticsProperties(window.rzp_user),
        },
      });
    }
  };

  const onTypeCheckboxClick = (typeValue) => {
    settype(typeValue);
    setdoesNeedCreds(true);

    let analyticsObject;
    const { user } = props;

    // Edit flow
    if (user.has_key_access) {
      analyticsObject = {
        objectName: `Url type selected`,
        actionName: 'Toggled',
        screen: 'My account',
        properties: {
          flow: 'Website edit',
          urlType: `${typeValue}`,
          ...getCommonAnalyticsProperties(window.rzp_user),
        },
      };
    } else {
      // Add flow
      analyticsObject = {
        objectName: `Url type selected`,
        actionName: 'Toggled',
        screen: 'My account',
        properties: {
          flow: 'Website add',
          urlType: `${typeValue}`,
          ...getCommonAnalyticsProperties(window.rzp_user),
        },
      };
    }

    analyticsTrack(analyticsObject);
  };

  const onNeedsCredsClick = (value) => setdoesNeedCreds(value);

  return (
    <div>
      <ModalHeader title="Update Website/App" onCloseClick={props.closeModal} />

      <div class="modal-body website-update-form">
        <form onSubmit={save}>
          <div class="help-block">
            Submit the url of your new website/app and the urls of all the other pages listed below
          </div>

          <div class="actions-header">
            <div>
              <input
                type="radio"
                class="radio-pointer"
                name="website"
                onChange={(e) => {
                  onTypeCheckboxClick(e.target.name, e.target.value);
                }}
                checked={type === 'website'}
              />
              <label>Website</label>
            </div>

            <div>
              <input
                type="radio"
                class="radio-pointer"
                name="app"
                onChange={(e) => {
                  onTypeCheckboxClick(e.target.name, e.target.value);
                }}
                checked={type === 'app'}
              />
              <label>App</label>
            </div>
          </div>

          <div class="form-group">
            <Input
              label={type === 'website' ? 'Website url' : 'App url'}
              autoFocus
              required
              name={type === 'website' ? 'website_url' : 'app_url'}
            />

            <hr />

            {type === 'website' && <WebsiteFields />}
          </div>

          <div class="form-group">
            <span>
              <strong>Test Account credentials </strong>
              <small class="help-content">
                <i class="i i-info-circle" />
                <Popover align="top" theme="dark">
                  <PopoverBody>
                    <div>
                      Please provide credentials of a demo account in case your {type} requires the
                      user to create an account to transact. This will help us to login and verify
                      your {type}.
                    </div>
                  </PopoverBody>
                </Popover>
              </small>
            </span>
            {type === 'website' && (
              <span>
                <Input.Check
                  fieldLabel="My website doesn’t require login to transact"
                  defaultValue={doesNeedCreds}
                  value={doesNeedCreds}
                  onChange={(e) => {
                    const value = e.target.value === '1';
                    onNeedsCredsClick(value);
                  }}
                />
              </span>
            )}

            {type === 'app' && (
              <span>
                <Input.Check
                  fieldLabel="My app doesn’t require login to transact"
                  defaultValue={doesNeedCreds}
                  value={doesNeedCreds}
                  onChange={(e) => {
                    const value = e.target.value === '1';
                    onNeedsCredsClick(value);
                  }}
                />
              </span>
            )}

            <Input placeholder="Username/email" name="username" />

            <Input placeholder="Password" type="password" name="passwd" />

            {type === 'app' && (
              <div class="note">
                Make sure your app has about us, privacy policy, terms and conditions, refund policy
                pages. Your app will not be approved without these pages
              </div>
            )}
          </div>

          <div class="Modal__actions">
            <button type="submit" class="btn btn-primary btn-block" text="">
              Submit {type} for review
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}

const mapStateToProps = (state) => {
  return {
    user: state.session.user,
  };
};

const mapDispatchToProps = (dispatch) => {
  return bindActionCreators(
    {
      ...ModalActions,
      ...NotificationsActions,
    },
    dispatch,
  );
};

export default connect(mapStateToProps, mapDispatchToProps)(UpdateWebsiteDetails);
