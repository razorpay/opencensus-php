import React from 'react';
import { withRouter } from 'common/deprecated/withRouter';
import { connect } from 'react-redux';

import { fetchPlans } from 'merchant/reducers/plans';
import {
  fetchSubscriptionItems,
  fetchSubscription,
  saveSubscription,
  fetchSubscriptionOffers,
  fetchSettings,
} from 'merchant/reducers/subscriptions';
import { fetchAddOns } from 'merchant/reducers/addons';
import { fetchCustomer } from 'merchant/reducers/customers';
import { showNotification } from 'merchant_common/reducers/notifications';
import DocsLink from 'merchant/components/DocsLink';

import { ModalAsideNav } from 'common/new-ui/Wizard';
import { Modal, ModalContent } from 'common/new-ui/Modal';
import Form from 'common/new-ui/Form';
import Button, { AsyncBtn } from 'common/new-ui/Button';

import {
  isPresent,
  findBy,
  getURLQueryParams,
  stringToObj,
  deepClone,
  classList,
} from 'common/utils/rzp-utils';
import { UPI_AVL_LIMIT } from 'merchant/helpers/data';

import AddOnDetails from './AddOnDetails';
import LinkDetails from './LinkDetails';
import PlanDetails from 'merchant/views/Subscriptions/SubscriptionLinks/components/PlanDetails';
import Review from './Review';
import UPIBanner from 'merchant/views/Subscriptions/SubscriptionLinks/components/UPIBanner';
import Spinner from 'common/ui/Spinner';
import moment from 'moment';
import { isMobileDevice } from 'merchant/components/Home/data';
import {
  trackSaveDuplicateSubscription,
  trackAddAddon,
  trackAddPlans,
} from 'merchant/views/Subscriptions/SubscriptionLinks/ga';
import analytics from 'merchant/views/Subscriptions/analytics';
import './index.styl';
import { selfServeTrackSuccess } from 'common/utils/selfServeAnalytics';

const tabs = ['Plan Details', 'Add Ons', 'Link Details', 'Review'];

// eslint-disable-next-line react/no-unsafe

@connect(
  (state) => ({
    plans: state.plans,
    items: state.items,
    user: state.session.user,
    subscriptionOffers: state.subscriptions.offers,
  }),
  {
    fetchSubscription,
    fetchCustomer,
    fetchPlans,
    fetchSubscriptionItems,
    saveSubscription,
    showNotification,
    fetchSettings,
    fetchSubscriptionOffers,
  },
)
class NewSubscriptionLink extends React.Component {
  state = {
    currentTab: 0,
    validTabs: [false, false, false, false],
    fields: {
      quantity: 1,
      addons: [],
    },
    internals: {},
  };
  isMobileDevice = isMobileDevice();

  UNSAFE_componentWillMount() {
    this.fetchDataForSubscription();
  }

  fetchDataForSubscription = async () => {
    await this.props.fetchPlans({ count: 100 }).then(() => this.initializePlan());
    if (
      this.props.user.isSubscriptionOffersEnabled &&
      this.props.subscriptionOffers.items.length === 0
    ) {
      this.props.fetchSettings().then((resp) => {
        const paymentMethods = [];
        resp.data.items.forEach((setting) => {
          if (setting.setting_enabled === '1') {
            paymentMethods.push(setting.name);
          }
        });

        this.props
          .fetchSubscriptionOffers(['card', ...paymentMethods])
          .then((res) => this.initializeOffer(res.data));
      });
    }

    this.fetchIfIntentDuplicate();
    this.props.fetchSubscriptionItems({ count: 100, type: 'addon' });
  };

  initializePlan() {
    if (this.state.fields.plan_id && this.props.plans.items && this.props.plans.items.length) {
      const currencyOfSelectedPlan = this.props.plans.items.filter(
        (p) => p.id === this.state.fields.plan_id,
      )[0].item.currency;

      this.setState({
        currencyOfSelectedPlan,
      });
    }
  }

  initializeOffer(offers) {
    if (this.state.fields.offer_id && offers.items && offers.items.length) {
      const currencyOfSelectedOffer = offers.items.filter(
        (offer) => offer.id === this.state.fields.offer_id,
      ).id;

      this.setState({
        currencyOfSelectedOffer,
        isFetchingSubscription: false,
      });
    }
  }

