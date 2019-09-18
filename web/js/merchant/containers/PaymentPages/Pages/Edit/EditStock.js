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
            }}
          />
          <Input
            name="stock"
            class="Input"
            placeholder="Total Stock"
            value={this.state.totalStock}
            disabled={this.state.hasNoStockLimit === '1'}
            autoFocus
            onFocus={e => e.target.select()}
            validator={val => {
              if (this.state.hasNoStockLimit === '0') {
                if (!this.state.totalStock && this.state.totalStock != 0) {
                  return 'Please fill out this field';
                } else if (!isInteger(val)) {
                  return 'Stock must be atleast 1';
                }
              }
            }}
            onChange={e => {
              const val = e.target.value;

              this.setState({
                totalStock: val ? Number(e.target.value) | 1 : '',
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
