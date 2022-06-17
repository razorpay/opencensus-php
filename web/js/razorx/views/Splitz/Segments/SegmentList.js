import React from 'react';
import PropTypes from 'prop-types';
import { formatDate } from 'razorx/helpers/utils';
import { PageTable } from 'razorx/components/ui/Table';
import Form from 'razorx/components/ui/Form';
import { SearchableSelectField } from 'razorx/components/ui/Field';
import { splitzFetch } from 'razorx/helpers/fetch';
import { SEGMENT_LIST } from './constants';
import { notifyError } from 'razorx/components/Modal';
import SegmentSearchItem from './SegmentSearchItem';

export default // @observer
class SegmentList extends React.Component {
  state = {
    segments: null,
    selectedSegmentName: null,
    selectedSegmentId: null,
  };

  getSegmentDetails = () => {
    splitzFetch({
      url: SEGMENT_LIST,
      data: {
        limit: 100,
        offset: 0,
      },
    })
      .then((response) => {
        this.setState({ segments: response?.items });
      })
      .catch((error) => {
        notifyError(error);
      });
  };

  componentDidMount() {
    this.getSegmentDetails();
  }

  filterList = () => {
    const { selectedSegmentId, selectedSegmentName } = this.state;

    const { collection } = this.props;

    collection.applyFilters({
      id: selectedSegmentId || '',
      name: selectedSegmentName || '',
    });
  };

  resetFilters = (e) => {
    const { collection } = this.props;
    // Clean filters in collection
    collection?.resetFilters();

    this.setState({
      selectedSegmentId: null,
      selectedSegmentName: null,
    });

    // Clear filters in UI form
    const form = e?.currentTarget?.closest('form') || {};
    form.reset?.();

    collection?.fetch();
  };

  handleSelectedName = (segment) => {
    this.setState({ selectedSegmentName: segment?.option?.name || null }, () => {
      this.filterList();
    });
  };

  handleSelectedId = (segment) => {
    this.setState({ selectedSegmentId: segment?.option?.id || null }, () => {
      this.filterList();
    });
  };

  render() {
    const { segments, selectedSegmentId, selectedSegmentName } = this.state;
    return (
      <div className="list-container">
        <Form onSubmit={this.filterList} className="filters">
          <SearchableSelectField
            optionComponent={(segment) => <SegmentSearchItem text={segment?.option?.name} />}
            name="segment_name"
            placeholder="Select a segment name"
            label="Select Segment Name"
            searchIndices={['name']}
            options={segments || []}
            selected={selectedSegmentName}
            onChange={this.handleSelectedName}
            className="search-box"
          />
          <SearchableSelectField
            name="segment_id"
            optionComponent={(segment) => <SegmentSearchItem text={segment?.option?.id} />}
            placeholder="Select a segment ID"
            label="Select Segment ID"
            searchIndices={['id']}
            options={segments || []}
            selected={selectedSegmentId}
            onChange={this.handleSelectedId}
            className="search-box"
          />
          <button type="button" className="btn btn--primary field">
            Search
          </button>
          <button type="button" onClick={this.resetFilters} className="btn btn--link field">
            Clear
          </button>
        </Form>
        <div>
          <PageTable
            model={this.props.collection}
            fields={[
              ['ID', (item) => item.id],
              ['Name', (item) => item.name],
              ['Type', (item) => item.source_type],
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
