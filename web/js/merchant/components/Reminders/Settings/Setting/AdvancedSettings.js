import Input from 'component/Input';

import ContentToggler from 'rzp/ui/Toggler/ContentToggler';

import EntityDetailRow from 'merchant/components/EntityDetailRow';

export default ({ channels, onChannelChange }) => (
  <div class="setting">
    <ContentToggler>
      Advanced settings
      <div>
        <EntityDetailRow label="Channels">
          <Input.Group class="InputGroup--inline InputGroup--near">
            <div class="Input-content">
              <Input.Check
                name="sms"
                fieldLabel="SMS"
                defaultValue={channels.sms}
                onChange={onChannelChange('sms')}
              />

              <Input.Check
                name="email"
                defaultValue="0"
                fieldLabel="Email"
                defaultValue={channels.email}
                onChange={onChannelChange('email')}
              />
            </div>

            <div class="m-t">
              <small>
                Customers will receive email or SMS only if the details are
                mentioned during the creation of a payment link
              </small>
            </div>
          </Input.Group>
        </EntityDetailRow>
      </div>
    </ContentToggler>
  </div>
);
