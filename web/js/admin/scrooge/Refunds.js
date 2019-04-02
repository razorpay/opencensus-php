import React, { Component } from 'react';
import { withRouter, Link } from 'react-router-dom';
import { PageTable } from 'ui/Table';
import Form, { serialize } from 'ui/Form';
import AsyncButton from 'ui/AsyncButton';
import Field, {
  FromField,
  ToField,
  SelectField,
  SelectMode,
  CheckField,
} from 'ui/Field';
import MultiSelectField from 'ui/MultiSelectField';
import Collection from 'model/collection';
import { adminPost, adminFetch } from 'common/fetch';
import {
  formatDate,
  getFormattedAmount,
  getSearchParams,
  intersect,
} from 'common/util';
import { ModalContent } from 'component/Modal';
import { openModal, notifySuccess, notifyError } from 'common/modal';

import ShowWhen from 'admin/components/ShowWhen';
import NavBar from 'admin/scrooge/NavBar';
import { bulkUpdateRefundStatus } from 'admin/scrooge/util';
import { updateStatusEvents } from 'admin/scrooge/constants';

@withRouter
export default class Refunds extends Component {
  constructor(props) {
    super(props);

    this.mode = this.props.match.params.mode || 'live';

    this.state = {
      isLoading: true,
      selectedRefunds: [],
    };

    this.params = this.getQueryParams();

    this.multiSelectInitialValues = {
      'refunds.status': statuses,
    };

    adminFetch(`${this.mode}/admin/entities/all`)
      .then(data => {
        if (!data) {
          return;
        }

        let gatewayValues = data.fields.gateway.values || [];
        let methodValues = data.fields.method.values || {};
        let gatewayAcquirerValues =
          data.entities.terminal.gateway_acquirer.values || [];
        let gateways = [];
        let methods = [];
        let gatewayAcquirers = [];

        gatewayValues.forEach(gateway => {
          gateways.push({
            name: gateway,
            value: gateway,
          });
        });

        this.multiSelectInitialValues['refunds.gateway'] = gateways;

        for (let methodValue in methodValues) {
          methods.push({
            name: methodValues[methodValue],
            value: methodValue,
          });
        }

        this.multiSelectInitialValues['refunds.method'] = methods;

        gatewayAcquirerValues.forEach(gatewayAcquirer => {
          gatewayAcquirers.push({
            name: gatewayAcquirer,
            value: gatewayAcquirer,
          });
        });

        this.multiSelectInitialValues[
          'refunds.gateway_acquirer'
        ] = gatewayAcquirers;
      })
      .then(d => {
        let params = this.params;

        let filterQueryData = {};

        for (let tableName in params) {
          if (params.hasOwnProperty(tableName)) {
            let columns = params[tableName];

            for (let columnName in columns) {
              if (columns.hasOwnProperty(columnName)) {
                let key = tableName + '.' + columnName;

                let keySplit = key.split('.');

                keySplit.reduce((filterQueryData, i) => {
                  filterQueryData[i] = filterQueryData[i] || {};
                  return filterQueryData[i];
                }, filterQueryData);

                if (this.multiSelectInitialValues.hasOwnProperty(key)) {
                  let commonValues = intersect(
                    this.multiSelectInitialValues[key].map(x => x.value),
                    columns[columnName]
                  );
                  this.defaultValues[key] = this.multiSelectInitialValues[
                    key
                  ].filter(x => commonValues.indexOf(x.value) > -1);
                  filterQueryData[tableName][columnName] = this.defaultValues[
                    key
                  ].map(x => x.value);
                } else {
                  this.defaultValues[key] = columns[columnName];
                  filterQueryData[tableName][columnName] = this.defaultValues[
                    key
                  ];
                }
              }
            }
          }
        }

        this.collection = new Collection({
          data: {
            url: `${this.mode}/scrooge/refunds`,
            data: {
              query: filterQueryData,
            },
          },
          extraFields: {
            mode: this.mode,
          },
          fetchFn: data =>
            adminPost({ ...data }).then(d => {
              this.setState({
                isLoading: false,
              });
              return d.data || [];
            }),
        });
      });

    this.defaultValues = {};

    this.onRefundModeChange = this.onRefundModeChange.bind(this);
    this.onSubmit = this.onSubmit.bind(this);
    this.resetForm = this.resetForm.bind(this);
    this.handleSelectAll = this.handleSelectAll.bind(this);
    this.handleSelection = this.handleSelection.bind(this);
    this._getFields = this._getFields.bind(this);
  }

