import { snakeToTitleCase } from 'common/util';

import Field, { TextAreaField, SelectField, SwitchField } from 'ui/Field';

const CONFIG_TYPE_LABEL = 'Report to customized';
const FILE_FORMAT_LABEL = 'File Format';

export default function ConfigDetails(props) {
  const { configTypes, configOptions } = props;
  return (
    <div>
      {!configTypes.loading ? (
        <SelectField name="report_type" label={CONFIG_TYPE_LABEL}>
          <option value="">Select...</option>
          {configTypes.data.map(type => (
            <option value={type} key={type}>
              {snakeToTitleCase(type)}
            </option>
          ))}
        </SelectField>
      ) : (
        <FetchingDataForField label={CONFIG_TYPE_LABEL} />
      )}

      <Field name="report_name" label="Report Name" />

      <TextAreaField name="description" label="Report Description" />

      <Field
        name="file_download_name"
        label="File Download Name"
        helpMsg="Type as - Yourtext_{Merchant_ID}_{Merchant_name}_{Report_type}_{DD/MM/YY}_{HH:MM} - type the date/ time format you want"
      />

      {!configOptions.loading ? (
        <SelectField name="file_format" label={FILE_FORMAT_LABEL}>
          <option value="">Select...</option>
          {configOptions.data.extensions.map(extension => (
            <option value={extension} key={extension}>
              {extension}
            </option>
          ))}
        </SelectField>
      ) : (
        <FetchingDataForField label={FILE_FORMAT_LABEL} />
      )}

      <SwitchField
        name="header"
        label="Headers"
        enabledLabel="Yes"
        disabledLabel="No"
      />
    </div>
  );
}

function FetchingDataForField({ label }) {
  return (
    <div class="field">
      <label>{label}</label>
      <span>Loading...</span>
    </div>
  );
}
