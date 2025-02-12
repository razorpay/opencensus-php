import React, { Component } from 'react';
import { compose } from 'redux';
import { connect } from 'react-redux';
import { Field, reduxForm } from 'redux-form';
import AsyncButton from 'react-async-button';
import RTracking from 'react-tracking';
import { withI18Service } from 'common/i18';
import InputField from 'common/ui/Forms/InputField';
import Alert from 'common/ui/Forms/Alert';
import ModalHeader from 'common/ui/ModalHeader';
import Spinner from 'common/ui/Spinner';
import { createAppWebhook, editAppWebhook } from 'merchant/reducers/applications';
import { required, email } from 'common/utils/validators';
import { saveWebhook } from 'merchant/reducers/webhooks';
import { merchantFetch } from 'merchant/utils/ajax';
import * as ModalActions from 'merchant_common/reducers/modals';
import * as NotificationsActions from 'merchant_common/reducers/notifications';
import DocsLink from 'merchant/components/DocsLink';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import sanitizer from 'common/utils/xss-sanitizer';
import { selfServeTrackSuccess } from 'common/utils/selfServeAnalytics';

function hideWebhookType(isConfigTagEnabled) {
  return {
    payment: isConfigTagEnabled('webhooks.payment'),
    order: isConfigTagEnabled('webhooks.order'),
    invoice: isConfigTagEnabled('webhooks.invoices'),
    subscription: isConfigTagEnabled('webhooks.subscriptions'),
    fund_account: isConfigTagEnabled('webhooks.fund_account'),
    refund: isConfigTagEnabled('webhooks.refunds'),
    payment_link: isConfigTagEnabled('webhooks.payment_links'),
  };
}

class webhookForm extends Component {
  state = {
    errors: null,
    showSecret: false,
    searchEventsQuery: '',
    allWebhooks: [],
    groupedWebhooks: null,
    filterGroupedWebhooks: null,
    isSecretPresent: false,
    eventsError: false,
  };

  UNSAFE_componentWillReceiveProps(nextProps) {
    let eventValues = {};
    let eventGroupValues = {};

    if (nextProps.webhookFormData.values) {
      eventValues = { ...nextProps.webhookFormData.values.events };
      eventGroupValues = { ...nextProps.webhookFormData.values.eventGroup };
    }

    if (this.state.filterGroupedWebhooks) {
      Object.entries(this.state.filterGroupedWebhooks).forEach((webhook) => {
        const [eventGroup, events] = webhook;
        let checkGroup = true;
        Object.entries(eventValues).forEach((eventEntry) => {
          const [evName, evValue] = eventEntry;
          if (evName.includes(eventGroup) && events.includes(evName)) {
            if (!evValue) checkGroup = false;
          }
        });
        eventGroupValues[eventGroup] = checkGroup;
      });

      this.props.change('eventGroup', eventGroupValues);
    }
  }

  UNSAFE_componentWillMount() {
    const {
      webhook,
      userData,
      i18: { isConfigTagEnabled },
    } = this.props;

    if (webhook) {
      if (webhook.secret_exists) {
        this.setState({ isSecretPresent: true });
      }

      const userMerchantTxnMails = userData.merchant.transaction_report_email;
      const userTxnReportEmail =
        Array.isArray(userMerchantTxnMails) && userMerchantTxnMails.length > 0
          ? userMerchantTxnMails[0]
          : userMerchantTxnMails;

      this.props.initialize({
        url: webhook.url,
        alert_email: webhook.alert_email ? webhook.alert_email : userTxnReportEmail,
        secret_exists: webhook.secret_exists,
        eventGroup: {},
        events: {},
      });
    }

    merchantFetch('webhooks/events/all')
      .then((resp) => {
        if (resp.success && resp.data) {
          const allWebhooks = resp.data;
          const events = resp.data.reduce((_events, rawEvent) => {
            const [eventGroup] = rawEvent.split('.');
            return {
              ..._events,
              [eventGroup]: [...(_events[eventGroup] || []), rawEvent],
            };
          }, {});

          // Remove merchant un-supported webhooks
          Object.keys(events).forEach((eventGroupKey) => {
            const HIDE_WEBHOOK_TYPE = hideWebhookType(isConfigTagEnabled);
            const webhookType = HIDE_WEBHOOK_TYPE[eventGroupKey];
            const i18TagFound = webhookType;
            if (i18TagFound) {
              delete events[eventGroupKey];
            }
            if (
              eventGroupKey === 'payment' &&
              isConfigTagEnabled('webhooks.downtime_payment_events')
            ) {
              this.removePaymentDowntimeEvents(events);
            }
          });
          this.setState(
            {
              groupedWebhooks: events,
              filterGroupedWebhooks: events,
              allWebhooks,
            },
            () => {
              this.initializeEventValues();
            },
          );
        } else {
          this.setState({ groupedWebhooks: [], filterGroupedWebhooks: [] });
        }
      })
      .catch((err) => {
        this.props.showNotification({
          type: 'error',
          message: err,
        });
      });
  }

