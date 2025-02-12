import Input from 'common/new-ui/Input';
import Popover, { PopoverBody, PopoverTitle } from 'common/ui/Popover';

export default class FixedAmountWithQuantity extends React.Component {
  render() {
    const { children, field } = this.props;

    return (
      <React.Fragment>
        {children}

        {/* Add dummy Counter */}
        <div className="Input--counterTooltip">
          <div className="Input-content Input--disabled">
            <button type="button" disabled>
              -
            </button>
            <input value={field.min_purchase} disabled />
            <button type="button" disabled>
              +
            </button>
          </div>

          <Popover
            align="top"
            theme="dark"
            parentQuerySelector=".Modal-content.Modal-content--PaymentButton-CreateForm"
          >
            <PopoverBody>
              Customers can change Item quantity
              <br />
              {/* TODO: As per the actual limits */}
              (Min: {field.min_purchase}, Max:{' '}
              {field.max_purchase || 'No Limit'})
            </PopoverBody>
          </Popover>
        </div>
      </React.Fragment>
    );
  }
}
