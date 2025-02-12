import Input from 'common/new-ui/Input';
import Popover, { PopoverBody, PopoverTitle } from 'common/ui/Popover';

export default class FixedAmount extends React.Component {
  render() {
    const { children, isMandatory } = this.props;

    return (
      <React.Fragment>
        {children}

        {/* Dummy Checkbox for optional field */}
        {!isMandatory && (
          <div className="Input--checkboxTooltip">
            <Input.Check disabled />

            <Popover
              align="top"
              theme="dark"
              parentQuerySelector=".Modal-content--PaymentButton-CreateForm"
            >
              <PopoverBody>
                Customers can select or unselect this Item
              </PopoverBody>
            </Popover>
          </div>
        )}
      </React.Fragment>
    );
  }
}
