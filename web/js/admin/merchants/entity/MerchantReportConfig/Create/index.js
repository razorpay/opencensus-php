import { Component } from 'react';
import { observer } from 'mobx-react';

import { ModalContent } from 'component/Modal';
import { adminFetch } from 'common/fetch';

import TabsContainer from 'ui/Tabs';
import EntityRow from 'ui/EntityRow';
import Form from 'ui/Form';
import AsyncButton from 'ui/AsyncButton';

import Model from '../../model';

import ConfigDetails from './ConfigDetails';
import DataDetails from './DataDetails';
// import FieldDetails from './FieldDetails';

@observer
export default class CreateMerchantReportConfig extends Component {
  state = {
    configOptions: {
      loading: true,
      data: {},
    },
    configComponents: {
      loading: false,
    },
    values: {},
  };
  constructor(props) {
    super();

    this.model = new Model({
      fetchFn: adminFetch,
      merchantId: props.merchantId,
    });
  }

  componentWillMount() {
    fetchConfigOptions().then(({ file: data }) => {
      this.setState({ configOptions: { loading: false, data } });
    });
  }

  handleSubmitClick = () => {
    const data = this.state.values;
    const { fieldsMap, filters } = data;
    delete data.fields_map;
    delete data.filters;

    const template = {
      output_fields: [],
      fields_map: {},
    };

    Object.keys(fieldsMap || {}).map(field => {
      Object.keys(fieldsMap[field]).map(column => {
        const reportColumn = fieldsMap[field][column];
        if (reportColumn.present) {
          template.output_fields.push(reportColumn.outputField);
          template.fields_map[reportColumn.outputField] = `${field}.${column}`;
        }
      });
    });
  };

  handleChangeIn = ({ target }) => {
    const { name, type } = target;
    const values = { ...this.state.values };

    const value = type === 'checkbox' ? target.checked : target.value;
    dotToString(name, value, values);

    this.setState({ values });
  };

  handleReportTypeChange = event => {
    const value = event.target.value;

    if (!!value) {
      fetchConfigComponents(event.target.value).then(data => {
        this.setState({ configComponents: { loading: false, data } });
      });
    } else {
      this.setState({ loading: false, data: undefined });
    }
  };

  render() {
    const merchantId = this.props.merchantId;
    const { details } = this.model.merchant;

    return (
      <ModalContent header="Create New Report Config">
        <div class="box">
          <div class="heading">Merchant Id: {merchantId}</div>
          {Object.keys(details).length ? (
            [
              <EntityRow key={0} label="Merchant Name" value={details.name} />,
              <EntityRow
                key={1}
                label="Registered Email"
                value={details.email}
              />,
            ]
          ) : (
            <div class="spinner center" />
          )}
        </div>

        <div class="box ReportConfig">
          <div class="heading">Create New Config Form</div>
          <Form class="full-span full-elements" onChange={this.handleChangeIn}>
            {Object.keys(details).length ? (
              <>
                <DataDetails
                  partnerType={details.partner_type}
                  configOptions={this.state.configOptions}
                  reportEmails={details.transaction_report_email}
                  onChange={this.handleChangeIn}
                />
                <ConfigDetails
                  onReportTypeChange={this.handleReportTypeChange}
                  {...this.state.configComponents.data}
                  selectedFilters={this.state.values.filters}
                />
              </>
            ) : (
              <div class="spinner center" />
            )}

            <AsyncButton
              text="Create"
              class="btn"
              pendingClass="small spinner"
              onClick={this.handleSubmitClick}
            />
          </Form>
        </div>
      </ModalContent>
    );
  }
}

const selfServeReportBase = 'live/admin-reporting/';

function fetchConfigOptions() {
  return adminFetch(selfServeReportBase + 'config-options').then(response => {
    if (response) {
      return response;
    }
  });
}

function fetchConfigComponents(field) {
  return adminFetch(`${selfServeReportBase}config-components/${field}`).then(
    response => {
      if (response) {
        return response;
      }
    }
  );
}

function dotToString(path, value, obj) {
  const parts = path.split('.');
  const last = parts.pop();
  while ((part = parts.shift())) {
    if (typeof obj[part] !== 'object') obj[part] = {};
    obj = obj[part];
  }
  obj[last] = value;
  var part;
}
