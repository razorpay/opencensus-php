import Input from 'common/new-ui/Input';

export default ({
  percentRate,
  maxCashback,
  flatCashback,
  discountType,
  getFormElementValidations,
}) => {
  return (
    <React.Fragment>
      <strong>Instant Discount</strong>
      <p>The customer will pay the discounted price for the product</p>
      <div>
        <Input.Select
          name="discount_type"
          label="Discount Type"
          placeholder="Discount Type"
          required
          defaultValue={discountType}
          options={[
            { label: 'Select Type', name: '' },
            { label: 'Flat', name: 'flat' },
            { label: 'Percentage', name: 'percent' },
          ]}
          validator={getFormElementValidations('discount_type')}
        />
        {discountType &&
          discountType === 'flat' && (
            <Input
              label="Discount Worth"
              name="flat_cashback"
              class="Input--half"
              addonBefore={<span>{window.currencyList['INR'].symbol}</span>}
              description="Discount worth in cash"
              required
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
                description="Discount worth in Percent"
                addonBefore={<span>%</span>}
                required
                defaultValue={percentRate}
                validator={getFormElementValidations('percent_rate')}
              />
              <Input
                label="Maximum Cashback"
                name="max_cashback"
                defaultValue={maxCashback}
                class="Input--half"
                description="Maximum cashback for this offer"
                addonBefore={<span>{window.currencyList['INR'].symbol}</span>}
                validator={getFormElementValidations('max_cashback')}
                required
              />
            </React.Fragment>
          )}
      </div>
    </React.Fragment>
  );
};
