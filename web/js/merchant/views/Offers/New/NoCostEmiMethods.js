import React from 'react';
import Input from 'common/new-ui/Input';
import { merchantFetch } from 'merchant/utils/ajax';
import { ISSUERS, PAYMENT_NETWORK_MAP } from 'merchant/views/Offers/Entity';
import { deepClone } from '../../../../common/utils/rzp-utils';
import Spinner from 'common/ui/Spinner';

export default class NoCostEmiMethods extends React.Component {
  constructor(props) {
    super(props);
    this.state = {
      emiOptions: null,
      selectedIssuer: null,
      tenure: {},
      isLoading: true,
    };
  }

  componentDidMount() {
    let methodsReq = merchantFetch('merchant/methods');
    methodsReq
      .then(res => {
        this.setState({
          emiOptions: (res.data && res.data.emi_plans) || null,
          isLoading: false,
        });
      })
      .catch(error => {
        //todo show error message in the header
      });
  }

  onChange = event => {
    event.persist();
    this.setState({ selectedIssuer: event.target.value }, () =>
      this.props.getFormOnChangeHandler()(event)
    );
  };

  onSelectTenure = tenure => event => {
    let tenureState = deepClone(this.state.tenure);
    if (event.target.value === '1' && !tenureState[tenure]) {
      tenureState[tenure] = true;
    }
    if (event.target.value === '0' && tenureState[tenure]) {
      delete tenureState[tenure];
    }
    this.setState({ tenure: tenureState }, () => {
      if (this.props.getFormOnChangeHandler) {
        let pseudoEvent = {
          target: {
            name: 'emi_durations',
            value: Object.keys(this.state.tenure).map(stringTenure =>
              parseInt(stringTenure)
            ),
          },
        };
        this.props.getFormOnChangeHandler()(pseudoEvent);
      }
    });
  };

  renderDuration() {
    let planFields = [];
    if (this.state.selectedIssuer !== null) {
      let emiPlans = this.state.emiOptions[this.state.selectedIssuer] || {
        plans: [],
      };
      let count = 1;
      for (let duration in emiPlans.plans) {
        let text = `${duration} Months`;
        planFields.push(
          <div key={this.state.selectedIssuer + count++}>
            <Input.Check
              fieldLabel={text}
              onChange={this.onSelectTenure(duration)}
            />
          </div>
        );
      }
    }
    if (planFields.length > 0) {
      return <Input.Group label={'EMI Tenure'}>{planFields}</Input.Group>;
    }
  }

  render() {
    let issuers =
      (this.state.emiOptions && Object.keys(this.state.emiOptions)) || [];
    let networksAndIssuers = { ...PAYMENT_NETWORK_MAP, ...ISSUERS };
    issuers = issuers
      .filter(issuer => {
        let issuerData = this.state.emiOptions[issuer];
        return issuerData.min_amount <= this.props.minAmount * 100;
      })
      .map(issuer => ({
        name: issuer,
        label: networksAndIssuers[issuer] || issuer,
      }));
    issuers.unshift({
      name: '',
      label: 'Select Issuer',
    });
    return this.state.isLoading === false ? (
      <React.Fragment>
        <Input.Select
          label="Issuer"
          name="issuer"
          placeholder="Select network"
          options={issuers}
          onChange={this.onChange}
        />
        {this.renderDuration()}
        <div className="no-cost-emi-footnote">
          In No-Cost-EMI, the interest charged by bank in given as a discount to
          the customer. To know more how this works click here.
        </div>
      </React.Fragment>
    ) : (
      <div className="no-cost-emi-loader">
        <Spinner />
      </div>
    );
  }
}
