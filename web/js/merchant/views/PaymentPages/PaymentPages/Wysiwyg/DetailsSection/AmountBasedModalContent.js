import Form from 'common/new-ui/Form';
import Input, { Label } from 'common/new-ui/Input';
import Button from 'common/new-ui/Button';
import { useState } from 'react';
// import { getCustomURL } from 'merchant/components/DocsLink';

const AmountBasedModalContent = ({
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
      const form = document.getElementsByClassName('goal-tracker-form--amount')[0];
      const _disableSubmit = form.querySelectorAll('.is-invalid').length;

      setDisableSubmit(_disableSubmit);
    });
  };

  const handleMetaDataChange = (key, e) => {
    onMetaDataChange(key, e.target.value);
  };

  return (
    <>
      <div class="main-title">Configure Your Goal Tracker</div>
      <Form class="goal-tracker-form--amount" onChange={handleFormChange}>
        <div className="modal-section">
          <Input.Group
            class="InputGroup--inline InputGroup--vTop amount"
            label="Enter your goal amount"
          >
            <div class="Input-content">
              <Input.CurrencySelect autoRender name="currency" defaultValue="INR" />
              <Input
                autoRender
                placeholder="0.00"
                type="number"
                step="1"
                defaultValue={meta_data.goal_amount}
                onChange={handleMetaDataChange.bind(null, 'goal_amount')}
                validator={validateAmount}
              />
            </div>
          </Input.Group>

          <Input.Check
            fieldLabel="Display supporter count for this goal"
            onChange={handleMetaDataChange.bind(null, 'display_supporter_count')}
            checked={meta_data.display_supporter_count === '1'}
            defaultChecked={meta_data.display_supporter_count === '1'}
            className="supporter-checkbox"
          />
        </div>

        <div className="modal-section">
          <Label text="End date for your goal" />
          <Input.Check
            fieldLabel="Goal has a fixed end date"
            onChange={handleMetaDataChange.bind(null, 'display_days_left')}
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
          />{' '}
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

export default AmountBasedModalContent;

function validateAmount(val) {
  if (!val) {
    return 'Please add goal amount';
  }
  if (val && val.indexOf('.') > -1) {
    return 'Goal amount cannot contain decimals';
  }
  return '';
}
