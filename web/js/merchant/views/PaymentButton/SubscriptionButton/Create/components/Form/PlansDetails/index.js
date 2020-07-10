import { connect } from 'react-redux';

import Button from 'common/new-ui/Button';
import EditableDisplayField from './EditableDisplayField';

import { updateStepReviewProgress } from 'merchant/reducers/subscriptionButtons/create';
import { fetchPlans } from 'merchant/reducers/plans';

// import track from '../../../track';

@connect(
  state => ({
    plans: state.plans,
  }),
  {
    updateStepReviewProgress,
    fetchPlans,
  }
)
export default class PlansDetails extends React.Component {
  maxFieldsLimit = 5;

  componentDidMount() {
    this.props.fetchPlans({ count: 100 }); // TODO: Api support max 100. If some merchant has plans >100, we should implement auto complete
  }

  goNext = () => {
    this.props.goNext();

    this.markReviewDone();

    // track.lj.trackAmountScreenNextSuccess();
  };

  markReviewDone = () => {
    this.props.updateStepReviewProgress({
      isPlansDetailsReviewed: true,
    });
  };

  setRefFormContainer = el => (this.formContainerEl = el);

  render() {
    // TODO: Filter plan as per the currency of 1st plan selected
    const { planFields, subscriptionButtonEntity, plans } = this.props;
    const currency = subscriptionButtonEntity.currency;

    return (
      <div class="Form" style={{ display: this.props.isHidden ? 'none' : '' }}>
        <div class="PaymentButtonForm-AmountDetails Form-content">
          {planFields.map((field, index) => (
            <EditableDisplayField
              key={index}
              indexInOrder={index}
              field={field}
              plansOptions={plans.items}
              currency={currency}
            />
          ))}
          {this.maxFieldsLimit !== planFields.length && (
            <EditableDisplayField
              field={null}
              plansOptions={plans.items}
              currency={currency}
            >
              <Button
                class="Button--primary--invert addFieldBtn"
                // onClick={track.lj.trackCustomerScreenInputField}
              >
                <b>+ Add Plan Item</b>
              </Button>
            </EditableDisplayField>
          )}
        </div>

        <div className="Form-controls">
          <Button.Transparent
            type="button"
            onClick={() => {
              this.props.goBack();

              // track.lj.trackAmountScreenBackSuccess();
            }}
          >
            Back
          </Button.Transparent>

          <Button.Primary
            type="button"
            disabled={!planFields.length}
            onClick={this.goNext}
          >
            Next <i className="i i-chevron-right" />
          </Button.Primary>
        </div>
      </div>
    );
  }
}