  _getFields = () => {
    const { selectedRefunds } = this.state;

    return [
      [
        <CheckField defaultChecked={false} onChange={this.handleSelectAll} />,
        item => (
          <CheckField
            data-refundid={item.id}
            checked={selectedRefunds.indexOf(item.id) > -1}
            onChange={this.handleSelection}
          />
        ),
      ],
      ['Refund ID', item => refundLink(item, this.mode)],
      ['Payment ID', item => paymentLink(item, this.mode)],
      ['Merchant ID', item => item.merchant_id],
      ['Status', item => item.status],
      ['Gateway', item => item.gateway],
      ['Refund Gateway', item => item.refund_gateway],
      ['Method', item => item.method],
      ['Attempts', item => item.attempts],
      ['Refund Amount', item => showAmount(item.currency, item.amount)],
      [
        'Payment Amount',
        item => showAmount(item.currency, item.payment_amount),
      ],
      ['Gateway Acquirer', item => item.gateway_acquirer],
      ['Refund Created At', item => formatDate(item.created_at)],
      ['Payment Created At', item => formatDate(item.payment_created_at)],
    ];
  };

  handleSelectAll = event => {
    let selectedRefunds = [];

    if (event.target.checked) {
      this.collection.items.map(item => selectedRefunds.push(item.id));
    }

    this.setState({ selectedRefunds });
  };

  handleSelection = event => {
    const selectedRefunds = [...this.state.selectedRefunds];
    const refundId = event.target.dataset.refundid;
    const index = selectedRefunds.indexOf(refundId);

    if (index > -1) {
      selectedRefunds.splice(index, 1);
    } else {
      selectedRefunds.push(refundId);
    }

    this.setState({ selectedRefunds });
  };

  getQueryParams = () => {
    let rawParams = getSearchParams();

    for (var key in rawParams) {
      rawParams[key] = JSON.parse(rawParams[key]);
    }

    return rawParams;
  };

  onRefundModeChange = e => {
    this.collection.extraFields.mode = this.mode = e.target.value;
  };

  setCollectionData = filters => {
    filters = parseFilters(filters);

    if (filters !== null) {
      this.collection.data.url = `${filters.mode}/scrooge/refunds`;
      delete filters['mode'];

      this.collection.data.data.query = this.collection.extraFields.query = filters;
    }
  };

  onSubmit = filters => {
    this.setCollectionData(filters);

    this.setState({ selectedRefunds: [] });

    return this.collection.fetch();
  };

  resetForm = () => {
    window.location = window.location.pathname;
  };

  onDownloadAllClick = filters => {
    this.setCollectionData(filters);

    adminPost({
      url: `${this.collection.extraFields.mode ||
        'live'}/scrooge/refunds/download`,
      data: {
        query: this.collection.extraFields.query,
      },
    }).then(d => {
      if (d.link !== '') {
        window.open(d.link);
      } else {
        notifyError('Unable to download data');
      }
    });
  };

  onDownloadGatewayFile = filters => {
    this.setCollectionData(filters);

    adminPost({
      url: `${this.collection.extraFields.mode ||
        'live'}/scrooge/refunds/download-gateway-file`,
      data: {
        query: this.collection.extraFields.query,
      },
    }).then(d => {
      if (d.link !== '') {
        window.open(d.link);
      } else {
        notifyError('Unable to download data');
      }
    });
  };

