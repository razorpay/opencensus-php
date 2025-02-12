/* eslint-disable react/no-unsafe */
import React from 'react';
import Input from 'common/new-ui/Input';
import Button, { AsyncBtn } from 'common/new-ui/Button';
import { getUnitsDescription } from 'merchant/views/PaymentPages/PaymentPages/utils';
import { PRODUCT_STATUS } from 'merchant/views/PaymentPages/common/Products/utils';

export default class EditStock extends React.Component {
  state = this.resetState(this.props);

  resetState(props) {
    props = props || this.props;

    return {
      isEditableMode: false,
      totalStock: !props.isStorefrontPage
        ? props.totalStock || ''
        : props.storefrontCatalogStatus !== 'unlimited'
        ? props.totalStock
        : '',
      // if stock is falsy, then its unlimited quantity
      hasNoStockLimit: !props.isStorefrontPage
        ? props.totalStock
          ? '0'
          : '1'
        : props.storefrontCatalogStatus === 'unlimited'
        ? '1'
        : '0',
    };
  }

  UNSAFE_componentWillReceiveProps(nextProps) {
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

  setRef = (el) => (this.stockEl = el);

  render() {
    const {
      isRoleAllowedEdit,
      quantitySold,
      paymentPageItemId,
      isStorefrontPage,
      storefrontCatalogStatus,
    } = this.props;

    let content = (
      <>
        {!isStorefrontPage ? (
          <>
            {quantitySold}
            <span style={{ opacity: 0.7 }}>
              {this.state.totalStock && ` of ${this.state.totalStock}`}
            </span>
          </>
        ) : (
          getUnitsDescription({
            units: this.props.totalStock,
            quantitySold,
            status: storefrontCatalogStatus,
          })
        )}
        {isRoleAllowedEdit && (
          <Button.Transparent onClick={this.makeEditable} className="Button--Link pull-right">
            Update Stock
          </Button.Transparent>
        )}
      </>
    );

    if (this.state.isEditableMode) {
      content = (
        <div className="InputGroup Input" style={{ maxWidth: 260 }}>
          <Input.Check
            fieldLabel="No Limit"
            name="hasNoStockLimit"
            defaultValue={this.state.hasNoStockLimit}
            value={this.state.hasNoStockLimit}
            onChange={(e) => {
              const val = e.target.value;

              this.setState({
                hasNoStockLimit: val,
              });

              if (val == '0') {
                setTimeout(() => {
                  if (this.stockEl) {
                    this.stockEl.focus();
                    this.stockEl.el.select();
                  }
                }, 10);
              }
            }}
          />
          <Input
            name="stock"
            ref={this.setRef}
            className="Input"
            placeholder="Total Stock"
            value={this.state.totalStock}
            disabled={this.state.hasNoStockLimit === '1'}
            validator={(val) => {
              // if PP, retain previous validation
              if (!isStorefrontPage) {
                if (this.state.hasNoStockLimit === '0') {
                  if (!this.state.totalStock && this.state.totalStock != 0) {
                    return 'Please fill out this field';
                  } else if (val < 1) {
                    return 'Stock must be at least 1';
                  }
                }
              } else if (this.state.hasNoStockLimit === '0' && !val) {
                // if not unlimited stock & value is empty
                return 'Please fill out this field';
              }
              return '';
            }}
            onChange={(e) => {
              const val = e.target.value;

              // Allow only numbers and empty value
              if (!val || !isNaN(val)) {
                this.setState({
                  totalStock: val ? Number(val) : '',
                });
              }
            }}
          />
          <div
            style={{
              textAlign: 'right',
              marginBottom: 12,
            }}
          >
            <Button.Transparent
              className="Button--Link"
              onClick={() => {
                this.setState(this.resetState());
              }}
            >
              Cancel
            </Button.Transparent>

            <AsyncBtn.Primary
              className="Button--small"
              style={{ marginRight: 0, marginLeft: 16 }}
              disabled={
                !isStorefrontPage
                  ? this.state.hasNoStockLimit === '0' && !this.state.totalStock
                  : this.state.hasNoStockLimit === '0' && this.state.totalStock === ''
              }
              onClick={() => {
                const request = {};
                if (!isStorefrontPage) {
                  request.stock =
                    this.state.hasNoStockLimit == '1' ? null : Number(this.state.totalStock);
                } else {
                  request.units =
                    this.state.hasNoStockLimit == '1' ? null : Number(this.state.totalStock);
                  request.status =
                    request.units === null
                      ? PRODUCT_STATUS.UNLIMITED
                      : request.units === 0
                      ? PRODUCT_STATUS.OUT_OF_STOCK
                      : PRODUCT_STATUS.IN_STOCK;
                }
                return this.props.editFn(request, paymentPageItemId).then((resp) => {
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