  fetchIfIntentDuplicate() {
    const searchQuery = getURLQueryParams(this.props.location.search);

    if (searchQuery.duplicate_id) {
      this.setState({
        isFetchingSubscription: true,
      });

      this.props
        .fetchSubscription(searchQuery.duplicate_id)
        .then((data) => {
          this.isIntentDuplicate = true;

          let expire_by = data.expire_by && moment(data.expire_by * 1000);
          let start_at = data.start_at && moment(data.start_at * 1000);

          // If null or is before current time
          if (!expire_by || expire_by.diff(moment()) < 0) {
            expire_by = '';
          } else {
            expire_by = data.expire_by;
          }

          // If null or is before current time
          if (!start_at || start_at.diff(moment()) < 0) {
            start_at = '';
          } else {
            start_at = data.start_at;
          }

          const newSubscription = {
            customer_notify: data.customer_notify,
            plan_id: data.plan_id,
            quantity: data.quantity,
            start_at,
            total_count: data.total_count,
            expire_by,
          };

          newSubscription.notes = Object.keys(data.notes).map((key) => ({
            key,
            value: data.notes[key],
          }));

          this.setState(
            (prevState) => {
              return {
                fields: { ...prevState.fields, ...newSubscription },
                internals: {
                  _startsImmediately: !start_at,
                  _isNonExpiringLink: !expire_by,
                },
              };
            },
            (_) => this.initializePlan(),
          );

          // Fetch addons
          fetchAddOns({
            subscription_id: searchQuery.duplicate_id,
          }).then(({ data: respData }) => {
            const addons = respData.items.map((a) => ({
              item_id: a.item.id,
              quantity: a.quantity,
              item: {
                name: a.item.name,
                description: a.item.description,
                amount: a.item.amount,
                currency: a.item.currency,
                type: 'addon',
              },
            }));

            this.setState((prevState) => ({
              fields: {
                ...prevState.fields,
                addons,
              },
              internals: {
                ...prevState.internals,
                _addOnPresent: isPresent(addons),
              },
            }));
          });

          // Fetch customer details
          if (data.customer_notify && data.customer_id) {
            this.props.fetchCustomer(data.customer_id).then((resData) => {
              this.setState((prevState) => ({
                fields: {
                  ...prevState.fields,
                  notify_info: {
                    notify_email: resData.email,
                    notify_phone: resData.contact,
                  },
                },
              }));
            });
          }
        })
        .finally(() => {
          this.setState({
            isFetchingSubscription: false,
          });
        });
    }
  }

  handleTabChange = ({ target }) => {
    const currentTab = Number(target.dataset.index);
    this.setState({ currentTab });
  };

  handleChangeIn = ({ target }) => {
    let value = target.value;
    const name = target.name || target.dataset.name;
    const stateKey = target.name ? 'fields' : 'internals';
    if (!name || name.match(/_time/)) {
      return;
    }

    this.setState(
      (prevState) => {
        let values = { ...prevState[stateKey] };

        if (target.type === 'number') {
          value = Number(value);
        } else if (target.type === 'checkbox') {
          value = target.checked;
        }

        values = stringToObj(name, value, values);
        return { [stateKey]: values };
      },
      () => {
        if (name === '_addOnPresent') {
          this.setState((prevState) => ({
            fields: {
              ...prevState.fields,
              addons: target.checked ? [{}] : [],
            },
          }));
        }
      },
    );
  };

  handleChangeInOffer = ({ option = {} } = {}) => {
    this.setState((prevState) => ({
      fields: {
        ...prevState.fields,
        offer_id: option.id,
      },
    }));
  };

  handleChangeInPlan = ({ option }) => {
    if (!option) return;

    const { currencyOfSelectedPlan, fields, internals } = this.state;

    trackAddPlans(option.currency);

    const currSelectedPlan = findBy(this.props.plans.items, 'id', option.id);

    if (currSelectedPlan && currSelectedPlan.item.currency !== currencyOfSelectedPlan) {
      if (internals._addOnPresent) {
        this.props.showNotification({
          type: 'neutral',
          message: 'Currency of Plan is changed. Please select the Add Ons again',
          closeTimeout: 8000,
        });

        this.setState({
          currencyOfSelectedPlan: option.currency,
          fields: {
            ...fields,
            plan_id: option.id,
            addons: [{}],
          },
          _selectedPlanAmount: currSelectedPlan.item.amount,
        });

        return;
      }

      this.setState({
        currencyOfSelectedPlan: option.currency,
        fields: {
          ...fields,
          plan_id: option.id,
        },
        _selectedPlanAmount: currSelectedPlan.item.amount,
      });

      return;
    }

    this.setState({
      currencyOfSelectedPlan: option.currency,
      fields: {
        ...fields,
        plan_id: option.id,
      },
      _selectedPlanAmount: currSelectedPlan.item.amount,
    });
  };

  handleSelectAddonItem =
    (addonIndex) =>
    ({ option }) => {
      trackAddAddon(option.currency);

      this.setState((prevState) => {
        const fields = { ...prevState.fields };
        fields.addons[addonIndex] = {
          item: {
            name: option.name,
            description: option.description,
            amount: option.amount,
            currency: option.currency,
            type: 'addon',
          },
          item_id: option.id,
          quantity: 1,
        };
        return {
          fields,
        };
      });
    };

