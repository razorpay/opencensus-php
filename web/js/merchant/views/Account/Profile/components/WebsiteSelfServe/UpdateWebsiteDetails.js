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
import { getCommonAnalyticsProperties, autoPrefixUrls } from 'common/utils/rzp-utils';
import FileUpload from 'merchant/components/File/Upload';
import { FLOWS } from './Constants';
import { fetchWorkflowStatus as fetchWorkflowStatusReducer } from 'merchant/reducers/workflows';
import { WORKFLOW_TYPES } from 'merchant/views/Account/Profile/components/WorkflowRequests/constants';
import { selfServeTrackSuccess } from 'common/utils/selfServeAnalytics';
import { useBusinessWebsiteRevamp } from 'merchant/views/AccountAndSettings/WebsiteAppSettings/Tabs/BusinessWebsiteDetails/utils';

function WebsiteFields({
  flowType,
  handleFileChange,
  onBiggerFileSize,
  onCloseClick,
  file,
  validator,
}) {
  const isBusinessWebsiteRevamp = useBusinessWebsiteRevamp();

  return (
    <>
      {isBusinessWebsiteRevamp && flowType === FLOWS.BUSINESS_WEBSITE ? null : (
        <>
          <Input
            required
            label="About us"
            name="about_us"
            validator={(input) => {
              return validator('about_us', input);
            }}
          />

          <Input
            required
            label={
              <>
                Pricing details{' '}
                <small class="help-content">
                  <i class="i i-info-circle" />
                  <Popover align="top" theme="dark" parentQuerySelector=".modal-body">
                    <PopoverBody>
                      <div>
                        In case of multiple product pricing pages, please share a URL for any one of
                        them
                      </div>
                    </PopoverBody>
                  </Popover>
                </small>
                {}
              </>
            }
            name="pricing_details"
            validator={(input) => {
              return validator('pricing_details', input);
            }}
          />
        </>
      )}

      {isBusinessWebsiteRevamp && flowType === FLOWS.BUSINESS_WEBSITE ? (
        <Input
          label="Shipping policy"
          name="shipping_policy"
          validator={(input) => {
            return validator('shipping_policy', input);
          }}
        />
      ) : null}

      <Input
        required
        label="Contact us"
        name="contact_us"
        validator={(input) => {
          return validator('contact_us', input);
        }}
      />

      <Input
        required
        label={
          <a
            target="_blank"
            href="https://docs.google.com/document/d/1bCwt0WccF7oDMBGAGRxtPgUfzqGzkUjtLnnE1JlL2dg/pub"
            rel="noreferrer noopener"
          >
            Terms and conditions
          </a>
        }
        name="tnc"
        validator={(input) => {
          return validator('tnc', input);
        }}
      />

      <Input
        required
        label={
          <a
            target="_blank"
            href="https://docs.google.com/document/d/1yqqWTE_jfC8F_u9UV9nLq3AUZR2wwpQGJigRJV3YQvg/pub"
            rel="noreferrer noopener"
          >
            Privacy policy
          </a>
        }
        name="privacy_policy"
        validator={(input) => {
          return validator('privacy_policy', input);
        }}
      />

      <Input
        required
        label={
          <a
            target="_blank"
            href="https://docs.google.com/document/d/1xYM1QHm9S5phnkzyENqJ3KXv37schlsiTp0Id_4IMwE/pub"
            rel="noreferrer noopener"
          >
            Cancellation/Refund Policy
          </a>
        }
        name="refund_policy"
        validator={(input) => {
          return validator('refund_policy', input);
        }}
      />

      {flowType === FLOWS.ADDITIONAL_WEBSITE && (
        <div class="upload-invoice">
          <label>Upload invoice</label>
          <FileUpload
            accept={['jpg', 'png', 'pdf']}
            maxSize={1048576} // 1 MB
            showCloseBtn
            showFileSize={false}
            showAcceptInfo={false}
            onBiggerFileSize={onBiggerFileSize}
            onFileChange={handleFileChange}
            onCloseClick={onCloseClick}
          />
          {file?.message ? <label class="notify-error">{file.message}</label> : null}
        </div>
      )}
    </>
  );
}

