// this is supposed to be a searchable dropdown in future
import Input from 'common/new-ui/Input';

export default function SelectConfig({ configs, ...props }) {
  function onChange({ target }) {
    const { value } = target;
    if (!value) {
      props.onConfigChange(null);
    } else {
      const selectedConfig = configs.find(({ id }) => id === value);
      props.onConfigChange(selectedConfig);
    }
  }

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
      onChange={onChange}
    />
  );
}

function getConfigOptions(configs = []) {
  return configs.map(config => ({
    label: config.name,
    name: config.id,
  }));
}