  onDownloadSelectedClick = () => {
    const selectedRefunds = [...this.state.selectedRefunds];

    return adminPost({
      url: `${this.mode}/scrooge/refunds/download`,
      data: {
        query: {
          refunds: {
            id: selectedRefunds,
          },
        },
      },
    }).then(d => {
      if (d.link !== '') {
        window.open(d.link);
      } else {
        notifyError('Unable to download data');
      }
    });
  };

  statusModal = () => {
    if (!updateStatusEvents || !updateStatusEvents.length) {
      return notifyError('No status updates available');
    }

    const selectedRefunds = [...this.state.selectedRefunds];

    openModal(
      <UpdateStatusModal
        mode={this.mode}
        selectedRefunds={selectedRefunds}
        availableEvents={updateStatusEvents}
      />
    );
  };

  render() {
    let selectedDisabledClass =
      this.state.selectedRefunds.length <= 0 ? ' disabled' : '';

    return (
      <div class="list-container refunds entity-container">
        <NavBar active="refunds" />
        {this.state.isLoading ? (
          <div class="spinner center" />
        ) : (
          <div>
            <div className="box">
              <header>Refunds</header>
              <Form
                name="refunds-search"
                id="refunds-search-form"
                class="filters"
              >
                <SelectMode
                  name="mode"
                  onChange={this.onRefundModeChange}
                  id="refunds-mode"
                  defaultValue={this.mode}
                />

                {formFilters.map((filter, index) => {
                  if (filter.type === 'multi-entity') {
                    return (
                      <Field
                        key={index}
                        class={'multi-value'}
                        label={filter.name}
                        name={filter.formKey}
                        placeholder="comma separated"
                        defaultValue={
                          this.defaultValues[filter.formKey]
                            ? this.defaultValues[filter.formKey].join(',')
                            : ''
                        }
                      />
                    );
                  } else if (filter.type === 'multi-select') {
                    return (
                      <MultiSelectField
                        key={index}
                        class={filter.formKey + '-select multi-value'}
                        label={filter.name}
                        name={filter.formKey}
                        options={
                          this.multiSelectInitialValues[filter.formKey] || []
                        }
                        defaultValue={this.defaultValues[filter.formKey]}
                        trackBy="value"
                        keys={['name']}
                      />
                    );
                  } else if (filter.type === 'numeric-range') {
                    return (
                      <div key={index}>
                        <Field
                          label={filter.name + ' >='}
                          name={filter.formKey + ';gte'}
                          component="input"
                          min={0}
                          type="number"
                          defaultValue={
                            this.defaultValues[filter.formKey] &&
                            this.defaultValues[filter.formKey].gte
                              ? this.defaultValues[filter.formKey].gte
                              : ''
                          }
                        />

                        <Field
                          label={filter.name + ' <='}
                          name={filter.formKey + ';lte'}
                          component="input"
                          min={0}
                          type="number"
                          defaultValue={
                            this.defaultValues[filter.formKey] &&
                            this.defaultValues[filter.formKey].lte
                              ? this.defaultValues[filter.formKey].lte
                              : ''
                          }
                        />
                      </div>
                    );
                  } else if (filter.type === 'date-range') {
                    return (
                      <div key={index}>
                        <FromField
                          label={filter.name + ' From'}
                          format="X"
                          allowToday={true}
                          name={filter.formKey + ';gte'}
                          defaultValue={
                            this.defaultValues[filter.formKey] &&
                            this.defaultValues[filter.formKey].gte
                              ? moment
                                  .unix(this.defaultValues[filter.formKey].gte)
                                  .utc()
                              : null
                          }
                        />

                        <ToField
                          label={filter.name + ' To'}
                          format="X"
                          allowToday={true}
                          name={filter.formKey + ';lt'}
                          defaultValue={
                            this.defaultValues[filter.formKey] &&
                            this.defaultValues[filter.formKey].lt
                              ? moment
                                  .unix(this.defaultValues[filter.formKey].lt)
                                  .utc()
                              : null
                          }
                        />
                      </div>
                    );
                  }
                })}

                {/*
                // Take this live when recon API is live
                <SelectField
                  label="Reconciled"
                  name="reconciled"
                  defaultValue={"all"}
                >
                  <option value="all">All</option>
                  <option value="yes">Yes</option>
                  <option value="no">No</option>
                </SelectField>

                */}

                <div class="action-btns">
                  <AsyncButton
                    text="Go"
                    class="btn btn-go"
                    pendingClass="small spinner"
                    onSubmit={this.onSubmit}
                  />
                  <AsyncButton
                    class="btn btn-default"
                    onClick={this.resetForm}
                    text="Clear"
                  />
                  <AsyncButton
                    text="Download All"
                    class="link"
                    pendingClass="link disabled"
                    onSubmit={this.onDownloadAllClick}
                  />
                  <AsyncButton
                    text="Download Gateway File"
                    class="link"
                    pendingClass="link disabled"
                    onSubmit={this.onDownloadGatewayFile}
                  />
                </div>
              </Form>
            </div>

            <div className="box bulk-actions clearfix">
              <ShowWhen permission="edit_refund">
                <AsyncButton
                  text="Update Selected"
                  className={
                    'btn btn-bulk-update-status pull-right' +
                    selectedDisabledClass
                  }
                  pendingClass="btn btn-bulk-update-status pull-right"
                  onClick={
                    this.state.selectedRefunds.length <= 0
                      ? () => {}
                      : this.statusModal
                  }
                />
              </ShowWhen>

              <AsyncButton
                text="Download Selected"
                className={
                  'download-selected pull-right link' + selectedDisabledClass
                }
                pendingClass="download-selected pull-right link"
                onSubmit={
                  this.state.selectedRefunds.length <= 0
                    ? () => {}
                    : this.onDownloadSelectedClick
                }
              />
            </div>

            <PageTable
              customClass="refunds-list"
              model={this.collection}
              fields={this._getFields()}
            />
          </div>
        )}
      </div>
    );
  }
}

