import React from 'react';
import Input from 'common/new-ui/Input';
import { isUrlLenient } from 'common/utils/validators';

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
}) => {
  return (
    <>
      <div class="Input-label">Payment Channels</div>
      <div className="Input-content channel_checkbox">
        <div className="info-text">
          This allows us to recommend a suitable product for your business
        </div>
        <Input.Check
          name="physical_store"
          fieldLabel="Store/In-person"
          onChange={onChange}
          autoRender={true}
          className="Input-channels"
          extraClassName="contents"
          defaultValue={physicalStore}
        />
        <Input.Check
          name="social_media"
          fieldLabel="Social Media (e.g. WhatsApp)"
          onChange={onChange}
          autoRender={true}
          className="Input-channels"
          extraClassName="contents"
          defaultValue={socialMedia}
        />
        <Input.Check
          name="has_url"
          fieldLabel="Live Website/App"
          onChange={onChange}
          autoRender={true}
          className="Input-channels"
          extraClassName="contents"
          defaultValue={hasWebsiteAppURL}
        />
      </div>
      {hasWebsiteAppURL === '1' && (
        <>
          <div className="Input-content channel_checkbox website-app">
            <Input.Check
              name="app_website_url"
              value="1"
              fieldLabel="Accept payments on website"
              onChange={onChange}
              autoRender={true}
              className="Input-channels"
              extraClassName="contents"
              defaultValue={hasWebsiteURL}
            />
            <Input.Check
              name="app_url"
              fieldLabel="Accept payments on app"
              onChange={onChange}
              autoRender={true}
              className="Input-channels"
              extraClassName="contents"
              defaultValue={hasAppURL}
            />
          </div>
          <div
            className="channel-inputs"
            style={{ justifyContent: hasWebsiteURL === '1' ? 'space-between' : 'flex-end' }}
          >
            {hasWebsiteURL === '1' && (
              <Input
                name="business_website"
                placeholder="Website URL"
                defaultValue={businessWebsite}
                type="url"
                className="Input--small website-input"
                onChange={onWebsiteInputChange}
                validator={(value) => (!isUrlLenient(value) ? 'Please enter a valid url' : '')}
                onBlur={(e) => {
                  const error = !isUrlLenient(businessWebsite) ? 'Please enter a valid url' : '';
                  sendErrorMessageToSegment(e, error);
                }}
              />
            )}
            {hasAppURL === '1' && (
              <Input
                name="playstore_url"
                placeholder="App URL"
                defaultValue={playstoreUrl}
                type="url"
                className="Input--small website-input app-input"
                onChange={onWebsiteInputChange}
              />
            )}
          </div>
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
                    rel="noreferrer"
                  >
                    Privacy Policy
                  </a>
                </li>
                <li>
                  <a
                    href="https://docs.google.com/document/d/1bCwt0WccF7oDMBGAGRxtPgUfzqGzkUjtLnnE1JlL2dg/pub"
                    target="_blank"
                    rel="noreferrer"
                  >
                    Terms & Conditions
                  </a>
                </li>
                <li>
                  <a
                    href="https://docs.google.com/document/d/1xYM1QHm9S5phnkzyENqJ3KXv37schlsiTp0Id_4IMwE/pub"
                    target="_blank"
                    rel="noreferrer"
                  >
                    Cancellation/Refund Policy
                  </a>
                </li>
              </ul>
            </div>
          </div>
        </>
      )}
    </>
  );
};

export default CustomPaymentsCahnnel;
