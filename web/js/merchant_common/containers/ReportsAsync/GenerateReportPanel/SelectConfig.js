// this is supposed to be a searchable dropdown in future
import Input from 'common/new-ui/Input';

export default function SelectConfig({ configs }) {
  return (
    <Input.Select
      name="selectedConfigId"
      size="half_big"
      placeholder="Select Config"
      options={[
        { label: 'Select Config', name: '' },
        ...getConfigOptions(configs),
      ]}
      label="Select Report Type"
      class="Input--vTop"
    />
  );
}

function getConfigOptions(configs = []) {
  return configs.map(config => ({
    label: config.name,
    name: config.id,
  }));
}
