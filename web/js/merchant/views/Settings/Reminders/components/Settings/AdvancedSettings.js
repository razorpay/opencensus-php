import Input from 'common/new-ui/Input';

import ContentToggler from 'common/ui/Toggler/ContentToggler';

import EntityDetailRow from 'merchant/components/EntityDetailRow';

const isReminderChannelDisabled = ({ user, channel }) => {
  if (channel === 'sms') {
    return user.isPlV2DisableAllSmsEnabled || user.isPlV2DisableReminderSmsEnabled;
  } else if (channel === 'email') {
    return user.isPlV2DisableAllEmailEnabled || user.isPlV2DisableReminderEmailEnabled;
  }

  return false;
};

export default ({ channels, onChannelChange, user }) => (
  <div className="setting">
    <ContentToggler show>
      Advanced settings
      <div>
        <EntityDetailRow label="Channels">
          <Input.Group required className="InputGroup--inline InputGroup--near">
            <div className="Input-content">
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
            {Object.keys(channels)
              .map((channelName) => {
                return {
                  label: channelName === 'email' ? 'Email' : channelName.toUpperCase(),
                  channelName,
                };
              })
              .map(
                (channel) =>
                  channels[channel.channelName] &&
                  isReminderChannelDisabled({ user, channel: channel.channelName }) && (
                    <div className="m" key={channel.channelName}>
                      <small style={{ color: 'red' }}>
                        {channel.label} disabled. Contact support to enable.
                      </small>
                    </div>
                  ),
              )}

            <div className="m-t">
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