class UpdateStatusModal extends Component {
  constructor(props) {
    super(props);

    this.availableEvents = this.props.availableEvents || [];

    this.updateStatus = this.updateStatus.bind(this);
  }

  updateStatus = data => {
    if (!data.event) {
      return;
    }

    const selectedRefunds = [...this.props.selectedRefunds];

    bulkUpdateRefundStatus(selectedRefunds, data.event, this.props.mode);
  };

  render() {
    return (
      <ModalContent header="Update Status">
        <Form onSubmit={this.updateStatus}>
          <SelectField name="event" label="Event">
            <option key="0" value="">
              Select status to update
            </option>
            {this.availableEvents.map((e, i) => (
              <option key={i + 1} value={e}>
                {e}
              </option>
            ))}
          </SelectField>
          <button>Update</button>
        </Form>
      </ModalContent>
    );
  }
}

const refundLink = (item, mode) =>
  item.id && (
    <Link
      class="link"
      to={`/scrooge/refund/${mode}/${item.id}`}
      target="_blank"
    >
      {item.id}
    </Link>
  );

const paymentLink = (item, mode) =>
  item.payment_id && (
    <Link
      class="link"
      to={`/entity/payment/${mode}/pay_${item.payment_id}`}
      target="_blank"
    >
      {item.payment_id}
    </Link>
  );

const showAmount = (currency, amount) => currency + ' ' + amount;

