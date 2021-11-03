import Form from 'common/new-ui/Form';
import Input, { Label } from 'common/new-ui/Input';
import Button from 'common/new-ui/Button';
import { useState } from 'react';
// import { getCustomURL } from 'merchant/components/DocsLink';

const SupporterBasedModalContent = ({
  handleClose,
  endDate,
  onEndDateChange,
  meta_data,
  onMetaDataChange,
  handleSubmit,
}) => {
  const [disableSubmit, setDisableSubmit] = useState(false);

  const handleFormChange = () => {
    setTimeout(() => {
      const form = document.getElementsByClassName('goal-tracker-form--supporter')[0];
      const _disableSubmit = form.querySelectorAll('.is-invalid').length;

      setDisableSubmit(_disableSubmit);
    });
  };

  const validateAvailableUnits = (val) => {
    if (meta_data.display_available_units === '1') {
      if (!val) {
        return 'Please add your stock/unit limit';
      }
      if (val && Number(val) % 1 !== 0) {
        return 'Stocks limit cannot contain decimals';
      }
    }
    return '';
  };

  return (
    <>
      <div class="main-title">Configure Your Goal Tracker</div>
      <Form class="goal-tracker-form--supporter" onChange={handleFormChange}>
        <div className="modal-section">
          <Label text="Goal tracking options" />
          {meta_data.display_sold_units === '0' &&
            meta_data.display_supporter_count === '0' &&
            meta_data.display_days_left === '0' && (
              <div className="is-mature is-invalid support-common-error">
                <div className="Input-error">Please select at least one goal tracking option</div>
              </div>
            )}
          <Input.Check
            autoRender
            fieldLabel="Display stock/unit sales"
            onChange={(e) => {
              // update display_sold_units, reset display_available_units to false(if going from ON -> OFF)
              onMetaDataChange('display_sold_units', e.target.value, e.target.value === '0');
              if (e.target.value === '0') {
                onMetaDataChange('display_available_units', '0');
              }
            }}
            checked={meta_data.display_sold_units === '1'}
            defaultChecked={meta_data.display_sold_units === '1'}
          />

          <div class="Input--custom">
            <Input.Group>
              <div class="Input-content">
                <Input.Check
                  autoRender
                  fieldLabel="I have limited stocks/units available"
                  onChange={(e) => {
                    // update display_available_units, enable display_sold_units if going from OFF -> ON
                    onMetaDataChange('display_available_units', e.target.value);
                    if (e.target.value === '1') {
                      onMetaDataChange('display_sold_units', '1');
                    }
                  }}
                  checked={meta_data.display_available_units === '1'}
                  defaultChecked={meta_data.display_available_units === '1'}
                />
                <Input
                  autoRender
                  type="number"
                  placeholder="Add your stock/unit limit"
                  onChange={(e) => {
                    onMetaDataChange('available_units', e.target.value);
                  }}
                  value={meta_data.available_units}
                  defaultValue={meta_data.available_units}
                  class="Input--stock-limit"
                  // disabled={meta_data.display_available_units !== '1'}
                  validator={validateAvailableUnits}
                />
              </div>
            </Input.Group>
          </div>
        </div>

        <div className="modal-section">
          <Input.Check
            fieldLabel="Display supporter count for this goal"
            onChange={(e) => {
              onMetaDataChange('display_supporter_count', e.target.value);
            }}
            checked={meta_data.display_supporter_count === '1'}
            defaultChecked={meta_data.display_supporter_count === '1'}
          />
        </div>

        <div className="modal-section">
          <Label text="End date for your goal" />
          <Input.Check
            fieldLabel="Goal has a fixed end date"
            onChange={(e) => {
              onMetaDataChange('display_days_left', e.target.value);
            }}
            checked={meta_data.display_days_left === '1'}
            defaultChecked={meta_data.display_days_left === '1'}
          />
          <Input.DateTime
            value={endDate}
            defaultValue={endDate}
            required={true}
            onChange={onEndDateChange}
            className="end-date-picker"
          />
        </div>
        {/* TODO: Add real docs link */}
        {/* <div className="modal-section">
          <Description
            text={
              <>
                Learn more about the benefits of adding a goal tracker and its different
                configurations on our{' '}
                <a href={getCustomURL(url)} target="_blank">
                  documentation page <i className="i i-external-link ml-5" />
                </a>
              </>
            }
          />
        </div> */}
      </Form>
      <footer>
        <Button.Transparent type="button" onClick={handleClose}>
          Cancel
        </Button.Transparent>
        <Button.Primary onClick={handleSubmit} type="button" disabled={disableSubmit}>
          Save
        </Button.Primary>
      </footer>
    </>
  );
};

export default SupporterBasedModalContent;
