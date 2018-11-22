import Field, { SwitchField, SelectField, TextAreaField } from 'ui/Field';

export default function DataDetails(props) {
  const { partnerType, reportEmails, configOptions } = props;
  return (
    <div>
      <Field name="name" label="Report Name" />

      <TextAreaField name="description" label="Report Description" />

      <Field
        name="template.file_meta.filename"
        label="File Download Name"
        helpMsg="Type as - Yourtext_{Merchant_ID}_{Merchant_name}_{Report_type}_{DD/MM/YY}_{HH:MM} - type the date/ time format you want"
      />

      {!configOptions.loading ? (
        <SelectField name="template.file_meta.extension" label="File Format">
          <option value="">Select...</option>
          {configOptions.data.extensions.map(extension => (
            <option value={extension} key={extension}>
              {extension}
            </option>
          ))}
        </SelectField>
      ) : (
        <FetchingDataForField label="File Format" />
      )}

      <SwitchField
        name="template.file_meta.header"
        label="Headers"
        enabledLabel="Yes"
        disabledLabel="No"
        onChange={props.onChange}
      />

      {!!partnerType && (
        <SwitchField
          name="show_aggregate_data"
          label="Show aggregate data in report"
          enabledLabel="Yes"
          disabledLabel="No"
          onChange={props.onChange}
        />
      )}

      <SelectField
        name="formats.date"
        label="Date format in report"
        helpMsg="This will be applied across all date fields in the report"
      >
        {availableDateFormats.map(dateFormat => (
          <option value={dateFormat} key={dateFormat}>
            {dateFormat}
          </option>
        ))}
      </SelectField>

      <TextAreaField
        name="emails"
        label="Email ID(s)"
        helpMsg="Write comma separated values"
        defaultValue={reportEmails.join(',')}
      />
    </div>
  );
}

const availableDateFormats = [
  'DDMMYY',
  'DDMMYYYY',
  'YYMMDD',
  'YYYYMMDD',
  'DD-MM-YY',
  'DD-MM-YYYY',
  'YY-MM-DD',
  'YYYY-MM-DD',
  'DD/MM/YY',
  'DD/MM/YYYY',
  'YY/MM/DD',
  'YYYY/MM/DD',
  'DD.MM.YY',
  'DD.MM.YYYY',
  'YY.MM.DD',
  'YYYY.MM.DD',
  'DD-Mon-YY',
  'DD-Mon-YYYY',
  'YY-Mon-DD',
  'YYYY-Mon-DD',
];

function FetchingDataForField({ label }) {
  return (
    <div class="field">
      <label>{label}</label>
      <span>Loading...</span>
    </div>
  );
}
