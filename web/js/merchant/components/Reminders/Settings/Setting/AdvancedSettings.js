import Input from 'component/Input';

import { SelectField } from 'ui/Field';

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
                defaultValue="0"
                checked={channels.sms}
                onChange={onChannelChange}
              />

              <Input.Check
                name="email"
                defaultValue="0"
                fieldLabel="Email"
                checked={channels.email}
                onChange={onChannelChange}
              />
            </div>
          </Input.Group>
        </EntityDetailRow>
      </div>
    </ContentToggler>
  </div>
);
