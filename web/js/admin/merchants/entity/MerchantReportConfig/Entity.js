import Table from 'ui/Table';
import EntityRow from 'ui/EntityRow';

import { types } from './data';

export default function ViewMerchantConfig(props) {
  const config = props.config;

  const fields = [
    ['Column Name', outputField => outputField],
    [
      'Field And Column',
      outputField => config.template.fields_map[outputField],
    ],
    [
      'Filters',
      outputField => {
        const [field, column] = config.template.fields_map[
          outputField
        ][0].split('.');
        const filters = config.template.filters || {};
        return (
          !!filters[field] &&
          filters[field][column] && (
            <em>{config.template.filters[field][column].op}</em>
          )
        );
      },
    ],
  ];

  return (
    <div>
      <div className="box">
        <div className="heading">
          <strong>Config Id:</strong> {config.id}
        </div>
        <EntityRow label="Report Name" value={config.name} />
        <EntityRow label="Report Description" value={config.description} />
        <EntityRow
          label="Report Type"
          value={(types.find(({ value }) => value === config.type) || {}).label}
        />
        <EntityRow label="Emails" value={(config.emails || []).join(',')} />
        <EntityRow
          label="Name of downloaded file"
          value={(config.template.file_meta || {}).filename}
        />
        <EntityRow
          label="Date Format"
          value={(config.template.formats || {}).date}
        />
        <EntityRow
          label="Extension of File"
          value={(config.template.file_meta || {}).extension}
        />
      </div>

      <Table items={config.template.output_fields} fields={fields} />
    </div>
  );
}