  removePaymentDowntimeEvents = (events) => {
    if (events?.payment && Array.isArray(events.payment) && events.payment.length > 0) {
      const paymentEvents = events.payment.filter((event) => !event.includes('downtime'));
      events.payment = paymentEvents;
    }
  };

  initializeEventValues = (clearAll) => {
    const { webhook } = this.props;
    const currentValues = {};

    const activeEvents = [];
    if (webhook) {
      Object.keys(webhook.events).forEach((key) => {
        if (webhook.events[key] === true) {
          activeEvents.push(key);
        }
      });
    }

    this.props.initialize({
      ...currentValues,
      ...this.props.webhookFormData.values,
      events: this.state.allWebhooks.reduce((acc, event) => {
        if (activeEvents.includes(event) && !clearAll) {
          acc[event] = true;
        } else {
          acc[event] = false;
        }
        return acc;
      }, {}),
    });
  };

  validateUrl = (value) => {
    const { webhookList } = this.props;
    if (webhookList) {
      const existingUrlList = [];
      webhookList.forEach((item) => {
        existingUrlList.push(item.url);
      });

      return existingUrlList.includes(value) ? 'Webhook URL already exists' : undefined;
    }
    return null;
  };

  save = (formData) => {
    // if no events selected throw an error and do not save the form
    const { webhookFormData, webhookList } = this.props;
    let noOfEventsSelected = 0;
    if (webhookFormData && webhookFormData.values) {
      noOfEventsSelected = Object.values(webhookFormData.values.events).reduce(
        (counter, eventValue) => {
          if (eventValue) ++counter;
          return counter;
        },
        noOfEventsSelected,
      );
    }

    if (noOfEventsSelected === 0) {
      this.setState({
        eventsError: true,
      });
    } else {
      const { appId, mode } = this.props;
      const { webhook } = this.props;
      const { userData } = this.props;
      const tracking = this.props.tracking;

      let editedWebhookData = {};
      let difference = {};
      if (webhook) {
        editedWebhookData = {
          ...webhook,
          alert_email: formData.alert_email,
          url: formData.url,
          secret: formData.secret,
          events: formData.events,
        };

        // Have the same keys on both old and new data, remove unneccesary keys for better comparision
        const requiredKeys = ['url', 'secret', 'alert_email', 'events'];
        const oldData = {};
        const newData = {};

        Object.keys(webhook).forEach((key) => {
          if (requiredKeys.includes(key)) {
            oldData[key] = webhook[key];
          }
        });

        Object.keys(formData).forEach((key) => {
          if (requiredKeys.includes(key)) {
            newData[key] = formData[key];
          }
        });

        // compare the object and find difference
        const diff = (obj1, obj2) => {
          const result = {};
          for (const key in obj1) {
            if (obj1.hasOwnProperty(key)) {
              if (obj2[key] != obj1[key]) result[key] = obj2[key];
              if (!(key in obj2) && obj1[key] != undefined) {
                result[key] = obj1[key];
              }
              if (typeof obj2[key] == 'object' && typeof obj1[key] == 'object') {
                result[key] = diff(obj1[key], obj2[key]);
                if (Object.keys(result[key]).length === 0) delete result[key];
              }
            }
          }
          return result;
        };

        difference = diff(newData, oldData);
      }

      if (
        formData.alert_email &&
        formData.alert_email === userData.merchant.transaction_report_email
      ) {
        delete formData.alert_email;
      }

      const data = webhook ? editedWebhookData : formData;

      let submitWebhook = {};
      if (appId) {
        submitWebhook = this.props.webhook
          ? editAppWebhook({ appId, data, mode })
          : createAppWebhook({ appId, data, mode });
      } else {
        submitWebhook = this.props.saveWebhook(data);
      }

      analyticsTrack({
        objectName: `${this.props.webhook ? 'edit' : 'add'} webhooks popup`,
        actionName: 'clicked',
        screen: 'settings',
        properties: {
          location: 'webhooks',
          actionName: 'save',
          webhookUrl: data.url,
          activeEventsStatus: data.events,
          ...getCommonAnalyticsProperties(window.rzp_user),
        },
      });

      return submitWebhook
        .then((resp) => {
          this.props.onSave(resp);
          this.props.closeModal();
          this.props.showNotification({
            type: 'success',
            message: 'Webhook saved successfully',
          });
          if (webhook) {
            tracking.trackEvent(
              window.rzpQ.merchantActions().success('Webhook.editCompleted', {
                webhook_id: webhook.id,
                changes_made: difference,
              }),
            );
          } else {
            tracking.trackEvent(
              window.rzpQ.merchantActions().success('Webhook.setupCompleted', {
                secret: data.secret,
                alert_email: data.alert_email || '',
                webhook_count: webhookList.length || '',
              }),
            );
          }
          if (webhook) {
            selfServeTrackSuccess({
              selfServeAction: 'Webhook Edited',
              page: 'Webhooks',
              screen: 'Settings',
            });
          }
          analyticsTrack({
            objectName: `${webhook ? 'edit' : 'add'} webhooks`,
            actionName: 'result',
            screen: 'settings',
            properties: {
              location: 'webhooks',
              status: 'Success',
              ...getCommonAnalyticsProperties(window.rzp_user),
            },
          });
        })
        .catch((err) => {
          this.setState({
            errors: err.errors,
          });
          if (webhook) {
            tracking.trackEvent(
              window.rzpQ.merchantActions().failed('Webhook.editValidationError', {
                errors: err.errors,
              }),
            );
          } else {
            tracking.trackEvent(
              window.rzpQ.merchantActions().failed('Webhook.setupValidationError', {
                errors: err.errors,
              }),
            );
          }

          analyticsTrack({
            objectName: `${webhook ? 'edit' : 'add'} webhooks`,
            actionName: 'result',
            screen: 'settings',
            properties: {
              location: 'webhooks',
              status: 'Failure',
              failureReason: err.errors[0],
              ...getCommonAnalyticsProperties(window.rzp_user),
            },
          });
        });
    }
    return null;
  };

