import Input from 'component/Input';
import Button, { AsyncBtn } from 'component/Button';
import { isInteger } from 'rzp/utils/validators';

export default class EditStock extends React.Component {
  state = this.resetState(this.props);

  resetState(props) {
    props = props || this.props;

    return {
      isEditableMode: false,
      totalStock: props.totalStock || '',
      hasNoStockLimit: props.totalStock ? '0' : '1',
    };
  }

  componentWillReceiveProps(nextProps) {
    if (nextProps.totalStock !== this.state.totalStock) {
      this.setState(this.resetState(nextProps));
    }
  }

  makeEditable = () => {
    this.setState({
      isEditableMode: true,
    });

    this.props.trackerFn('Edit Stock');
  };

  setRef = el => (this.stockEl = el);

  render() {
    const { isRoleAllowedEdit, quantitySold, paymentPageItemId } = this.props;

    let content = (
      <React.Fragment>
        {quantitySold}
        <span style={{ opacity: 0.7 }}>
          {this.state.totalStock && ' of ' + this.state.totalStock}
        </span>
        {isRoleAllowedEdit && (
          <Button.Transparent
            onClick={this.makeEditable}
            class="Button--Link pull-right"
          >
            Update Stock
          </Button.Transparent>
        )}
      </React.Fragment>
    );

    if (this.state.isEditableMode) {
      content = (
        <div class="InputGroup Input" style={{ maxWidth: 260 }}>
          <Input.Check
            fieldLabel="No Limit"
            name="hasNoStockLimit"
            defaultValue={this.state.hasNoStockLimit}
            value={this.state.hasNoStockLimit}
            onChange={e => {
              this.setState({
                hasNoStockLimit: e.target.value,
              });

              if (e.target.value == '0') {
                setTimeout(() => {
                  this.stockEl && this.stockEl.focus();
                  this.stockEl.el.select();
                }, 10);
              }
            }}
          />
          <Input
            name="stock"
            ref={this.setRef}
            class="Input"
            placeholder="Total Stock"
            value={this.state.totalStock}
            disabled={this.state.hasNoStockLimit === '1'}
            validator={val => {
              if (this.state.hasNoStockLimit === '0') {
                if (!this.state.totalStock) {
                  return 'Please fill out this field';
                } else if (!isInteger(val)) {
                  return 'Enter valid number';
                }
              }
            }}
            onChange={e => {
              this.setState({
                totalStock: e.target.value,
              });
            }}
          />
          <div
            style={{
              textAlign: 'right',
              marginBottom: 12,
            }}
          >
            <Button.Transparent
              class="Button--Link"
              onClick={() => {
                this.setState(this.resetState());
              }}
            >
              Cancel
            </Button.Transparent>

            <AsyncBtn.Primary
              class="Button--small"
              style={{ marginRight: 0, marginLeft: 16 }}
              disabled={
                this.state.hasNoStockLimit === '0' && !this.state.totalStock
              }
              onClick={() => {
                return this.props
                  .editFn(
                    {
                      stock:
                        this.state.hasNoStockLimit == '1'
                          ? null
                          : Number(this.state.totalStock),
                    },
                    paymentPageItemId
                  )
                  .then(resp => {
                    if (resp && resp.data) {
                      this.setState(this.resetState());
                      this.props.trackerFn('Edit Stock (Saved)');
                    }
                  });
              }}
              showLoader={false}
              pendingState="Saving..."
            >
              Save
            </AsyncBtn.Primary>
          </div>
        </div>
      );
    }

    return content;
  }
}
