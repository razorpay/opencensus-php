import Input from 'common/new-ui/Input';

export default ({
  percentRate,
  maxCashback,
  flatCashback,
  discountType,
  getFormElementValidations,
  minAmount,
  maxAmount,
  currency,
  getFormOnChangeHandler,
  type,
  isOnlyNoCostEmi,
}) => {
  const fieldToResetOnMinAmountChange =
    discountType === 'no_cost_emi' ? ['emi_durations', 'issuer'] : [];

  return (
    <React.Fragment>
      {type === 'instant' && <strong>Instant Discount</strong>}
      {type === 'instant' && (
        <p>The customer will pay the discounted price for the product</p>
      )}
      <div>
        {!isOnlyNoCostEmi && (
          <Input.Select
            name="discount_type"
            class="Input--half"
            label="Discount Type"
            placeholder="Discount Type"
            required
            defaultValue={discountType}
            options={[
              { label: 'Select Type', name: '' },
              { label: 'Flat', name: 'flat' },
              { label: 'Percentage', name: 'percent' },
            ]}
            onChange={getFormOnChangeHandler('stateResetter')([
              'flat_cashback',
              'percent_rate',
              'max_cashback',
              'emi_durations',
              'max_order_amount',
            ])}
            validator={getFormElementValidations('discount_type')}
          />
        )}
        {discountType && (
          <Input
            label="Minimum Order amount"
            placeholder="0.00"
            name="min_amount"
            defaultValue={minAmount}
            class="Input--half"
            addonBefore={<span>{window.currencyList[currency].symbol}</span>}
            validator={getFormElementValidations('min_amount')}
            required={discountType !== 'percent'}
            onChange={getFormOnChangeHandler('stateResetter')(
              fieldToResetOnMinAmountChange
            )}
          />
        )}
        {discountType &&
          discountType === 'no_cost_emi' && (
            <Input
              label="Maximum Order amount"
              placeholder="0.00"
              name="max_order_amount"
              defaultValue={maxAmount}
              class="Input--half"
              addonBefore={<span>{window.currencyList[currency].symbol}</span>}
              validator={getFormElementValidations('max_order_amount')}
              required={discountType === 'flat'}
              onChange={getFormOnChangeHandler()}
            />
          )}

        {discountType &&
          discountType === 'flat' && (
            <Input
              label="Discount Worth"
              name="flat_cashback"
              class="Input--half"
              addonBefore={<span>{window.currencyList[currency].symbol}</span>}
              description="Discount worth in cash"
              required
              placeholder="0.00"
              onChange={getFormOnChangeHandler()}
              defaultValue={flatCashback}
              validator={getFormElementValidations('flat_cashback')}
            />
          )}
        {discountType &&
          discountType === 'percent' && (
            <React.Fragment>
              <Input
                label="Discount Worth"
                name="percent_rate"
                class="Input--half"
                placeholder="0.00"
                description="Discount worth in Percent"
                addonAfter={<span>%</span>}
                required
                defaultValue={percentRate}
                validator={getFormElementValidations('percent_rate')}
                onChange={getFormOnChangeHandler()}
              />
              <Input
                label={
                  type === 'instant' ? 'Maximum Discount' : 'Maximum Cashback'
                }
                placeholder="0.00"
                name="max_cashback"
                defaultValue={maxCashback}
                class="Input--half"
                description={
                  type === 'instant'
                    ? 'Maximum discount for this offer'
                    : 'Maximum cashback for this offer'
                }
                addonBefore={
                  <span>{window.currencyList[currency].symbol}</span>
                }
                validator={getFormElementValidations('max_cashback')}
                required
                onChange={getFormOnChangeHandler()}
              />
            </React.Fragment>
          )}
      </div>
    </React.Fragment>
  );
};
