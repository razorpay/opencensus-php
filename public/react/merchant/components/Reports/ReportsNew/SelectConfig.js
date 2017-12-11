import React from 'react';
import { PowerSelect } from 'react-power-select';

const SelectConfig = ({
  configs,
  selectedConfig,
  onConfigChange,
  isMobileDevice,
}) => (
  <div
    class={
      `col-lg-4 col-md-4 col-sm-12 col-xs-12` +
      ` report-list-panel 
             report-list-panel${isMobileDevice ? '--mobile' : '--desktop'}`
    }
  >
    <div class="title">SELECT REPORT TYPE</div>
    {isMobileDevice ? (
      <PowerSelect
        options={configs}
        searchEnabled={false}
        selected={selectedConfig}
        showClear={false}
        onChange={onConfigChange}
        optionComponent={({ option }) => (
          <div class="reports-entity-options">
            <label>{option.label}</label>
          </div>
        )}
        selectedOptionComponent={({ option }) => <div>{option.label}</div>}
      />
    ) : (
      <div>
        {configs.map((config, index) => (
          <div class="reports-entity-options" key={index}>
            <label
              className={selectedConfig.value === config.value ? 'active' : ''}
              onClick={() => onConfigChange({ option: config })}
            >
              {config.label}
            </label>
          </div>
        ))}
      </div>
    )}
  </div>
);

export default SelectConfig;