const parseFilters = filters =>
  filters &&
  Object.keys(filters).reduce((accumulator, currentValue) => {
    let semiColonSplit = currentValue.split(';');

    let filter = formFilters.find(f => f.formKey === currentValue);

    let queryKey = semiColonSplit[0];
    let queryKeySplit = queryKey.split('.');

    queryKeySplit.reduce((filters, i) => {
      filters[i] = filters[i] || {};
      return filters[i];
    }, filters);

    let value = getFilterValue(filters, filter, currentValue, semiColonSplit);

    if (queryKeySplit.length === 1) {
      let gatewayKeys = filters['gateway_keys'] || {};

      switch (queryKey) {
        case 'internal_error_code':
          gatewayKeys['name'] = 'internal_error_code';
          gatewayKeys['value'] = value;

          break;

        case 'gateway_error_code':
          gatewayKeys['name'] = 'gateway_error_code';
          gatewayKeys['value'] = value;

          break;

        default:
          accumulator[queryKey] = value;
      }

      if (Object.keys(gatewayKeys).length !== 0) {
        accumulator['gateway_keys'] = gatewayKeys;
      }
    } else {
      if (!accumulator.hasOwnProperty(queryKeySplit[0])) {
        accumulator[queryKeySplit[0]] = {};
      }

      accumulator[queryKeySplit[0]][queryKeySplit[1]] = value;
    }

    return accumulator;
  }, {});

const getFilterValue = (filters, filter, currentValue, semiColonSplit) => {
  let value;
  let multiValue = false;

  if (
    filter &&
    (filter.type === 'multi-entity' || filter.type === 'multi-select')
  ) {
    multiValue = true;
  }

  if (semiColonSplit.length > 1) {
    let nestedFilter =
      (filters[semiColonSplit[0]] && JSON.parse(filters[semiColonSplit[0]])) ||
      {};

    nestedFilter[semiColonSplit[1]] = filters[currentValue];

    value = Object.assign(filters[semiColonSplit[0]] || {}, nestedFilter);
  } else {
    value = filters[currentValue];

    if (multiValue === true) {
      value = filters[currentValue].split(',').map(e => e.trim());
    }
  }

  return value;
};

const statuses = [
  { name: 'Init', value: 'init' },
  { name: 'File Init', value: 'file_init' },
  { name: 'Attempt Failed', value: 'attempt_failed' },
  { name: 'Processed', value: 'processed' },
  { name: 'On Hold', value: 'on_hold' },
];

// multi-entity, multi-select, select, entity, numeric-range, date-range
const formFilters = [
  {
    name: 'Refund ID(s)',
    formKey: 'refunds.id',
    type: 'multi-entity',
  },
  {
    name: 'Merchant ID(s)',
    formKey: 'refunds.merchant_id',
    type: 'multi-entity',
  },
  {
    name: 'Payment ID(s)',
    formKey: 'refunds.payment_id',
    type: 'multi-entity',
  },
  {
    name: 'Gateway(s)',
    formKey: 'refunds.gateway',
    type: 'multi-select',
  },
  {
    name: 'Gateway Acquirer(s)',
    formKey: 'refunds.gateway_acquirer',
    type: 'multi-select',
  },
  {
    name: 'Method(s)',
    formKey: 'refunds.method',
    type: 'multi-select',
  },
  {
    name: 'Status(es)',
    formKey: 'refunds.status',
    type: 'multi-select',
  },
  {
    name: 'Refund Amount',
    formKey: 'refunds.amount',
    type: 'numeric-range',
  },
  {
    name: 'Attempts',
    formKey: 'refunds.attempts',
    type: 'numeric-range',
  },
  {
    name: 'Refund Date',
    formKey: 'refunds.created_at',
    type: 'date-range',
  },
  {
    name: 'Payment Date',
    formKey: 'refunds.payment_created_at',
    type: 'date-range',
  },
  {
    name: 'Internal Error Code',
    formKey: 'internal_error_code',
    type: 'multi-entity',
  },
  {
    name: 'Gateway Error Code',
    formKey: 'gateway_error_code',
    type: 'multi-entity',
  },
];
