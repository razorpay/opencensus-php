import Input from 'common/new-ui/Input';

import ContentToggler from 'common/ui/Toggler/ContentToggler';

import EntityDetailRow from 'merchant/components/EntityDetailRow';

export default ({ channels, onChannelChange }) => (
  <div class="setting">
    <ContentToggler show>
      Advanced settings
      <div>
        <EntityDetailRow label="Channels">
          <Input.Group required class="InputGroup--inline InputGroup--near">
            <div class="Input-content">
              {Object.keys(channels).map((channelName) => (
                <Input.Check
                  key={channelName}
                  name={channelName}
                  fieldLabel={channelName === 'email' ? 'Email' : channelName.toUpperCase()}
                  checked={channels[channelName]}
                  onChange={onChannelChange(channelName)}
                />
              ))}
            </div>

            <div class="m-t">
              <small>
                Customers will receive email or SMS only if the details are mentioned during the
                creation of a payment link
              </small>
            </div>
          </Input.Group>
        </EntityDetailRow>
      </div>
    </ContentToggler>
  </div>
);
