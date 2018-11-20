import { Component } from 'react';
import { observer } from 'mobx-react';

import { ModalContent } from 'component/Modal';
import { adminFetch } from 'common/fetch';

import TabsContainer from 'ui/Tabs';
import EntityRow from 'ui/EntityRow';
import Form from 'ui/Form';

import Model from '../../model';

import ConfigDetails from './ConfigDetails';

@observer
export default class CreateMerchantReportConfig extends Component {
  state = {
    configTypes: {
      loading: true,
      data: [],
    },
    configOptions: {
      loading: true,
      data: {},
    },
  };
  constructor(props) {
    super();

    this.model = new Model({
      fetchFn: adminFetch,
      merchantId: props.merchantId,
    });
  }

  componentWillMount() {
    fetchConfigTypes().then(data => {
      this.setState({ configTypes: { loading: false, data } });
    });

    fetchConfigOptions().then(({ file: data }) => {
      this.setState({ configOptions: { loading: false, data } });
    });
  }

  render() {
    const merchantId = this.props.merchantId;
    const { details } = this.model.merchant;
    return (
      <ModalContent header="Create New Report Config">
        <div class="box">
          <div class="heading">Merchant Id: {merchantId}</div>
          {!Object.keys(details).length ? (
            <div class="spinner center" />
          ) : (
            [
              <EntityRow label="Merchant Name" value={details.name} />,
              <EntityRow label="Registered Email" value={details.email} />,
            ]
          )}
        </div>

        <div class="box">
          <div class="heading">Create New Config Form</div>
          <Form class="full-span full-elements">
            <TabsContainer tabNames={tabNames}>
              <ConfigDetails
                configOptions={this.state.configOptions}
                configTypes={this.state.configTypes}
              />

              <div>Page 2</div>
            </TabsContainer>
          </Form>
        </div>
      </ModalContent>
    );
  }
}

const tabNames = ['Page 1', 'Page 2'];

const selfServeReportBase = 'live/admin-reporting/';
function fetchConfigOptions() {
  return adminFetch(selfServeReportBase + 'config-options').then(response => {
    if (response) {
      return response;
    }
  });
}

function fetchConfigTypes() {
  return adminFetch(selfServeReportBase + 'config-types').then(response => {
    if (response) {
      return response;
    }
  });
}
