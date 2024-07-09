import { withFormik } from 'formik';
import { connect } from 'react-redux';
import RTracking from 'react-tracking';

import { withRouter } from 'common/deprecated/withRouter';
import { getURLQueryParams } from 'common/utils/rzp-utils';
import { selfServeTrackSuccess } from 'common/utils/selfServeAnalytics';
import { luminateRow } from 'merchant/reducers/app';
import { saveOffer } from 'merchant/reducers/offers/offerDetails';
import { appendOfferInReduxList } from 'merchant/reducers/offers/offersList';
import { StyledOfferModal } from 'merchant/views/Offers/New/Screens/NoCostEMI/Styled';
import { closeModal, openModal } from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';

import BaseForm from './Forms/BaseForm';
import NoCostEMI from './Forms/NoCostEMI';
import OfferForm from './Forms/Offers';
import Subscription from './Forms/Subscription';
import OfferTypeSelector from './components/OfferTypeSelector';

const BaseFormKey = 'base';
const FORMS = {
  [BaseFormKey]: BaseForm,
  basic: OfferForm,
  'no-cost-emi': NoCostEMI,
  subscription: Subscription,
};

@connect((state) => state.session, {
  showNotification,
  openModal,
  closeModal,
  luminateRow,
  appendOfferInReduxList,
})
@RTracking(() => window.rzpQ.component('CreateOfferWizard'))
// eslint-disable-next-line no-undef
class CreateOfferWizard extends React.Component {
  constructor(props) {
    super();

    this.IS_MODAL_VIEW = !!props.onClose;

    const searchQuery = getURLQueryParams(props.location.search);
    this.CURRENT_FORM = searchQuery.offer_creation_modal_type || BaseFormKey;

    this.state = {
      isFormLocked: false,
    };
  }

  onSubmit = (values) => {
    this.setState({
      isFormLocked: true,
    });

    return saveOffer(values)
      .then((savedOffer) => {
        this.setState({
          isFormLocked: false,
        });

        let selfServeActionName = 'New Offer Created';
        if (this.CURRENT_FORM === 'no-cost-emi') {
          selfServeActionName = 'New No Cost EMI Offer Created';
        }

        if (savedOffer && savedOffer['0']) {
          if (
            Object.keys(savedOffer).find((offer) => {
              return (
                savedOffer[offer] &&
                savedOffer[offer].code &&
                savedOffer['0'].code === 'BAD_REQUEST_ERROR'
              );
            })
          ) {
            this.props.showNotification({
              type: 'error',
              message: savedOffer['0'].description,
            });
          } else {
            selfServeTrackSuccess({
              selfServeAction: selfServeActionName,
              page: 'Offers',
              screen: 'Offers',
            });
            this.props.showNotification({
              type: 'success',
              message: 'New offer created',
            });

            const savedOfferIds = [];
            Object.keys(savedOffer).forEach((offer) => {
              if (offer && typeof savedOffer[offer] === 'object' && savedOffer[offer].id) {
                savedOfferIds.push(savedOffer[offer].id);
                const entityId = `offer_${savedOffer[offer].id}`;
                this.props.appendOfferInReduxList({
                  ...savedOffer[offer],
                  id: entityId,
                  resourceFields: savedOffer.resourceFields,
                  resourceIdField: savedOffer.resourceIdField,
                  resourceUrl: savedOffer.resourceUrl,
                  entity: 'offer',
                });
                this.props.luminateRow(entityId);
              }
            });

            if (savedOfferIds && savedOfferIds.length) {
              savedOfferIds.forEach((id) => {
                //analytics event tracking
                this.props.tracking.trackEvent(
                  window.rzpQ.merchantActions().success('offer_create', {
                    offer_id: id,
                  }),
                );
              });
              if (this.IS_MODAL_VIEW) {
                this.props.onClose();
              } else {
                this.props.history.push(`/offers/`);
              }
            }
          }
        } else {
          selfServeTrackSuccess({
            selfServeAction: selfServeActionName,
            page: 'Offers',
            screen: 'Offers',
          });
          this.props.showNotification({
            type: 'success',
            message: 'New offer created',
          });

          //analytics event tracking
          this.props.tracking.trackEvent(
            window.rzpQ.merchantActions().success('offer_create', {
              offer_id: savedOffer.id,
            }),
          );

          const entityId = savedOffer.id;
          if (this.IS_MODAL_VIEW) {
            this.props.appendOfferInReduxList(savedOffer);

            setTimeout(() => {
              this.props.onClose();
              this.props.luminateRow(entityId);
            }, 50);
          }
          this.props.history.push(`/offers/${entityId}`);
        }
      })
      .catch(({ errors }) => {
        let error = (errors || [])[0];

        if (!error) {
          error = `Some network error has occured`;
        }

        this.props.showNotification({
          type: 'error',
          message: error,
        });

        this.setState({
          isFormLocked: false,
        });
      });
  };

  handleSelectTemplate = (offerType) => {
    this.props.history.push(`?offer_creation_modal_type=${offerType}`);
    this.CURRENT_FORM = offerType;
  };

  render() {
    const { state, props } = this;
    const showSelectionView = this.CURRENT_FORM === BaseFormKey;
    const Form = FORMS[this.CURRENT_FORM];
    return (
      <StyledOfferModal class="Offers--Create">
        {showSelectionView && (
          <OfferTypeSelector
            selectTemplate={this.handleSelectTemplate}
            isModalView={this.IS_MODAL_VIEW}
          />
        )}

        <Form {...props} onSubmit={this.onSubmit} isFormLocked={state.isFormLocked} />
      </StyledOfferModal>
    );
  }
}

export default withRouter(
  withFormik({
    mapPropsToValues: () => ({}),
    validate: () => {
      const errors = {};
      return errors;
    },
    validateOnChange: true,
  })(CreateOfferWizard),
);