function UpdateWebsiteDetails(props) {
  const [type, settype] = useState('website');
  const [doesNeedCreds, setdoesNeedCreds] = useState(true);
  const [file, setfile] = useState(null);
  const [isReasonValid, setisReasonValid] = useState(null); // Validity => minimum 100 words
  const [isLinkValid, setisLinkValid] = useState(true); // Validity => should not be an already existing one
  const isBusinessWebsiteRevamp = useBusinessWebsiteRevamp();

  const [areMetaUrlsValid, setareMetaUrlsValid] = useState(() => {
    if (isBusinessWebsiteRevamp && props.flowType === FLOWS.BUSINESS_WEBSITE) {
      return {
        shipping_policy: true,
        contact_us: true,
        tnc: true,
        privacy_policy: true,
        refund_policy: true,
      };
    } else {
      return {
        about_us: true,
        contact_us: true,
        tnc: true,
        pricing_details: true,
        privacy_policy: true,
        refund_policy: true,
      };
    }
  });

  const submitBusinessDetails = async (formFieldValues) => {
    const payload = {};
    let urlDetails = {};

    if (type === 'website') {
      // Gather all field values into payload
      Object.keys(formFieldValues).forEach((key) => {
        const isKeyCred = ['username', 'password'].includes(key);
        const value = formFieldValues[key];
        payload[`business_website_${key}`] = isKeyCred ? value : autoPrefixUrls(value);
      });

      if (isBusinessWebsiteRevamp) {
        payload.version = 'v2';
        if (payload.business_website_shipping_policy === '') {
          delete payload.business_website_shipping_policy;
        }
      }

      // If creds are not checked, removing these keys
      if (!doesNeedCreds) {
        delete payload.business_website_username;
        delete payload.business_website_password;
      }

      urlDetails = { ...payload };
      // Removing creds from analytics
      delete urlDetails.business_website_username;
      delete urlDetails.business_website_password;
    } else {
      payload.business_app_url = autoPrefixUrls(formFieldValues.app_url);
      urlDetails = { ...payload };

      if (doesNeedCreds) {
        payload.business_app_username = formFieldValues.username;
        payload.business_app_password = formFieldValues.password;
      }
    }

    const { user } = props;
    if (urlDetails?.business_website_main_page === user?.business_website) {
      props.showNotification({
        type: 'error',
        message: `${type} Updating with same detail, please change main website`,
      });
      return;
    }
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
        mode: 'live',
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
        props.fetchWorkflowStatus(WORKFLOW_TYPES.UPDATE_BUSINESS_WEBSITE);
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

  const submitAdditionalDomainDetails = async (formFieldValues) => {
    const formData = new FormData();

    if (type === 'website') {
      Object.keys(formFieldValues).forEach((key) => {
        if (key === 'username' || key === 'password')
          formData.append(`additional_website_test_${key}`, formFieldValues[key]);
        else
          formData.append(
            `additional_website_${key}`,
            key === 'reason' ? formFieldValues[key] : autoPrefixUrls(formFieldValues[key]),
          );
      });

      // If creds are not checked, removing these keys
      if (!doesNeedCreds) {
        formData.delete('additional_website_test_username');
        formData.delete('additional_website_test_password');
      }

      if (file instanceof File) formData.append('additional_website_proof_url', file);
    } else {
      formData.append('additional_app_url', autoPrefixUrls(formFieldValues.app_url));
      formData.append('additional_app_reason', formFieldValues.reason);

      if (doesNeedCreds) {
        formData.append('additional_app_test_username', formFieldValues.username);
        formData.append('additional_app_test_password', formFieldValues.password);
      }
    }

    try {
      const response = await merchantFetch({
        url: `merchant/additional_website/${type}`,
        method: 'POST',
        mode: 'live',
        data: formData,
        headers: {
          'Content-Type': 'application/json',
        },
      });

      if (response) {
        const { user } = props;
        props.showNotification({
          type: 'success',
          message: `${type} submitted successfully`,
        });
        if (props.flowType === FLOWS.ADDITIONAL_WEBSITE) {
          selfServeTrackSuccess({
            selfServeAction: 'Additional Website - App Url Updated',
            page: user?.isAccountAndSettingsRevampEnabled ? 'Business Website Details' : 'Profile',
            screen: user?.isAccountAndSettingsRevampEnabled ? 'Account & Settings' : 'My Account',
          });
        }

        props.fetchWorkflowStatus(WORKFLOW_TYPES.ADD_ADDITIONAL_WEBSITE);
        props.closeModal();
      }
    } catch ({ errors }) {
      props.showNotification({
        type: 'error',
        message: errors,
      });
    }
  };

  const save = (e) => {
    e.preventDefault();

    const formFieldValues = Array.from(e.target.elements).reduce((acc, ele) => {
      acc[ele.name] = ele.value;
      return acc;
    }, {});

    delete formFieldValues.website;
    delete formFieldValues.app;
    delete formFieldValues[''];

    if (props.flowType === FLOWS.BUSINESS_WEBSITE) {
      submitBusinessDetails(formFieldValues);
    } else {
      submitAdditionalDomainDetails(formFieldValues);
    }
  };

  const onTypeCheckboxClick = (typeValue) => {
    settype(typeValue);
    setdoesNeedCreds(true);
    setfile(null);

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

  const handleFileChange = (uploadedFile, _) => setfile(uploadedFile);

  const onCloseClick = () => setfile(null);

  const onBiggerFileSize = () => {
    const err = new Error('Document too large. Max limit 1MB', { cause: 'FILE_SIZE_EXCEEDED' });
    setfile(err);
  };

  const onTextInputBlur = (e) => {
    const input = e.target.value;
    const tokens = input.split(' ');

    if (tokens.length >= 50) setisReasonValid(true);
    else setisReasonValid(false);
  };

  const isUrlValid = (url) => {
    url = url || '';

    // references: web/js/common/utils/validators.js => isUrlLenient()
    // disabled on purpose because I don't want to break the regex & I have no clue why it's complicated
    // eslint-disable-next-line no-useless-escape
    const urlRegExp = /^(https?:\/\/)?[\w.-]+(?:\.[\w\.-]+)+[\w\-\._~:/?#[\]@!\$&'\(\)\*\+,;=.]+$/;
    return urlRegExp.test(url);
  };

  const validateWebsiteNAppLink = (input) => {
    const value = isUrlValid(input);
    if (!value) {
      setisLinkValid(false);
      return 'Please enter valid url';
    } else {
      setisLinkValid(true);
    }
    if (props.flowType === FLOWS.ADDITIONAL_WEBSITE) {
      const { user } = props;

      // if additional website doesn't exits => Normal flow
      if (!user.additional_websites) return '';
      else {
        // Additional websites exists, check if new website already exists
        if (user.additional_websites.includes(input)) {
          setisLinkValid(false);
          return 'You have already added this website';
        }
        setisLinkValid(true);
        return '';
      }
    }

    return '';
  };

  const validateMetaUrls = (fieldName, input) => {
    // ignore shipping policy
    if (fieldName === 'shipping_policy' && input === '') {
      return '';
    }
    const value = isUrlValid(input);
    if (!value) {
      const _obj = { ...areMetaUrlsValid };
      _obj[fieldName] = false;
      setareMetaUrlsValid(_obj);
      return 'Please enter valid url';
    } else {
      const _obj = { ...areMetaUrlsValid };
      _obj[fieldName] = true;
      setareMetaUrlsValid(_obj);
    }

    return '';
  };

  const areAllMetaLinksValid = () => {
    let areAllValid = true;
    Object.keys(areMetaUrlsValid).forEach((key) => {
      const value = areMetaUrlsValid[key];
      // ignore shippping policy
      if (!value && key !== 'shipping_policy') areAllValid = false;
    });

    return areAllValid;
  };

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
              name={type === 'website' ? 'main_page' : 'app_url'}
              validator={validateWebsiteNAppLink}
            />

            <hr />

            {type === 'website' && (
              <WebsiteFields
                handleFileChange={handleFileChange}
                onCloseClick={onCloseClick}
                flowType={props.flowType}
                onBiggerFileSize={onBiggerFileSize}
                file={file}
                validator={validateMetaUrls}
              />
            )}

            {props.flowType === FLOWS.ADDITIONAL_WEBSITE && (
              <>
                <Input.Textarea
                  placeholder="Min 50 Words"
                  label="Reason for adding new website"
                  required
                  name="reason"
                  onBlur={onTextInputBlur}
                />
                {isReasonValid === false && (
                  <label style={{ color: '#f05050' }}>Minimum 50 words required</label>
                )}
              </>
            )}
          </div>

          <div class="form-group">
            <span class="info-container">
              <strong>Test account credentials</strong>
              <small class="help-content">
                <i class="i i-info-circle" />
                <Popover align="top" theme="dark" parentQuerySelector=".modal-body">
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
                  value={!doesNeedCreds}
                  onChange={(e) => {
                    const value = e.target.value === '1';
                    onNeedsCredsClick(!value);
                  }}
                />
              </span>
            )}

            {type === 'app' && (
              <span>
                <Input.Check
                  fieldLabel="My app doesn’t require login to transact"
                  value={!doesNeedCreds}
                  onChange={(e) => {
                    const value = e.target.value === '1';
                    onNeedsCredsClick(!value);
                  }}
                />
              </span>
            )}

            {doesNeedCreds && (
              <>
                <Input placeholder="Username/email" name="username" required />
                <Input placeholder="Password" type="password" name="password" required />
              </>
            )}

            {type === 'app' && (
              <div class="note">
                Make sure your app has about us, privacy policy, terms and conditions, refund policy
                pages. Your app will not be approved without these pages
              </div>
            )}
          </div>

          <div class="Modal__actions">
            <button
              type="submit"
              class="btn btn-primary btn-block"
              disabled={
                isReasonValid === false || isLinkValid === false || areAllMetaLinksValid() === false
              }
            >
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
      fetchWorkflowStatus: fetchWorkflowStatusReducer,
    },
    dispatch,
  );
};

export default connect(mapStateToProps, mapDispatchToProps)(UpdateWebsiteDetails);