  toggleVisibility = () => {
    this.setState((prevState) => {
      return { showSecret: !prevState.showSecret };
    });
  };

  filterWebhooks = (searchEventsQuery) => {
    const { groupedWebhooks } = this.state;

    if (groupedWebhooks) {
      const newGroupedWebhooks = {};
      Object.entries(groupedWebhooks).forEach((webhook) => {
        const [eventGroup, events] = webhook;
        const filteredEvents = events.filter((event) => event.includes(searchEventsQuery));
        if (filteredEvents.length) {
          newGroupedWebhooks[eventGroup] = events.filter((event) =>
            event.includes(searchEventsQuery),
          );
        }
      });
      this.setState({
        filterGroupedWebhooks: newGroupedWebhooks,
        searchEventsQuery,
      });
    }
  };

  render() {
    const { handleSubmit, webhookFormData, webhook, userData } = this.props;
    const { filterGroupedWebhooks, searchEventsQuery, isSecretPresent } = this.state;

    let noOfEventsSelected = 0;
    if (webhookFormData?.values?.events) {
      noOfEventsSelected = Object.values(webhookFormData.values.events).reduce(
        (counter, eventValue) => {
          if (eventValue) ++counter;
          return counter;
        },
        noOfEventsSelected,
      );
    }

    let noOfEvents = 0;
    if (filterGroupedWebhooks) {
      noOfEvents = Object.values(filterGroupedWebhooks).reduce(
        (eventsCount, events) => eventsCount + events.length,
        noOfEvents,
      );
    }

    return (
      <div>
        <ModalHeader
          title="Webhook Setup"
          onCloseClick={() => {
            analyticsTrack({
              objectName: `${webhook ? 'edit' : 'add'} webhooks popup`,
              actionName: 'clicked',
              screen: 'settings',
              properties: {
                location: 'webhooks',
                actionName: 'close',
                ...getCommonAnalyticsProperties(window.rzp_user),
              },
            });
            this.props.closeModal();
          }}
        />

        <form className="form-horizontal" onSubmit={handleSubmit(this.save)}>
          <div className="modal-body">
            <Alert type="error" message={this.state.errors} />
            <div className="form-group">
              <label className="col-md-3 control-label label-required">Webhook URL</label>
              <div className="col-md-9">
                <Field
                  name="url"
                  component={InputField}
                  className="form-control"
                  autoFocus={true}
                  validate={[required(), this.validateUrl]}
                  placeholder="HTTPS URL is recommended"
                  data-testid="webhook-url"
                />
              </div>
            </div>

            <div className="form-group">
              <label className="col-md-3 control-label">Secret</label>
              <div className="col-md-9">
                {isSecretPresent ? (
                  <div className="webhooks-secret-container">
                    <InputField
                      value="Secret was provided during webhook setup"
                      className="form-control"
                      disabled
                    />
                    <button
                      type="button"
                      className="btn btn-link secret-label"
                      onClick={() => this.setState({ isSecretPresent: false })}
                    >
                      Change Secret
                    </button>
                  </div>
                ) : (
                  <Field
                    name="secret"
                    type={this.state.showSecret ? 'text' : 'password'}
                    component={InputField}
                    className="form-control"
                    autoComplete="new-password"
                  />
                )}
                <DocsLink
                  title="We strongly recommend adding secrets for security"
                  url="https://razorpay.com/docs/webhooks/#validation"
                  style={{ fontSize: '12px', padding: '0' }}
                />
                {!isSecretPresent ? (
                  <button
                    type="button"
                    className="btn btn-link"
                    onClick={this.toggleVisibility}
                    style={{ fontSize: '12px', padding: '0', float: 'right' }}
                    data-testid="toggle-secret"
                  >
                    {this.state.showSecret ? 'Hide Secret' : 'Show Secret'}
                  </button>
                ) : null}
              </div>
            </div>

            <div className="form-group">
              <label className="col-md-3 control-label">Alert Email</label>
              <div className="col-md-9">
                <Field
                  name="alert_email"
                  component={InputField}
                  type="email"
                  className="form-control"
                  validate={email('Alert email must be a single valid email address')}
                  placeholder={userData.merchant.transaction_report_email}
                />
                <div className="help-block" style={{ margin: '0px' }}>
                  <small>Receive email alerts for webhook failures</small>
                </div>
              </div>
            </div>

            <div className="form-group">
              <label className="col-md-3 control-label label-required">Active Events</label>
              <div className="col-md-9">
                <div className="search-input-container" style={{ position: 'relative' }}>
                  <Field
                    name="search"
                    component={InputField}
                    type="input"
                    className="form-control"
                    placeholder="Search"
                    value={searchEventsQuery}
                    onChange={(e) => this.filterWebhooks(e.target.value)}
                    data-testid="webhook-events-search"
                  />
                  {searchEventsQuery ? (
                    <div
                      style={{
                        position: 'absolute',
                        zIndex: 1,
                        right: '13px',
                        top: '8px',
                        display: 'flex',
                      }}
                    >
                      <p style={{ fontSize: '12px', color: '#838383' }}>
                        {noOfEvents} search results
                      </p>
                      <i
                        onClick={() => {
                          this.filterWebhooks('');
                          this.props.change('search', '');
                        }}
                        className="i i-close"
                        style={{
                          cursor: 'pointer',
                          color: '#C8C8C8',
                          marginTop: '4px',
                          marginLeft: '8px',
                        }}
                        data-testid="webhook-events-search-close"
                      />
                    </div>
                  ) : null}
                </div>
                <div
                  className="col-md-9 webhooks-events-container"
                  style={{
                    display: 'flex',
                    flexDirection: 'column',
                    height: '230px',
                    overflow: 'auto',
                    background: 'white',
                    width: '100%',
                    border:
                      noOfEventsSelected === 0 && this.state.eventsError
                        ? ' 1px solid #f05050'
                        : '1px solid #D1DADD',
                  }}
                >
                  {!filterGroupedWebhooks ? (
                    <div className="page-spinner-container" data-testid="webhook-events-spinner">
                      <Spinner />
                    </div>
                  ) : Object.keys(filterGroupedWebhooks).length ? (
                    Object.entries(filterGroupedWebhooks).map((_webhook) => {
                      const [eventGroup, events] = _webhook;
                      return (
                        <React.Fragment key={eventGroup}>
                          <div className="checkbox eventGroup">
                            <label>
                              <Field
                                data-testid={`eventGroup['${eventGroup}']`}
                                name={`eventGroup['${eventGroup}']`}
                                component="input"
                                type="checkbox"
                                onChange={(e, v) => {
                                  let eventValues = {};
                                  if (webhookFormData.values && webhookFormData.values.events) {
                                    eventValues = {
                                      ...webhookFormData.values.events,
                                    };
                                  }
                                  filterGroupedWebhooks[eventGroup].forEach((event) => {
                                    eventValues[event] = !!v;
                                  });
                                  this.props.change('events', eventValues);
                                }}
                              />
                              {eventGroup} Events
                            </label>
                          </div>
                          {events.map((eventName) => (
                            <div key={eventName} className="checkbox event">
                              <label>
                                <Field
                                  name={`events['${eventName}']`}
                                  component="input"
                                  type="checkbox"
                                />
                                {searchEventsQuery.length > 0 ? (
                                  <p
                                    dangerouslySetInnerHTML={{
                                      __html: sanitizer(
                                        eventName.replace(
                                          new RegExp(searchEventsQuery, 'gi'),
                                          (match) => `<b style="background: #FEFFDE;">${match}</b>`,
                                        ),
                                      ),
                                    }}
                                  />
                                ) : (
                                  eventName
                                )}
                              </label>
                            </div>
                          ))}
                          <div className="eventGroup-divider" />
                        </React.Fragment>
                      );
                    })
                  ) : (
                    <span style={{ paddingTop: '10px' }}>No Webhooks available</span>
                  )}
                </div>
                <div
                  className="col-md-9"
                  style={{
                    display: 'flex',
                    flexDirection: 'row',
                    justifyContent: 'space-between',
                    border: '1px solid #D1DADD',
                    background: 'white',
                    width: '100%',
                    padding: 0,
                  }}
                >
                  <span style={{ fontSize: '12px', padding: '8px 14px' }}>
                    {noOfEventsSelected || 'No'} {noOfEventsSelected > 1 ? 'events' : 'event'}{' '}
                    selected
                  </span>

                  <button
                    type="button"
                    className="btn btn-link"
                    onClick={this.initializeEventValues}
                    style={{ fontSize: '12px', paddingRight: '14px' }}
                  >
                    Clear all
                  </button>
                </div>
                {noOfEventsSelected === 0 && this.state.eventsError && (
                  <div
                    style={{
                      fontSize: '11px',
                      textAlign: 'right',
                      color: '#f05050',
                    }}
                  >
                    {' '}
                    Required{' '}
                  </div>
                )}
                <DocsLink
                  title="Know more about the events"
                  url="https://razorpay.com/docs/webhooks/webhook-payloads/"
                  style={{ fontSize: '12px', padding: '0' }}
                />
              </div>
            </div>
          </div>

          <div className="modal-footer">
            <button
              type="button"
              className="btn btn-default"
              onClick={() => {
                analyticsTrack({
                  objectName: `${webhook ? 'edit' : 'add'} webhooks popup`,
                  actionName: 'clicked',
                  screen: 'settings',
                  properties: {
                    location: 'webhooks',
                    actionName: 'cancel',
                    ...getCommonAnalyticsProperties(window.rzp_user),
                  },
                });
                this.props.closeModal();
              }}
            >
              Cancel
            </button>

            <AsyncButton
              type="submit"
              className="btn btn-primary"
              text={webhook ? 'Save Webhook' : 'Create Webhook'}
              pendingText="Saving..."
              onClick={handleSubmit(this.save)}
            />
          </div>
        </form>
      </div>
    );
  }
}

webhookForm.defaultProps = {
  onSave: () => {},
};

export default compose(
  connect(
    (state) => ({
      userData: state.session.user,
      webhookFormData: state.form.webhookForm,
    }),
    { saveWebhook, ...ModalActions, ...NotificationsActions },
  ),
  reduxForm({
    form: 'webhookForm',
    onSubmitFail: (errros) => {
      window.rzpQ.push(
        window.rzpQ.merchantActions().failed('Webhook.validationError', {
          errros,
        }),
      );
    },
  }),
  // eslint-disable-next-line babel/new-cap
  RTracking(() => window.rzpQ.component('WebhooksContainer')),
)(withI18Service(webhookForm));
