import { Component } from 'react';
import Alert from 'rzp/ui/Forms/Alert';
import EntityRow from 'merchant/components/EntityDetailList/Row';

/*
 // Usage: Check slider/details view of payments, plans, etc.
 // Constraint: 1. Pass props 'progressLoader' to <Table> only when progress loaders is shown instead of <Spinner>.
 2. Passing props 'customClass' is advised so as to have more control on `progress loader` length
 */

export default class EntityDetailList extends Component {
  constructor(props) {
    super(props);
    this.state = { curLimit: props.moreAfterlimit };
  }

  getRowList() {
    const { loading, items, goToLink, activeSecEntityId } = this.props;
    let list = [];

    let limit = this.state.curLimit; // Show curLimit number of loaders. Also, default curLimit rows unless items.length is lesser
    if (!loading && items.length) {
      limit = items.length < this.state.curLimit ? items.length : limit;
    }

    for (let index = 0; index < limit; index++) {
      let item = {};
      if (items.length) {
        item = items[index];
      }

      list.push(
        <EntityRow
          key={index}
          goToLink={goToLink}
          activeSecEntityId={activeSecEntityId}
          index={items.length ? items.length - index : index}
          item={item}
          loading={loading}
        />
      );
    }

    return list;
  }

  render() {
    let { title, subTitle, error, loading, items } = this.props;
    let showBtn = null;

    // 'Show More' btn is visible only when items are available and length of items is more than limit set by parent
    if (
      !loading &&
      items.length &&
      items.length > this.state.curLimit &&
      (this.state.curLimit !== 12 || this.state.curLimit !== items.length)
    ) {
      showBtn = (
        <button
          class="primary-link"
          style={{ display: 'block', margin: '0 auto' }}
          onClick={() =>
            this.setState({
              curLimit: items.length > 12 ? 12 : items.length,
            })}
        >
          Show All <i class="icon icon-chevron-down" />
        </button>
      );
    }

    let rowList = (rowList = this.getRowList());

    if (!loading && !items.length) {
      rowList = <h4 class="empty-table-message">{`No '${title}' Found!`}</h4>;
    }

    return (
      <div class="entity-detail-list">
        <div class="list-heading">
          <span class="label--primary">
            {title}
          </span>
          <span class="label--secondary" style={{ float: 'right' }}>
            {subTitle}
          </span>
        </div>
        <div class="list-content">
          {error && <Alert type="error" message={error} />}
          {rowList}
        </div>
        {showBtn}
      </div>
    );
  }
}
