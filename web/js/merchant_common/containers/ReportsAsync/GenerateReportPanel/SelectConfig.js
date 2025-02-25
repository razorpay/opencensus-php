import { PowerSelect } from 'react-power-select';
import React from 'react';

import { Label } from 'common/new-ui/Input';
import { pickProps } from 'common/utils/rzp-utils';
import Banner from 'common/ui/Banner';
import { DocLink } from 'merchant/components/DocsLink';

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
        <div className="Input Input--large invoice-select-container">
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
                className="ps-in-modal"
                showClear={false}
              />
            </div>
          </div>
          {selectedConfig &&
            (selectedConfig.id === 'invoice' ||
              selectedConfig.name === 'Monthly Invoice Report') && (
              <span className="faq-link">
                The December invoice is for the billing cycle starting on Dec 01, 2020 to Dec 30,
                2020 and is generated on Dec-31, 2020 due to new GST guidelines effective from 1st
                Jan 2020. The charges for Dec 31, 2020 will be added to the next billing cycle.{' '}
                <DocLink
                  href="https://razorpay.com/docs/announcements/gst-changes/"
                  target="_blank"
                  rel="noopener noreferrer"
                >
                  Learn more.
                </DocLink>
              </span>
            )}
        </div>
        <div>
          {(selectedConfig || {}).description && (
            <span className="text-muted text-small">{selectedConfig.description}</span>
          )}
        </div>
      </Banner>
    </div>
  );
}

function getConfigOptions(configs = []) {
  return configs.map((config) => pickProps(config, ['id', 'name', 'description']));
}

function ConfigOption({ option }) {
  const { description, name } = option;
  return (
    <div className="SelectConfig__Dropdown_Option custom-powerselect-options m-b">
      <p>
        <strong>{name}</strong>
      </p>
      <p>
        {description ? (
          getShortDescription(description)
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

function getShortDescription(description) {
  const indexOfFullStop = description.indexOf('.');
  return description.substring(0, indexOfFullStop > 0 ? indexOfFullStop : description.length);
}