  handleDateChange = (fieldName) => (selectedDate) => {
    selectedDate.startOf('day');

    const current = this.state.fields[fieldName] ? moment(this.state.fields[fieldName], 'X') : 0;
    const time = current
      ? Number(current.format('X')) - Number(current.startOf('day').format('X'))
      : 0;

    const target = {
      name: fieldName,
      value: Number(selectedDate.format('X')) + time,
    };
    this.handleChangeIn({ target });
  };

  handleTimeChange = (fieldName) => (selectedDate) => {
    const time = Number(selectedDate.format('X')) - Number(selectedDate.startOf('day').format('X'));
    fieldName = fieldName.replace('_time', '');

    let current = this.state.fields[fieldName];

    // adding time to current day
    current = Number(moment(current, 'X').startOf('day').format('X'));
    const target = {
      name: fieldName,
      value: current + time,
    };
    this.handleChangeIn({ target });
  };

  handleAddaddon = () => {
    this.setState((prevState) => {
      const fields = { ...prevState.fields };
      fields.addons.push({});
      return { fields };
    });
  };

  handleCreate = () => {
    analytics.track('subscription.create.issue');
    if (this.isIntentDuplicate) {
      trackSaveDuplicateSubscription();
      analytics.track('subscription.clone.complete');
    }

    const { fields: fieldsData, internals } = this.state;
    const data = deepClone(fieldsData);
    data.source = 'dashboard';

    if (internals._startsImmediately) {
      delete data.start_at;
    }

    if (internals._isNonExpiringLink) {
      delete data.expire_by;
    }

    if (!data.customer_notify) {
      delete data.customer_notify;
    }

    // formatting notes, from [key: key1, value: value1] => {key1: value1}
    data.notes = (data.notes || []).reduce(
      (otherNotes, { key, value }) => ({ ...otherNotes, [key]: value }),
      {},
    );

    data.addons = data.addons.map((addon) => {
      delete addon.item;

      return addon;
    });

    return this.props
      .saveSubscription(data)
      .then((resData) => {
        if (resData) {
          selfServeTrackSuccess({
            selfServeAction: 'Subscription Created',
            page: 'Subscriptions',
            screen: 'Subscriptions',
          });
          this.props.showNotification({
            type: 'success',
            message: 'Subscription Created Successfully',
          });
          analytics.track('subscription.create.success');

          if (this.props.onClose) {
            this.props.onClose();
          } else {
            const entityId = resData.id;
            const redirectUrl = `/subscriptions/${entityId}`;
            this.props.history.push(redirectUrl);
          }
        }
      })
      .catch(({ errors }) => {
        this.props.showNotification({
          type: 'error',
          message: errors,
        });
        analytics.track('subscription.create.fail');
      });
  };

  handleRemoveBtn = (addonIndex) => () => {
    const addons = this.state.fields.addons.filter((_, index) => addonIndex !== index);

    this.setState((prevState) => {
      const fields = {
        ...prevState.fields,
        addons,
      };

      const internals = {
        ...prevState.internals,
        _addOnPresent: isPresent(addons),
      };
      return { fields, internals };
    });
  };

  changeTab = (step) => {
    this.setState((prevState) => {
      const currentTab = prevState.currentTab + step;

      const validTabs = [...prevState.validTabs];
      validTabs[prevState.currentTab] = true;
      return { currentTab, validTabs };
    });
  };

  isFormValid = () => {
    const { currentTab, fields, internals } = this.state;
    const validateTotalCount = (this.planDetailsForm || {}).validateTotalCount;
    return isFormValid(currentTab, fields, internals, validateTotalCount, this.props.isEdit);
  };

  renderForm() {
    const cloneOptions = {
      clone: this.isIntentDuplicate ? '1' : '0',
    };

    switch (this.state.currentTab) {
      case 0:
        return (
          <PlanDetails
            plans={this.props.plans}
            offers={this.props.subscriptionOffers}
            showOffers={this.props.user.isSubscriptionOffersEnabled}
            onChangeInPlan={this.handleChangeInPlan}
            onDateChange={this.handleDateChange}
            onTimeChange={this.handleTimeChange}
            onChangeInOffer={this.handleChangeInOffer}
            fields={this.state.fields}
            internals={this.state.internals}
            ref={(form) => (this.planDetailsForm = form)}
            cloneOptions={cloneOptions}
          />
        );
      case 1:
        return (
          <AddOnDetails
            items={this.props.items}
            onSelectItem={this.handleSelectAddonItem}
            onAddAddon={this.handleAddaddon}
            fields={this.state.fields}
            internals={this.state.internals}
            removeAddOn={this.handleRemoveBtn}
            currency={this.state.currencyOfSelectedPlan}
            cloneOptions={cloneOptions}
          />
        );
      case 2:
        return (
          <LinkDetails
            onDateChange={this.handleDateChange}
            onTimeChange={this.handleTimeChange}
            fields={this.state.fields}
            internals={this.state.internals}
            cloneOptions={cloneOptions}
          />
        );
      case 3:
        return (
          <Review
            fields={this.state.fields}
            internals={this.state.internals}
            plans={this.props.plans.items}
            offers={this.props.subscriptionOffers.items}
            getCurrencyList={this.props.user.getCurrencyList}
          />
        );
      default:
        return null;
    }
  }

