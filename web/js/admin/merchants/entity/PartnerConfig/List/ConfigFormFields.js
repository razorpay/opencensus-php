import { SelectField } from 'ui/Field';
import EntityRow from 'ui/EntityRow';
import { isPresent } from 'rzp/utils/rzp-utils';

export function SelectApplication(props) {
  const { applications } = props;
  return applications.loading ? (
    <EntityRow label={<em>Fetching Applications...</em>} value={' '} />
  ) : (
    <SelectField label="Select Application" onChange={props.onAppChange}>
      <option value="">Not Selected</option>
      {applications.data.map(app => (
        <option key={app.id} value={app.id}>
          {app.name} - {app.id}
        </option>
      ))}
    </SelectField>
  );
}

export function WriteConfigButton(props) {
  return (
    <div className="field">
      <button
        class="btn"
        type="button"
        style={{ marginBottom: '0' }}
        onClick={props.onWriteConfig({
          config: props.defaultConfigs[0],
        })}
      >
        {isPresent(props.defaultConfigs) ? 'Update' : 'Create'} Default Config
      </button>
    </div>
  );
}
