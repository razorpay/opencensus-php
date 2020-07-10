import { getCurrency } from 'common/ui/Amount';
import ButtonDetailsPreview from './ButtonDetailsPreview';

export default class WidgetPreview extends React.Component {
  getPlanField(field) {
    const { subscriptionButtonEntity } = this.props;
    const currency = subscriptionButtonEntity.currency,
      currencySymbol = getCurrency(currency).symbol;

    const amount = field.item.amount;
    let amountToDisplay;

    if (amount) {
      const _amount = Number(amount).toFixed(2);
      const _amountToDisplay = _amount.split('.');

      amountToDisplay = (
        <span class="amount">
          <b>
            {currencySymbol} {_amountToDisplay[0]}
          </b>
          <span class="amount-decimal">.{_amountToDisplay[1]}</span>
        </span>
      );
    }

    return (
      <label class="item-label">
        <div class="item">
          <div class="item-title">{field.item.name}</div>
          {field.item.description && (
            <div class="item-description">{field.item.description}</div>
          )}

          <div class="item-details">
            {amountToDisplay}
            {/*<div class="item-details-description">{getSubscriptionPeriodText(plan)}</div>*/}
          </div>
        </div>
      </label>
    );
  }

  render() {
    const { planFields } = this.props;

    return (
      <div class="WidgetPreview">
        <div>
          {planFields && planFields.length ? (
            planFields.map((field, index) => (
              <React.Fragment key={index}>
                {this.getPlanField(field)}
              </React.Fragment>
            ))
          ) : (
            <label class="item-label item-label--empty">
              <div class="item">
                <div class="item-details">Add plans to see their preview</div>
              </div>
            </label>
          )}
        </div>

        <ButtonDetailsPreview {...this.props} />
      </div>
    );
  }
}
