/* eslint-disable */
import React from 'react';
import Input from 'common/new-ui/Input';
import { merchantFetch } from 'merchant/utils/ajax';
import { ISSUERS, PAYMENT_NETWORK_MAP } from '../constants';
import { deepClone } from '../../../../common/utils/rzp-utils';
import Spinner from 'common/ui/Spinner';
import Amount from '../../../../common/ui/Amount';
import { DocLink } from 'merchant/components/DocsLink';

export default class NoCostEmiMethods extends React.Component {
  constructor(props) {
    super(props);
    this.state = {
      emiOptions: null,
      selectedIssuer: this.props.issuer || null,
      tenure: {},
      isLoading: true,
      emiOptionsDetails: null,
    };
  }

  componentDidMount() {
    let methodsReq = merchantFetch('merchant/methods');
    methodsReq
      .then((res) => {
        this.setState({
          emiOptions: (res.data && res.data.emi_plans) || null,
          emiOptionsDetails: (res.data && res.data.emi_options) || null,
          isLoading: false,
        });
      })
      .catch(() => {
        //todo show error message in the header
      });
    this.setState({
      emiDurations: this.props.emiDurations || null,
      tenure: (this.props.emiDurations || []).reduce((pV, cV) => {
        pV[cV] = true;
        return pV;
      }, {}),
    });
  }

  onChange = (event) => {
    event.persist();
    this.props.getFormOnChangeHandler('stateResetter')(['emi_durations'])(event);
    this.setState({ selectedIssuer: event.target.value });
  };

  onSelectTenure = (tenure) => (event) => {
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
            value: Object.keys(this.state.tenure).map((stringTenure) => parseInt(stringTenure)),
          },
        };
        this.props.getFormOnChangeHandler()(pseudoEvent);
      }
    });
  };

  renderDuration() {
    let planFields = [];

    if (this.state.selectedIssuer !== null) {
      if (this.state.selectedIssuer.length === 0) return null;

      let emiPlans = this.state.emiOptions[this.state.selectedIssuer] || {
        plans: [],
      };

      let emiMerchantPaybacks = this.state.emiOptionsDetails[this.state.selectedIssuer].reduce(
        (acc, item) => {
          if (acc[item.duration]) {
            return acc;
          } else {
            acc[item.duration] = item;
            return acc;
          }
        },
        {},
      );

      for (let duration in emiPlans.plans) {
        let text = `${duration} Months`;
        planFields.push(
          <div className="offers-emi-options-row">
            <div className="emi-checkfield">
              <Input.Check
                fieldLabel={text}
                onChange={this.onSelectTenure(duration)}
                defaultValue={
                  (Array.isArray(this.props.emiDurations) &&
                    this.props.emiDurations.indexOf(parseInt(duration)) > -1) ||
                  false
                }
              />
            </div>
            <p>{emiMerchantPaybacks[duration].merchant_payback} %</p>
          </div>,
        );
      }

      planFields.unshift(
        <div className="offers-emi-options-row" style={{ padding: '13px', fontWeight: 'bold' }}>
          <div className="emi-checkfield">
            <p>EMI tenure</p>
          </div>
          <p>Discount borne by merchant</p>
        </div>,
      );
    }
    if (planFields.length > 0) {
      return (
        <Input.Group label="EMI Tenure">
          <div className="offers-emi-options-container" required>
            {planFields}
          </div>
        </Input.Group>
      );
    }
  }

  render() {
    let issuers = (this.state.emiOptions && Object.keys(this.state.emiOptions)) || [];
    let networksAndIssuers = { ...PAYMENT_NETWORK_MAP, ...ISSUERS };
    issuers = issuers
      .filter((issuer) => {
        let issuerData = this.state.emiOptions[issuer];
        return issuerData.min_amount <= this.props.minAmount * 100;
      })
      .map((issuer) => ({
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
          defaultValue={this.props.issuer || ''}
          onChange={this.onChange}
          required
        />
        {this.renderDuration()}
        <div className="no-cost-emi-footnote">
          <ul>
            <li>
              Only banks with minimum EMI order amount of{' '}
              <Amount value={this.props.minAmount * 100} /> are being displayed.
            </li>
            <li>
              In No-Cost-EMI, the interest charged by bank is given as a discount to the customer.
              To know more about how this works, click{' '}
              <DocLink target="_blank" href="https://razorpay.com/docs/offers/no-cost-emi/">
                here
              </DocLink>
              .
            </li>
          </ul>
        </div>
      </React.Fragment>
    ) : (
      <div className="no-cost-emi-loader">
        <Spinner />
      </div>
    );
  }
}
