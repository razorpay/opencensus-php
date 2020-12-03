import React from 'react';
import PropTypes from 'prop-types';
import { classList } from 'common/utils/rzp-utils';
import { formatDate } from 'razorx/helpers/utils';
import { PageTable } from 'razorx/components/ui/Table';
import { splitzFetch } from 'razorx/helpers/fetch';
import Form from 'razorx/components/ui/Form';
import Field, {
  TextAreaField,
  SwitchField,
  SelectField,
  SearchableSelectField,
} from 'razorx/components/ui/Field';

export default // @observer
class SegmentList extends React.Component {
  render() {
    return (
      <div className="list-container">
        <div>
          <PageTable
            model={this.props.collection}
            fields={[
              ['ID', (item) => item.id],
              ['Name', (item) => item.name],
              ['Entries', (item) => item.entries],
              ['Created On', (item) => formatDate(item.created_at)],
            ]}
            href={(item) => `/splitz/segments/${item.id}`}
            info={false}
          />
        </div>
      </div>
    );
  }
}

SegmentList.propTypes = {
  collection: PropTypes.object.isRequired,
};
