/* eslint-disable react/display-name */
import { withSplitzService } from 'common/splitz';
import { merchantFetch } from 'merchant/utils/ajax';
import BaseForm from 'merchant/views/Offers/New/Forms/BaseForm';
import Description from 'merchant/views/Offers/New/Screens/Description';
import DiscountType from 'merchant/views/Offers/New/Screens/DiscountTypes';
import ApplicableOn from 'merchant/views/Offers/New/Screens/NoCostEMI/ApplicableOn';
import { isLowCostExperimentEnabled } from 'merchant/views/Offers/New/Screens/NoCostEMI/helpers/helper';
import OfferValidity from 'merchant/views/Offers/New/Screens/OfferValidity';
import Overview from 'merchant/views/Offers/New/Screens/Overview';
import Wizard from 'merchant/views/Offers/New/components/Wizard';

const VALID_TABS = [false, false, false, false, false];

class NoCostEMIForm extends BaseForm {
  constructor(props) {
    super(props);
    this.state = {
      offersData: {},
      isLoading: true,
      emiData: {},
    };
  }

  onOffersChange = (data) => {
    this.setState({
      offersData: data,
    });
  };

  get tabsData() {
    return [
      {
        name: 'Description',
        render: () => (
          <Description
            hideType
            isFormLocked={this.props.isFormLocked}
            values={this.props.values}
            handleChange={this.props.handleChange}
            handleBlur={this.props.handleBlur}
            setFieldTouched={this.props.setFieldTouched}
            setFieldValue={this.props.setFieldValue}
            errors={this.props.errors}
            setErrors={this.props.setErrors}
            touched={this.props.touched}
          />
        ),
      },
      {
        name: 'Discount type',
        render: () => (
          <DiscountType
            hideDiscountType
            isFormLocked={this.props.isFormLocked}
            currencySymbol={this.currencySymbol}
            offerType={this.props.values.type}
            emiData={this.state.emiData}
            values={this.props.values}
            handleChange={this.props.handleChange}
            handleBlur={this.props.handleBlur}
            setFieldTouched={this.props.setFieldTouched}
            setFieldValue={this.props.setFieldValue}
            errors={this.props.errors}
            setErrors={this.props.setErrors}
            touched={this.props.touched}
          />
        ),
      },
      {
        name: 'Applicable On',
        render: () => {
          return (
            <ApplicableOn
              emiData={this.state.emiData}
              onChange={this.onFieldChange}
              offersData={this.state.offersData}
              onOffersChange={this.onOffersChange}
              minAmount={this.props.values.min_amount}
              isFormLocked={this.props.isFormLocked}
              values={this.props.values}
              handleChange={this.props.handleChange}
              handleBlur={this.props.handleBlur}
              setFieldTouched={this.props.setFieldTouched}
              setFieldValue={this.props.setFieldValue}
              errors={this.props.errors}
              setErrors={this.props.setErrors}
              touched={this.props.touched}
            />
          );
        },
      },
      {
        name: 'Offer Validity',
        render: () => (
          <OfferValidity
            onChange={this.onFieldChange}
            isFormLocked={this.props.isFormLocked}
            values={this.props.values}
            handleChange={this.props.handleChange}
            handleBlur={this.props.handleBlur}
            setFieldTouched={this.props.setFieldTouched}
            setFieldValue={this.props.setFieldValue}
            errors={this.props.errors}
            setErrors={this.props.setErrors}
            touched={this.props.touched}
          />
        ),
      },
      {
        name: 'Overview',
        render: () => (
          <Overview
            currencySymbol={this.currencySymbol}
            isFormLocked={this.props.isFormLocked}
            values={this.props.values}
            handleChange={this.props.handleChange}
            handleBlur={this.props.handleBlur}
            setFieldTouched={this.props.setFieldTouched}
            setFieldValue={this.props.setFieldValue}
            errors={this.props.errors}
            setErrors={this.props.setErrors}
            touched={this.props.touched}
          />
        ),
      },
    ];
  }

  componentDidMount() {
    const { setFieldValue } = this.props;
    //setting initial values
    setFieldValue('type', 'instant');
    setFieldValue('discount_type', 'no_cost_emi');

    this.prepareDataForForm();
  }

  prepareDataForForm = () => {
    merchantFetch('merchant/methods')
      .then((resp) => {
        if (resp.data) {
          const { emi_plans = {}, emi_options = {} } = resp.data;

          this.setState({
            emiData: {
              emi_options,
              emi_plans,
            },
            isLoading: false,
          });
        }
      })
      .catch(({ errors }) => {
        let error = (errors || [])[0];

        if (!error) {
          error = `Some network error has occured`;
        }

        this.setState({
          error,
          isLoading: false,
        });
      });
  };

  render() {
    const {
      abExperiments: { Low_cost_offer },
    } = this.props.splitz;

    const isLowCostEnabled = isLowCostExperimentEnabled(Low_cost_offer);

    const { isFormLocked, values } = this.props;
    const isFormDisabled = isFormLocked || !values.creation_terms_accepted;
    return (
      <Wizard
        error={this.state.error}
        isLoading={this.state.isLoading}
        disabled={isFormDisabled}
        ref={(form) => (this.FormWizard = form)}
        tabsData={this.tabsData}
        validTabs={VALID_TABS}
        submitBtnText="Create EMI offer"
        onChange={this.onFieldChange}
        onClose={this.props.onClose}
        onSubmit={this.onSubmit}
        offersData={this.state.offersData}
        isLowCostExperimentEnabled={isLowCostEnabled}
        zIndex={9999}
        values={this.props.values}
        errors={this.props.errors}
        setErrors={this.props.setErrors}
      />
    );
  }
}

export default withSplitzService(NoCostEMIForm);
