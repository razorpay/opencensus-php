import React from 'react';
import { PowerSelect } from 'react-power-select';
import PlaceholderLoader from 'rzp/ui/PlaceholderLoader';

const SelectConfig = ({
  configs,
  isLoading,
  selectedConfig,
  onConfigChange,
  isMobileDevice,
}) => {
  if (isLoading) {
    // this is two show three placeholder entries while loading
    configs = [null, null, null];
    selectedConfig = '';
  }

  return (
    <div
      class={
        `col-lg-4 col-md-4 col-sm-12 col-xs-12` +
        ` report-list-panel 
               report-list-panel${isMobileDevice ? '--mobile' : '--desktop'}`
      }
    >
      <div class="title">SELECT REPORT TYPE</div>
      {isMobileDevice ? (
        isLoading ? (
          <PlaceholderLoader />
        ) : (
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
            selectedOptionComponent={({ option }) => {
              return <div>{option.label}</div>;
            }}
          />
        )
      ) : (
        <div>
          {configs.map((config, index) => (
            <div class="reports-entity-options new" key={index}>
              {isLoading ? (
                <label>
                  <PlaceholderLoader />
                </label>
              ) : (
                <label
                  className={
                    selectedConfig.value === config.value ? 'active' : ''
                  }
                  onClick={() => onConfigChange({ option: config })}
                >
                  {config.label}
                </label>
              )}
            </div>
          ))}
        </div>
      )}
    </div>
  );
};

export default SelectConfig;
