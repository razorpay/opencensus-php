import React from 'react';
import PropTypes from 'prop-types';
import { classList } from 'common/utils/rzp-utils';
import { formatDate } from 'razorx/helpers/utils';
import { PageTable } from 'razorx/components/ui/Table';

export default // @observer
class ProjectList extends React.Component {
  render() {
    return (
      <div className="list-container">
        <div>
          <PageTable
            model={this.props.collection}
            fields={[
              ['ID', (item) => item.id],
              ['Name', (item) => item.name],
              ['Description', (item) => item.description],
              ['Created On', (item) => formatDate(item.created_at)],
            ]}
            href={(item) => `/splitz/projects/${item.id}`}
            info={false}
          />
        </div>
      </div>
    );
  }
}

ProjectList.propTypes = {
  collection: PropTypes.object.isRequired,
};
