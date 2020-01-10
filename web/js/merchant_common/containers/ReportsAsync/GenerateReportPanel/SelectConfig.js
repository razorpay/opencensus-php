import { PowerSelect } from 'react-power-select';

import { Label } from 'common/new-ui/Input';
import { pickProps } from 'common/utils/rzp-utils';
import Banner from 'common/ui/Banner';

export default function SelectConfig({ configs, selectedConfig, ...props }) {
  function onChange({ option }) {
    if (option) {
      const { id: selectedId } = option;
      const newSelectedConfig = configs.find(({ id }) => id === selectedId);
      props.onConfigChange(newSelectedConfig);
    } else {
      props.onConfigChange(null);
    }
  }

  return (
    <div className="SelectConfig">
      <Banner>
        <div className="Input Input--large">
          <Label text="Select Report Type" />
          <div className="Input-content m-t">
            <div className="Input-elWrapper">
              <PowerSelect
                options={getConfigOptions(configs)}
                placeholder="Select Config"
                onChange={onChange}
                selected={selectedConfig}
                optionComponent={ConfigOption}
                selectedOptionLabelPath="name"
                selectedOptionComponent={ConfigSelected}
                searchEnabled
                searchIndices={['label', 'name']}
                searchPlaceholder="Search..."
                showClear
                class="ps-in-modal"
              />
            </div>
          </div>
        </div>
        <div>
          {(selectedConfig || {}).description && (
            <span className="text-muted text-small">
              {selectedConfig.description}
            </span>
          )}
        </div>
      </Banner>
    </div>
  );
}

function getConfigOptions(configs = []) {
  return configs.map(config =>
    pickProps(config, ['id', 'name', 'description'])
  );
}

function ConfigOption({ option }) {
  const { description, name } = option;
  return (
    <div class="custom-powerselect-options m-b">
      <p>
        <strong>{name}</strong>
      </p>
      <p>
        {description ? (
          description.substr(0, 50)
        ) : (
          <span className="text-muted">
            <em>No Description</em>
          </span>
        )}
      </p>
    </div>
  );
}

function ConfigSelected({ option }) {
  return <span>{option.name}</span>;
}
