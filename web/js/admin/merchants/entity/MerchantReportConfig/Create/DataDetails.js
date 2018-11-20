import { SwitchField, SelectField, TextAreaField } from 'ui/Field';

export default function DataDetails(props) {
  const { partnerType, reportEmails } = props;
  return (
    <div>
      {!!partnerType && (
        <SwitchField
          name="show_aggregate_data"
          label="Show aggregate data in report"
          enabledLabel="Yes"
          disabledLabel="No"
        />
      )}

      <SelectField
        name="date_format"
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
