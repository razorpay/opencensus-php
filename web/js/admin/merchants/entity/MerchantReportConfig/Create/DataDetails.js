import Field, { SwitchField, SelectField, TextAreaField } from 'ui/Field';
import { types, availableDateFormats } from '../data';

export default function DataDetails(props) {
  const { partnerType, reportEmails, configOptions } = props;
  return (
    <div class="row-item">
      <Field name="name" label="Report Name" required />

      <TextAreaField name="description" label="Report Description" required />

      <Field
        name="template.file_meta.filename"
        label="File Download Name"
        placeholder="(Optional)"
        helpMsg="Type as - Yourtext_{Merchant_ID}_{Merchant_name}_{date=d/m/Y H:i:s} - type the date/ time format you want"
      />

      {!configOptions.loading ? (
        <>
          <SelectField
            name="template.file_meta.extension"
            label="File Format"
            defaultValue="csv"
          >
            {configOptions.data.extensions.map(extension => (
              <option value={extension} key={extension}>
                {extension}
              </option>
            ))}
          </SelectField>
          {props.extension === 'txt' &&
            (function() {
              const delimiters = configOptions.data.delimiters.txt;
              return (
                <SelectField
                  label="File Delimiter"
                  name="template.file_meta.delimiter"
                >
                  <option value="">Select...</option>
                  {Object.keys(delimiters).map(delimiter => (
                    <option value={delimiters[delimiter]}>
                      {delimiter}({delimiters[delimiter]})
                    </option>
                  ))}
                </SelectField>
              );
            })()}
        </>
      ) : (
        <FetchingDataForField label="File Format" />
      )}

      <SwitchField
        name="template.file_meta.header"
        label="Headers"
        enabledLabel="Yes"
        disabledLabel="No"
        defaultValue
        onChange={props.onChange}
      />

      {!!partnerType && (
        <SwitchField
          name="template.referred_accounts"
          label="Show aggregate data in report"
          enabledLabel="Yes"
          disabledLabel="No"
          enabledValue="all"
          disabledValue={0}
          onChange={props.onChange}
        />
      )}

      <SwitchField
        name="template.attach_to_email"
        label="Attach To Email"
        enabledLabel="Yes"
        disabledLabel="No"
        onChange={props.onChange}
      />

      <SelectField
        name="template.formats.date"
        label="Date format in report"
        helpMsg="This will be applied across all date fields in the report"
        defaultValue="d-m-Y"
      >
        {availableDateFormats.map(({ value, label }) => (
          <option value={value} key={value}>
            {label}
          </option>
        ))}
      </SelectField>

      <TextAreaField
        name="emails"
        label="Email ID(s)"
        placeholder="Write comma separated values"
        helpMsg="This won't create the schedule, to create schedules please contact developers"
      />

      <SelectField
        name="type"
        label={'Report Type'}
        onChange={props.onReportTypeChange}
      >
        <option value="">Select...</option>
        {types.map(({ label, value }) => (
          <option value={value} key={value}>
            {label}
          </option>
        ))}
      </SelectField>
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