  renderWizard(isStandAlone = false) {
    const { isFetchingSubscription, currentTab, _selectedPlanAmount, fields } = this.state;
    const isLastTab = currentTab === tabs.length - 1;

    const sumOfAddons =
      fields.addons?.reduce((previous, addonItem) => {
        const totalAmount = addonItem.item && addonItem.item.amount * addonItem.quantity;

        return totalAmount + previous;
      }, 0) ?? 0;

    const showUPIUnAvlBanner = _selectedPlanAmount > UPI_AVL_LIMIT || sumOfAddons > UPI_AVL_LIMIT;

    return (
      // need to improve this css styling
      <div
        class={classList(
          'Links--Create SubscriptionLinks--new Wizard',
          showUPIUnAvlBanner && 'upi-banner-visible',
        )}
      >
        {/* create subscription link tabs */}
        <ModalAsideNav
          title="Create Subscription"
          description={<p>Provide details to create a subscription link</p>}
          tabs={tabs}
          tabClickHandler={this.handleTabChange}
          activeTab={currentTab}
          tabsValidity={this.state.validTabs}
          disableTabCondition={(tabIndex) => tabIndex !== 0 && !this.state.validTabs[tabIndex - 1]}
        />
        {isFetchingSubscription ? (
          <div class="page-center">
            <Spinner />
          </div>
        ) : (
          <>
            <main class="form-container">
              {(!this.isMobileDevice || isStandAlone) && (
                <main-title>{tabs[currentTab]}</main-title>
              )}
              <Form layout="tabular" onChange={this.handleChangeIn}>
                {this.renderForm()}
              </Form>
            </main>
            <footer>
              {this.props.user.isCardRecurringPaymentsBlocked && (
                <div
                  class={classList(
                    'card-blocked-banner',
                    showUPIUnAvlBanner && 'upi-banner-visible',
                  )}
                >
                  <i class="i i-info-circle" /> Cards issued by Indian banks are temporarily
                  disabled for new subscriptions.{' '}
                  <DocsLink
                    url="https://razorpay.com/docs/announcements/rbi-card-mandate-guidelines/recurring-payments"
                    title="Learn more"
                  />
                </div>
              )}

              {showUPIUnAvlBanner && <UPIBanner />}

              {currentTab > 0 && (
                <Button
                  class="btn-outline"
                  onClick={() => {
                    analytics.track(`subscription.create.previous${currentTab}`);
                    this.changeTab(-1);
                  }}
                  type="button"
                >
                  Previous
                </Button>
              )}
              {!isLastTab ? (
                <Button.Primary
                  onClick={() => {
                    analytics.track(`subscription.create.next${currentTab + 1}`);
                    this.changeTab(1);
                  }}
                  type="button"
                  disabled={!this.isFormValid()}
                >
                  Next
                </Button.Primary>
              ) : (
                <AsyncBtn.Primary
                  pendingState="Creating..."
                  type="submit"
                  onClick={this.handleCreate}
                >
                  Create Subscription Link
                </AsyncBtn.Primary>
              )}
            </footer>
          </>
        )}
      </div>
    );
  }

  render() {
    const isModalView = this.props.onClose;

    return isModalView ? (
      <Modal class="NewSubscriptionLink animate-down" onClose={this.props.onClose} fullWidth>
        <ModalContent header={this.isMobileDevice ? tabs[this.state.currentTab] : null}>
          {this.renderWizard(false)}
        </ModalContent>
      </Modal>
    ) : (
      <div class="StandAloneContainer">{this.renderWizard(true)}</div>
    );
  }
}

function isFormValid(formIndex, fields, internals, validateTotalCount = () => {}, isEdit) {
  switch (formIndex) {
    case 0: {
      return (
        !!fields.plan_id &&
        (internals._startsImmediately || !!fields.start_at) &&
        !validateTotalCount(isEdit ? fields.remaining_count : fields.total_count)
      );
    }

    case 1: {
      return !internals._addOnPresent || fields.addons.every(isPresent);
    }

    case 2: {
      const notify_info = fields.notify_info || {};
      return (
        (!fields.customer_notify || !!notify_info.notify_email || !!notify_info.notify_phone) &&
        (internals._isNonExpiringLink || !!fields.expire_by)
      );
    }
    default:
      return false;
  }
}

export default withRouter(NewSubscriptionLink);
