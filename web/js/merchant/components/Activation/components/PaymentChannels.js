import React from 'react';
import { connect } from 'react-redux';
import { compose } from 'redux';
import Input from 'common/new-ui/Input';
import { isUrlLenient } from 'common/utils/validators';
import * as EventActions from 'merchant/reducers/trackEvents';

const CustomPaymentsCahnnel = ({
  hasWebsiteAppURL,
  hasWebsiteURL,
  hasAppURL,
  onChange,
  onWebsiteInputChange,
  businessWebsite,
  playstoreUrl,
  sendErrorMessageToSegment,
  physicalStore,
  socialMedia,
  isOnKYCTab,
  trackEvents,
}) => {
  return (
    <>
      {!isOnKYCTab && (
        <>
          <div class="Input-label">Payment Channels</div>
          <div className="Input-content channel_checkbox">
            <div className="info-text">
              This allows us to recommend a suitable product for your business
            </div>
            <Input.Check
              name="physical_store"
              fieldLabel="Store/In-person"
              onChange={(e) => {
                trackEvents({
                  objectName: 'Checkbox',
                  actionName: 'Clicked',
                  screen: 'home page',
                  properties: {
                    'Checkbox Label': 'Payment Channels',
                    'Option Selected': 'Store/In-person',
                    'Element Type': 'Form',
                    Mandatory: 'Yes',
                  },
                });
                onChange(e);
              }}
              autoRender={true}
              className="Input-channels"
              extraClassName="contents"
              defaultValue={physicalStore}
            />
            <Input.Check
              name="social_media"
              fieldLabel="Social Media (e.g. WhatsApp)"
              onChange={(e) => {
                trackEvents({
                  objectName: 'Checkbox',
                  actionName: 'Clicked',
                  screen: 'home page',
                  properties: {
                    'Checkbox Label': 'Payment Channels',
                    'Option Selected': 'Social Media (e.g. WhatsApp)',
                    'Element Type': 'Form',
                    Mandatory: 'Yes',
                  },
                });
                onChange(e);
              }}
              autoRender={true}
              className="Input-channels"
              extraClassName="contents"
              defaultValue={socialMedia}
            />
            <Input.Check
              name="has_url"
              fieldLabel="Live Website/App"
              onChange={(e) => {
                trackEvents({
                  objectName: 'Checkbox',
                  actionName: 'Clicked',
                  screen: 'home page',
                  properties: {
                    'Checkbox Label': 'Payment Channels',
                    'Option Selected': 'Live Website/App',
                    'Element Type': 'Form',
                    Mandatory: 'Yes',
                  },
                });
                onChange(e);
              }}
              autoRender={true}
              className="Input-channels"
              extraClassName="contents"
              defaultValue={hasWebsiteAppURL}
            />
          </div>
        </>
      )}
      {hasWebsiteAppURL === '1' && (
        <>
          {!isOnKYCTab && (
            <div className="Input-content channel_checkbox website-app">
              <Input.Check
                name="app_website_url"
                value="1"
                fieldLabel="Accept payments on website"
                onChange={(e) => {
                  trackEvents({
                    objectName: 'Checkbox',
                    actionName: 'Clicked',
                    screen: 'home page',
                    properties: {
                      'Checkbox Label': 'Payment Channels',
                      'Option Selected': 'Accept payments on website',
                      'Element Type': 'Form',
                      Mandatory: 'Yes',
                    },
                  });
                  onChange(e);
                }}
                autoRender={true}
                className="Input-channels"
                extraClassName="contents"
                defaultValue={hasWebsiteURL}
              />
              <Input.Check
                name="app_url"
                fieldLabel="Accept payments on app"
                onChange={(e) => {
                  trackEvents({
                    objectName: 'Checkbox',
                    actionName: 'Clicked',
                    screen: 'home page',
                    properties: {
                      'Checkbox Label': 'Payment Channels',
                      'Option Selected': 'Accept payments on app',
                      'Element Type': 'Form',
                      Mandatory: 'Yes',
                    },
                  });
                  onChange(e);
                }}
                autoRender={true}
                className="Input-channels"
                extraClassName="contents"
                defaultValue={hasAppURL}
              />
            </div>
          )}
          <div
            className="channel-inputs"
            style={{ justifyContent: hasWebsiteURL === '1' ? 'space-between' : 'flex-end' }}
          >
            {hasWebsiteURL === '1' && (
              <Input
                name="business_website"
                placeholder="Website URL"
                label={isOnKYCTab ? 'Website URL' : ''}
                defaultValue={businessWebsite}
                type="url"
                className="Input--small website-input"
                onChange={onWebsiteInputChange}
                validator={(value) => (!isUrlLenient(value) ? 'Please enter a valid url' : '')}
                onBlur={(e) => {
                  const error = !isUrlLenient(businessWebsite) ? 'Please enter a valid url' : '';
                  sendErrorMessageToSegment(e, error);
                  trackEvents({
                    objectName: 'Form Details',
                    actionName: 'Filled',
                    screen: 'home page',
                    properties: {
                      'Tab Title': 'Business Overview',
                      'Element Type': 'Form',
                      'Field Type': 'Text',
                      'Field Name': 'Website URL',
                      'Card Title': 'none',
                    },
                  });
                }}
              />
            )}
            {hasAppURL === '1' && !isOnKYCTab && (
              <Input
                name="playstore_url"
                placeholder="App URL"
                defaultValue={playstoreUrl}
                type="url"
                className="Input--small website-input app-input"
                onChange={onWebsiteInputChange}
                onBlur={() => {
                  trackEvents({
                    objectName: 'Form Details',
                    actionName: 'Filled',
                    screen: 'home page',
                    properties: {
                      'Tab Title': 'Business Overview',
                      'Element Type': 'Form',
                      'Field Type': 'Text',
                      'Field Name': 'App URL',
                      'Card Title': 'none',
                      Mandatory: 'Yes',
                    },
                  });
                }}
              />
            )}
          </div>
          {!isOnKYCTab && (
            <div className="Input-content payment-channel-bullet">
              The website should have the following pages/sections:
              <div className="bullet-list-container">
                <ul className="bullet-list bullet-list--left">
                  <li class="shallow"> About Us</li>
                  <li class="shallow"> Contact Us</li>
                  <li class="shallow"> Pricing</li>
                </ul>
                <ul className="bullet-list bullet-list--right">
                  <li>
                    <a
                      href="https://docs.google.com/document/d/1yqqWTE_jfC8F_u9UV9nLq3AUZR2wwpQGJigRJV3YQvg/pub"
                      target="_blank"
                      rel="noreferrer noopener"
                    >
                      Privacy Policy
                    </a>
                  </li>
                  <li>
                    <a
                      href="https://docs.google.com/document/d/1bCwt0WccF7oDMBGAGRxtPgUfzqGzkUjtLnnE1JlL2dg/pub"
                      target="_blank"
                      rel="noreferrer noopener"
                    >
                      Terms & Conditions
                    </a>
                  </li>
                  <li>
                    <a
                      href="https://docs.google.com/document/d/1xYM1QHm9S5phnkzyENqJ3KXv37schlsiTp0Id_4IMwE/pub"
                      target="_blank"
                      rel="noreferrer noopener"
                    >
                      Cancellation/Refund Policy
                    </a>
                  </li>
                </ul>
              </div>
            </div>
          )}
        </>
      )}
    </>
  );
};

export default compose(connect(null, { ...EventActions }))(CustomPaymentsCahnnel);
